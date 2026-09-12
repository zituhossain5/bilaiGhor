<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FundTransaction;
use App\Models\FundTransactionLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\AccountingSummaryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FundController extends Controller
{
    /**
     * ফান্ড ড্যাশবোর্ড + হিস্টরি লিস্ট
     */
    public function index(Request $request)
    {
        $period = $this->resolvePeriod($request);
        $summary = AccountingSummaryService::businessSummary($period['from'], $period['to']);

        $transactions = $this->transactionQuery($period)
            ->with(['logs', 'creator:id,name'])
            ->paginate(20)
            ->withQueryString();

        $investmentQuery = FundTransaction::includedInAccounting()
            ->where('direction', 'in')
            ->whereIn('source', ['investment', 'manual_add'])
            ->orderByDesc(DB::raw('COALESCE(transaction_date, DATE(created_at))'))
            ->orderByDesc('id');
        AccountingSummaryService::applyDateRange(
            $investmentQuery,
            'COALESCE(transaction_date, DATE(created_at))',
            $period['from'],
            $period['to'],
            rawColumn: true
        );
        $investments = $investmentQuery
            ->with('creator:id,name')
            ->paginate(15, ['*'], 'investments_page')
            ->withQueryString();

        $fundTotals = AccountingSummaryService::fundTotals();
        $total_in = $fundTotals['in'];
        $total_out = $fundTotals['out'];
        $balance = $fundTotals['balance'];
        $hasInitialInvestment = FundTransaction::includedInAccounting()
            ->where('direction', 'in')
            ->whereIn('source', ['investment', 'manual_add'])
            ->where(function ($query) {
                $query->where('investment_type', 'initial')
                    ->orWhere(function ($legacy) {
                        $legacy->where('source', 'manual_add')->whereNull('investment_type');
                    });
            })
            ->exists();
        $isAdmin = $this->isAdmin();
        $withdrawalToken = (string) Str::uuid();

        return view('backEnd.fund.index', compact(
            'period',
            'summary',
            'balance',
            'transactions',
            'investments',
            'total_in',
            'total_out',
            'hasInitialInvestment',
            'isAdmin',
            'withdrawalToken'
        ));
    }

    /**
     * ফান্ড Add
     */
    public function add(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'investment_type' => 'required|in:initial,additional',
            'transaction_date' => 'required|date',
            'note'   => 'nullable|string|max:1000'
        ]);

        DB::transaction(function () use ($validated) {
            if ($validated['investment_type'] === 'initial') {
                $initialExists = FundTransaction::includedInAccounting()
                    ->where('direction', 'in')
                    ->whereIn('source', ['investment', 'manual_add'])
                    ->where(function ($query) {
                        $query->where('investment_type', 'initial')
                            ->orWhere(function ($legacy) {
                                $legacy->where('source', 'manual_add')->whereNull('investment_type');
                            });
                    })
                    ->lockForUpdate()
                    ->exists();

                if ($initialExists) {
                    throw ValidationException::withMessages([
                        'investment_type' => 'Initial investment already exists. Record this as an additional investment.',
                    ]);
                }
            }

            FundTransaction::create([
                'direction'  => 'in',
                'source'     => 'investment',
                'source_id'  => null,
                'investment_type' => $validated['investment_type'],
                'amount'     => round((float)$validated['amount'], 2),
                'transaction_date' => $validated['transaction_date'],
                'note'       => $validated['note'] ?? null,
                'created_by' => Auth::guard('admin')->id(),
            ]);
        });

        return back()->with('success', 'Investment recorded successfully.');
    }

    /**
     * ফান্ড Withdraw
     */
    public function withdraw(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'note' => 'nullable|string|max:1000',
            'idempotency_key' => 'required|uuid',
        ]);

        // calculate balance inside transaction and lock rows if concurrent operations possible
        // simple approach: compute current balance, then create out tx
        return DB::transaction(function () use ($validated) {
            $existing = FundTransaction::query()
                ->where('idempotency_key', $validated['idempotency_key'])
                ->lockForUpdate()
                ->first();
            if ($existing) {
                return redirect()->back()->with('success', 'This withdrawal was already recorded. No duplicate was created.');
            }

            $balance = AccountingSummaryService::lockedFundBalance();

            $amount = round((float)$validated['amount'], 2);

            if ($amount > $balance) {
                // throw ValidationException to redirect back with error
                throw ValidationException::withMessages([
                    'amount' => 'Withdrawal exceeds the available fund balance of ' . number_format($balance, 2) . '.',
                ]);
            }

            $withdrawal = FundTransaction::query()->firstOrCreate([
                'idempotency_key' => $validated['idempotency_key'],
            ], [
                'direction'  => 'out',
                'source'     => 'withdraw',
                'source_id'  => null,
                'amount'     => $amount,
                'transaction_date' => $validated['transaction_date'],
                'note'       => $validated['note'] ?? null,
                'created_by' => Auth::guard('admin')->id(),
            ]);

            if (! $withdrawal->wasRecentlyCreated) {
                return redirect()->back()->with('success', 'This withdrawal was already recorded. No duplicate was created.');
            }

            return redirect()->back()->with('success', 'Withdraw successful!');
        });
    }

    /**
     * ফান্ড হিস্টরি Export (CSV)
     * filter = year | month | custom
     */
    public function export(Request $request)
    {
        $period = $this->resolvePeriod($request);
        $summary = AccountingSummaryService::businessSummary($period['from'], $period['to']);
        $fundTotals = AccountingSummaryService::fundTotals();
        $query = $this->transactionQuery($period, ascending: true)->with('creator:id,name');

        // File name
        $fileName = 'fund-history-'.now()->format('Y-m-d-H-i-s').'.csv';

        // Streamed response with chunking for large datasets
        $response = new StreamedResponse(function () use ($query, $period, $summary, $fundTotals) {
            $handle = fopen('php://output', 'w');

            // Add UTF-8 BOM so Excel can open correctly
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['Bilai Ghor Account & Fund Report']);
            fputcsv($handle, ['Period', $period['label']]);
            fputcsv($handle, ['Available Fund Balance', number_format($fundTotals['balance'], 2, '.', '')]);
            fputcsv($handle, ['Investment Added', number_format($summary['period_investment'], 2, '.', '')]);
            fputcsv($handle, ['Sales Revenue', number_format($summary['sales_revenue'], 2, '.', '')]);
            fputcsv($handle, ['COGS', number_format($summary['cogs'], 2, '.', '')]);
            fputcsv($handle, ['Gross Profit', number_format($summary['gross_profit'], 2, '.', '')]);
            fputcsv($handle, ['Expenses', number_format($summary['expenses'], 2, '.', '')]);
            fputcsv($handle, ['Net Profit', number_format($summary['net_profit'], 2, '.', '')]);
            fputcsv($handle, ['Withdrawals', number_format($summary['withdrawals'], 2, '.', '')]);
            fputcsv($handle, ['Period Fund Change', number_format($summary['period_fund_change'], 2, '.', '')]);
            fputcsv($handle, []);
            fputcsv($handle, ['#', 'Date & Time', 'Type', 'Source', 'Amount', 'Note / Reference', 'Created By']);

            // chunk to avoid memory issues
            $rowNumber = 0;
            $query->chunk(500, function ($transactions) use ($handle, &$rowNumber) {
                foreach ($transactions as $tx) {
                    fputcsv($handle, [
                        ++$rowNumber,
                        $this->transactionDateTime($tx),
                        strtoupper($tx->direction),
                        str_replace('_', ' ', $tx->source),
                        number_format((float)$tx->amount, 2, '.', ''),
                        $tx->note,
                        $tx->creator?->name ?? 'System',
                    ]);
                }
            });

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', "attachment; filename=\"$fileName\"");

        return $response;
    }

    public function exportPdf(Request $request)
    {
        $period = $this->resolvePeriod($request);
        $summary = AccountingSummaryService::businessSummary($period['from'], $period['to']);
        $fundTotals = AccountingSummaryService::fundTotals();
        $transactions = $this->transactionQuery($period, ascending: true)
            ->with('creator:id,name')
            ->get();

        return Pdf::loadView('backEnd.fund.report_pdf', compact(
            'period',
            'summary',
            'fundTotals',
            'transactions'
        ))->setPaper('a4', 'landscape')
            ->download('fund-report-' . now()->format('Y-m-d-H-i-s') . '.pdf');
    }

    /**
     * Check if current user is Admin (Super Admin or has Admin role)
     */
    private function isAdmin()
    {
        $user = Auth::guard('admin')->user();
        if (!$user) {
            return false;
        }

        // Super Admin (id=1) is always admin
        if ($user->id == 1) {
            return true;
        }

        // Check if user has Admin role
        $spatieRoles = $user->getRoleNames()->map(function($role) {
            return strtolower($role);
        })->toArray();

        return in_array('admin', $spatieRoles);
    }

    /**
     * Edit Fund Transaction (Admin only)
     */
    public function edit($id)
    {
        if (!$this->isAdmin()) {
            abort(403, 'Only Admin can edit fund transactions.');
        }

        $transaction = FundTransaction::includedInAccounting()->findOrFail($id);
        abort_unless($transaction->isManuallyEditable(), 422, 'System-generated transactions cannot be edited. Use reconciliation for corrections.');
        return view('backEnd.fund.edit', compact('transaction'));
    }

    /**
     * Calculate current fund balance
     */
    private function calculateBalance()
    {
        return AccountingSummaryService::fundBalance();
    }

    /**
     * Update Fund Transaction (Admin only)
     */
    public function update(Request $request, $id)
    {
        if (!$this->isAdmin()) {
            abort(403, 'Only Admin can update fund transactions.');
        }

        $transaction = FundTransaction::includedInAccounting()->findOrFail($id);
        $isInvestment = in_array($transaction->source, ['investment', 'manual_add'], true);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'note'   => 'nullable|string|max:1000',
            'direction' => $isInvestment ? 'nullable' : 'required|in:in,out',
            'investment_type' => $isInvestment ? 'required|in:initial,additional' : 'nullable',
            'transaction_date' => $isInvestment ? 'required|date' : 'nullable|date',
        ]);

        return DB::transaction(function () use ($validated, $id, $isInvestment) {
            $transaction = FundTransaction::includedInAccounting()->lockForUpdate()->findOrFail($id);
            abort_unless($transaction->isManuallyEditable(), 422, 'System-generated transactions cannot be edited. Use reconciliation for corrections.');

            // Save old values for logging
            $old_amount = $transaction->amount;
            $old_direction = $transaction->direction;
            $old_note = $transaction->note;

            // Calculate balance before update
            $balance_before = $this->calculateBalance();

            // Update transaction
            $new_amount = round((float)$validated['amount'], 2);
            $new_direction = $isInvestment ? 'in' : $validated['direction'];
            $new_note = $validated['note'] ?? null;

            if ($isInvestment && $validated['investment_type'] === 'initial') {
                $duplicateInitial = FundTransaction::includedInAccounting()
                    ->where('id', '!=', $transaction->id)
                    ->where('direction', 'in')
                    ->whereIn('source', ['investment', 'manual_add'])
                    ->where('investment_type', 'initial')
                    ->lockForUpdate()
                    ->exists();

                if ($duplicateInitial) {
                    throw ValidationException::withMessages([
                        'investment_type' => 'Another initial investment already exists.',
                    ]);
                }
            }

            $transaction->update([
                'amount'    => $new_amount,
                'note'      => $new_note,
                'direction' => $new_direction,
                'source' => $isInvestment ? 'investment' : $transaction->source,
                'investment_type' => $isInvestment ? $validated['investment_type'] : null,
                'transaction_date' => $isInvestment ? $validated['transaction_date'] : $transaction->transaction_date,
                'updated_by' => Auth::guard('admin')->id(),
            ]);

            // Calculate balance after update
            $balance_after = $this->calculateBalance();

            // Create log entry
            $description = $this->generateEditDescription(
                $old_direction, $old_amount, $old_note,
                $new_direction, $new_amount, $new_note,
                $balance_before, $balance_after
            );

            FundTransactionLog::create([
                'fund_transaction_id' => $transaction->id,
                'action' => 'edit',
                'old_direction' => $old_direction,
                'new_direction' => $new_direction,
                'old_amount' => $old_amount,
                'new_amount' => $new_amount,
                'balance_before' => $balance_before,
                'balance_after' => $balance_after,
                'old_note' => $old_note,
                'new_note' => $new_note,
                'description' => $description,
                'performed_by' => Auth::id(),
            ]);

            return redirect()->route('admin.fund.index')
                            ->with('success', 'Fund transaction updated successfully! Balance adjusted automatically.');
        });
    }

    /**
     * Generate description for edit log
     */
    private function generateEditDescription($old_dir, $old_amt, $old_note, $new_dir, $new_amt, $new_note, $bal_before, $bal_after)
    {
        $parts = [];
        
        if ($old_dir != $new_dir) {
            $parts[] = "Direction changed from {$old_dir} to {$new_dir}";
        }
        
        if ($old_amt != $new_amt) {
            $diff = $new_amt - $old_amt;
            $diff_sign = ($diff > 0) ? '+' : '';
            $parts[] = "Amount changed from {$old_amt} to {$new_amt} ({$diff_sign}{$diff})";
        }
        
        $balance_diff = $bal_after - $bal_before;
        $balance_sign = ($balance_diff > 0) ? '+' : '';
        $parts[] = "Balance changed from {$bal_before} to {$bal_after} ({$balance_sign}{$balance_diff})";
        
        return implode('. ', $parts);
    }

    /**
     * Delete Fund Transaction (Admin only)
     */
    public function destroy($id)
    {
        if (!$this->isAdmin()) {
            abort(403, 'Only Admin can delete fund transactions.');
        }

        return DB::transaction(function () use ($id) {
            $transaction = FundTransaction::includedInAccounting()->lockForUpdate()->findOrFail($id);
            abort_unless($transaction->isManuallyEditable(), 422, 'System-generated transactions cannot be deleted. Use reconciliation for corrections.');

            // Save transaction data for logging
            $old_amount = $transaction->amount;
            $old_direction = $transaction->direction;
            $old_note = $transaction->note;

            // Calculate balance before delete
            $balance_before = $this->calculateBalance();

            // Calculate expected balance after delete (before actually deleting)
            // This helps us know what the balance will be
            $expected_balance_after = $balance_before;
            if ($old_direction == 'in') {
                $expected_balance_after = $balance_before - $old_amount; // Removing IN reduces balance
            } else {
                $expected_balance_after = $balance_before + $old_amount; // Removing OUT increases balance
            }

            // Create log entry BEFORE deleting (to avoid foreign key constraint violation)
            $balance_diff = $expected_balance_after - $balance_before;
            $balance_sign = ($balance_diff > 0) ? '+' : '';
            $description = "Transaction deleted: {$old_direction} {$old_amount}. Balance changed from {$balance_before} to {$expected_balance_after} ({$balance_sign}{$balance_diff})";

            FundTransactionLog::create([
                'fund_transaction_id' => $id, // Keep reference to transaction ID before deletion
                'action' => 'delete',
                'old_direction' => $old_direction,
                'new_direction' => null,
                'old_amount' => $old_amount,
                'new_amount' => null,
                'balance_before' => $balance_before,
                'balance_after' => $expected_balance_after,
                'old_note' => $old_note,
                'new_note' => null,
                'description' => $description,
                'performed_by' => Auth::id(),
            ]);

            // Now delete the transaction (log entry already created, so FK constraint won't fail)
            $transaction->delete();

            // Verify balance after delete (for confirmation)
            $actual_balance_after = $this->calculateBalance();

            return redirect()->route('admin.fund.index')
                            ->with('success', 'Fund transaction deleted successfully! Balance adjusted automatically.');
        });
    }

    /**
     * Fund Transaction Logs / Report
     */
    public function logs(Request $request)
    {
        $query = FundTransactionLog::with(['fundTransaction', 'performedBy'])
            ->orderBy('created_at', 'desc');

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $logs = $query->paginate(20)->withQueryString();

        // Summary statistics
        $total_edits = FundTransactionLog::where('action', 'edit')->count();
        $total_deletes = FundTransactionLog::where('action', 'delete')->count();

        return view('backEnd.fund.logs', compact('logs', 'total_edits', 'total_deletes'));
    }

    private function resolvePeriod(Request $request): array
    {
        $mode = $request->input('mode', $request->input('filter', 'current_month'));

        $validated = $request->validate([
            'mode' => 'nullable|in:current_month,previous_month,month,year,custom,all',
            'filter' => 'nullable|in:current_month,previous_month,month,year,custom,all',
            'year' => 'nullable|integer|min:2000|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
            'from_date' => [$mode === 'custom' ? 'required' : 'nullable', 'date'],
            'to_date' => [$mode === 'custom' ? 'required' : 'nullable', 'date', 'after_or_equal:from_date'],
        ]);

        return AccountingSummaryService::period(
            $mode,
            isset($validated['year']) ? (int) $validated['year'] : null,
            isset($validated['month']) ? (int) $validated['month'] : null,
            $validated['from_date'] ?? null,
            $validated['to_date'] ?? null,
        );
    }

    private function transactionQuery(array $period, bool $ascending = false)
    {
        $query = FundTransaction::query()->includedInAccounting();
        AccountingSummaryService::applyDateRange(
            $query,
            'COALESCE(transaction_date, DATE(created_at))',
            $period['from'],
            $period['to'],
            rawColumn: true
        );

        $direction = $ascending ? 'asc' : 'desc';

        return $query
            ->orderByRaw("COALESCE(transaction_date, DATE(created_at)) {$direction}")
            ->orderBy('created_at', $direction)
            ->orderBy('id', $direction);
    }

    private function transactionDateTime(FundTransaction $transaction): string
    {
        $date = ($transaction->transaction_date ?: $transaction->created_at)->format('d/m/Y');
        $time = $transaction->created_at?->format('h:i A');

        return trim($date . ' ' . $time);
    }
}
