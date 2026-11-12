<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseLog;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Product;
use App\Models\ProductVariantPrice;
use App\Models\FundTransaction;
use App\Models\Expense;
use App\Services\HistoricalPurchaseService;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\AccountingSummaryService;
use Illuminate\Validation\ValidationException;

class PurchaseController extends Controller
{
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
     * Calculate current fund balance
     */
    private function calculateFundBalance()
    {
        return AccountingSummaryService::fundBalance();
    }
    /**
     * ==============================
     * Purchase List + Summary
     * AJAX Pagination Supported
     * ==============================
     */
    public function index(Request $request)
    {
        $currentYear  = now()->year;
        $currentMonth = now()->month;

        // SUMMARY
        $yearlyTotal = Purchase::whereYear('purchase_date', $currentYear)->sum('grand_total');
        $monthlyTotal = Purchase::whereYear('purchase_date', $currentYear)
                                ->whereMonth('purchase_date', $currentMonth)
                                ->sum('grand_total');
        $todayTotal = Purchase::whereDate('purchase_date', now()->toDateString())
                              ->sum('grand_total');
        $totalDue = Purchase::sum('due_amount');

        // QUERY
        $query = Purchase::with('supplier')->latest();
        if ($request->integer('supplier_id') > 0) {
            $query->where('supplier_id', $request->integer('supplier_id'));
        }

        if ($request->year) {
            $query->whereYear('purchase_date', $request->year);
        }
        if ($request->month) {
            $query->whereMonth('purchase_date', $request->month);
        }
        if ($request->from_date) {
            $query->whereDate('purchase_date', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('purchase_date', '<=', $request->to_date);
        }

        $purchases = $query->paginate(10)->withQueryString();

        $suppliers = Supplier::orderBy('name')->get();
        $products  = Product::orderBy('name')->get();

        $reclassifiableExpenses = $this->isAdmin()
            ? Expense::includedInAccounting()->whereNull('reclassified_at')->orderByDesc('expense_date')->get()
            : collect();

        return view('backEnd.purchases.index', compact(
            'currentYear',
            'currentMonth',
            'yearlyTotal',
            'monthlyTotal',
            'todayTotal',
            'totalDue',
            'purchases',
            'suppliers',
            'products', 'reclassifiableExpenses'
        ));
    }

    /**
     * ==============================
     * Store Purchase
     * ==============================
     */
    public function store(Request $request)
    {
        if (! $request->has('lines') && $request->has('product_id')) {
            $request->merge(['lines' => [[
                'product_id' => $request->input('product_id'),
                'variant_price_id' => $request->input('variant_price_id'),
                'qty' => $request->input('qty'),
                'unit_cost' => $request->input('unit_cost'),
            ]]]);
        }
        $mode = $request->input('purchase_mode', 'normal');
        if ($mode === 'historical' && ! $this->isAdmin()) {
            abort(403, 'Only Admin can enter historical purchases.');
        }
        $validated = $request->validate([
            'supplier_id'   => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date_format:Y-m-d',
            'invoice_no'    => 'required|string|max:50',
            'purchase_mode' => 'nullable|in:normal,historical',
            'lines' => 'required|array|min:1|max:100',
            'lines.*.product_id' => 'required|integer|exists:products,id',
            'lines.*.variant_price_id' => 'nullable|integer|exists:product_variant_prices,id',
            'lines.*.qty' => 'required|integer|min:1',
            'lines.*.unit_cost' => 'required|numeric|min:0',
            'discount'      => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'paid_amount'   => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:2000',
            'accounting_treatment' => [Rule::requiredIf($mode === 'historical'), 'nullable', 'in:reclassify_expense,already_recorded,new_payment'],
            'expense_id' => [Rule::requiredIf($mode === 'historical' && $request->input('accounting_treatment') === 'reclassify_expense'), 'nullable', 'integer', 'exists:expenses,id'],
            'accounting_reference' => 'nullable|string|max:255',
        ]);

        $productIds = array_column($validated['lines'], 'product_id');
        if (count($productIds) !== count(array_unique($productIds))) {
            throw ValidationException::withMessages(['lines' => 'Each product may appear only once per invoice.']);
        }
        foreach ($validated['lines'] as $line) {
            if (! empty($line['variant_price_id']) && ! ProductVariantPrice::whereKey($line['variant_price_id'])
                ->where('product_id', $line['product_id'])->exists()) {
                throw ValidationException::withMessages(['lines' => 'A selected variant does not belong to its product.']);
            }
        }
        if ($mode === 'historical') {
            (new HistoricalPurchaseService())->create($validated, (int) Auth::guard('admin')->id());
            return back()->with('success', 'Historical purchase recorded. Current stock was not changed.');
        }

        return DB::transaction(function () use ($request, $validated) {

        $discount      = $request->discount ?? 0;
        $shipping_cost = $request->shipping_cost ?? 0;

        $qty = array_sum(array_column($validated['lines'], 'qty'));
        $subtotal = round(array_sum(array_map(
            fn ($line) => (int) $line['qty'] * (float) $line['unit_cost'], $validated['lines']
        )), 2);
        $grandTotal = $subtotal - $discount + $shipping_cost;

        if ($grandTotal <= 0 || (float) ($request->paid_amount ?? 0) > $grandTotal) {
            throw ValidationException::withMessages(['paid_amount' => 'Total must be positive and paid amount cannot exceed it.']);
        }

        $paid = min($grandTotal, (float) ($request->paid_amount ?? 0));
        $due  = $grandTotal - $paid;

        $fundBalance = AccountingSummaryService::lockedFundBalance();
        if ($paid > $fundBalance) {
            throw ValidationException::withMessages([
                'paid_amount' => 'Not enough balance in fund. Available: ' . number_format($fundBalance, 2),
            ]);
        }

        // CREATE PURCHASE
        $purchase = Purchase::create([
            'supplier_id'   => $request->supplier_id,
            'invoice_no'    => $request->invoice_no,
            'purchase_date' => $request->purchase_date,
            'total_qty'     => $qty,
            'subtotal'      => $subtotal,
            'discount'      => $discount,
            'shipping_cost' => $shipping_cost,
            'grand_total'   => $grandTotal,
            'paid_amount'   => $paid,
            'due_amount'    => $due,
            'note'          => $request->note,
            'status'        => 'completed',
            'created_by'    => Auth::id(),
            'purchase_mode' => 'normal',
        ]);

        foreach ($validated['lines'] as $line) {
            PurchaseItem::create([
                'purchase_id' => $purchase->id,
                'product_id' => $line['product_id'],
                'variant_price_id' => $line['variant_price_id'] ?? null,
                'qty' => $line['qty'],
                'unit_cost' => $line['unit_cost'],
                'line_total' => round((int) $line['qty'] * (float) $line['unit_cost'], 2),
            ]);
            Product::whereKey($line['product_id'])->update(['purchase_price' => $line['unit_cost']]);
            if (! empty($line['variant_price_id'])) {
                ProductVariantPrice::whereKey($line['variant_price_id'])->increment('stock', $line['qty']);
            }
        }

        // STOCK UPDATE — creating a purchase means the goods physically arrived
        // (this shop records purchases on receipt), so receive it into inventory.
        // Idempotent: the service never books the same purchase in twice.
        \App\Services\InventoryService::receivePurchase($purchase);

        // SUPPLIER DUE
        $supplier = Supplier::findOrFail($request->supplier_id);
        $supplier->current_due += ($grandTotal - $paid);
        $supplier->save();

        // FUND PAYMENT
        if ($paid > 0) {
            $fund = FundTransaction::create([
                'direction'  => 'out',
                'source'     => 'supplier_payment',
                'source_id'  => null,
                'amount'     => $paid,
                'note'       => 'Purchase payment: '.$purchase->invoice_no,
                'created_by' => Auth::id(),
            ]);

            $payment = SupplierPayment::create([
                'supplier_id'        => $supplier->id,
                'purchase_id'        => $purchase->id,
                'amount'             => $paid,
                'payment_date'       => $request->purchase_date,
                'method'             => 'fund',
                'note'               => 'Initial payment',
                'fund_transaction_id'=> $fund->id,
                'created_by'         => Auth::id(),
            ]);

            $fund->source_id = $payment->id;
            $fund->save();
        }

        return back()->with('success','Purchase created & stock updated!');
        });
    }

    /**
     * ==============================
     * Pay Supplier Due
     * ==============================
     */
    public function payDue(Request $request, $id)
    {
        $purchase = Purchase::findOrFail($id);

        $request->validate([
            'amount'       => 'required|numeric|min:1',
            'payment_date' => 'required|date',
        ]);

        if ($request->amount > $purchase->due_amount) {
            return back()->with('error','Pay amount cannot be greater than due.');
        }

        $fund = FundTransaction::create([
            'direction'  => 'out',
            'source'     => 'supplier_payment',
            'source_id'  => null,
            'amount'     => $request->amount,
            'note'       => 'Due payment: '.$purchase->invoice_no,
            'created_by' => Auth::id(),
        ]);

        $payment = SupplierPayment::create([
            'supplier_id'        => $purchase->supplier_id,
            'purchase_id'        => $purchase->id,
            'amount'             => $request->amount,
            'payment_date'       => $request->payment_date,
            'method'             => 'fund',
            'note'               => $request->note,
            'fund_transaction_id'=> $fund->id,
            'created_by'         => Auth::id(),
        ]);

        $fund->source_id = $payment->id;
        $fund->save();

        $purchase->paid_amount += $request->amount;
        $purchase->due_amount  -= $request->amount;
        $purchase->save();

        $supplier = $purchase->supplier;
        $supplier->current_due = max(0, $supplier->current_due - $request->amount);
        $supplier->save();

        return back()->with('success','Due payment successful!');
    }

    /**
     * ==============================
     * Purchase Return
     * ==============================
     */
    public function returnItem(Request $request, $itemId)
    {
        $item = PurchaseItem::with('purchase','product','variant')->findOrFail($itemId);
        if ($item->purchase?->purchase_mode === 'historical') {
            abort(403, 'Historical purchases cannot be returned as current inventory.');
        }

        $request->validate([
            'return_qty' => 'required|integer|min:1',
        ]);

        $qty = (int) $request->return_qty;

        if ($qty > ($item->qty - $item->returned_qty)) {
            return back()->with('error','Return qty cannot be greater than remaining qty.');
        }

        $item->returned_qty += $qty;
        $item->save();

        \App\Services\InventoryService::returnPurchaseItem($item, $qty, Auth::id());

        if ($item->variant) {
            $item->variant->decrement('stock', $qty);
        }

        return back()->with('success','Purchase return processed & stock updated.');
    }

    /**
     * ==============================
     * Invoice
     * ==============================
     */
    public function invoice($id)
    {
        $purchase = Purchase::with(['supplier','items.product','items.variant','payments','reclassifiedExpense'])
                            ->findOrFail($id);
        return view('backEnd.purchases.invoice', compact('purchase'));
    }

    /**
     * ==============================
     * Export CSV
     * ==============================
     */
    public function export(Request $request)
    {
        $query = Purchase::with('supplier')->orderBy('purchase_date','asc');
        if ($request->integer('supplier_id') > 0) {
            $query->where('supplier_id', $request->integer('supplier_id'));
        }

        if ($request->year) {
            $query->whereYear('purchase_date',$request->year);
        }
        if ($request->month) {
            $query->whereMonth('purchase_date',$request->month);
        }
        if ($request->from_date) {
            $query->whereDate('purchase_date','>=',$request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('purchase_date','<=',$request->to_date);
        }

        $purchases = $query->get();

        $filename = 'purchases_'.now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($purchases) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date','Invoice','Supplier','Type','Total Qty','Grand Total','Paid','Due','Accounting Treatment']);

            foreach ($purchases as $p) {
                fputcsv($handle, [
                    $p->purchase_date ? Carbon::parse($p->purchase_date)->format('d/m/Y') : '',
                    $p->invoice_no,
                    optional($p->supplier)->name,
                    $p->purchase_mode ?? 'normal',
                    $p->total_qty,
                    $p->grand_total,
                    $p->paid_amount,
                    $p->due_amount,
                    $p->accounting_treatment,
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Edit Purchase (Admin only)
     */
    public function edit($id)
    {
        if (!$this->isAdmin()) {
            abort(403, 'Only Admin can edit purchases.');
        }

        $purchase = Purchase::with(['supplier', 'items.product', 'items.variant', 'payments'])->findOrFail($id);
        if ($purchase->purchase_mode === 'historical') {
            return view('backEnd.purchases.historical_edit', compact('purchase'));
        }
        $suppliers = Supplier::orderBy('name')->get();
        $products  = Product::orderBy('name')->get();

        return view('backEnd.purchases.edit', compact('purchase', 'suppliers', 'products'));
    }

    /**
     * Update Purchase (Admin only)
     */
    public function update(Request $request, $id)
    {
        if (!$this->isAdmin()) {
            abort(403, 'Only Admin can update purchases.');
        }

        $existing = Purchase::findOrFail($id);
        if ($existing->purchase_mode === 'historical') {
            $data = $request->validate([
                'purchase_mode' => 'required|in:historical',
                'invoice_no' => 'required|string|max:50',
                'purchase_date' => 'required|date_format:Y-m-d',
                'note' => 'nullable|string|max:2000',
                'lines' => 'required|array|min:1|max:100',
                'lines.*.item_id' => 'required|integer|distinct',
                'lines.*.qty' => 'required|integer|min:1',
                'lines.*.unit_cost' => 'required|numeric|min:0',
            ]);
            (new HistoricalPurchaseService())->update($existing, $data, (int) Auth::guard('admin')->id());
            return redirect()->route('purchases.invoice', $id)->with('success', 'Historical purchase updated without changing stock or fund.');
        }
        if ($request->input('purchase_mode', 'normal') !== 'normal') {
            throw ValidationException::withMessages(['purchase_mode' => 'Purchase mode cannot be changed after creation.']);
        }

        $request->validate([
            'invoice_no'    => 'required|string|max:50',
            'purchase_date' => 'required|date',
            'paid_amount'   => 'nullable|numeric|min:0',
            'note'          => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $id) {
            $purchase = Purchase::findOrFail($id);

            // Save old values for logging
            $old_invoice_no = $purchase->invoice_no;
            $old_purchase_date = $purchase->purchase_date;
            $old_paid_amount = $purchase->paid_amount;
            $old_grand_total = $purchase->grand_total;
            $old_note = $purchase->note;

            // Calculate fund balance before update
            $fund_balance_before = $this->calculateFundBalance();

            // Update purchase
            $new_paid_amount = (float) ($request->paid_amount ?? 0);
            $paid_diff = $new_paid_amount - $old_paid_amount;

            $purchase->update([
                'invoice_no'    => $request->invoice_no,
                'purchase_date' => $request->purchase_date,
                'paid_amount'   => $new_paid_amount,
                'due_amount'    => max(0, $purchase->grand_total - $new_paid_amount),
                'note'          => $request->note ?? null,
            ]);

            // Update linked fund transactions if paid amount changed
            if ($paid_diff != 0) {
                // Get all supplier payments for this purchase
                $payments = SupplierPayment::where('purchase_id', $purchase->id)->get();
                $total_paid_via_fund = $payments->sum('amount');

                if ($paid_diff > 0) {
                    // Paid amount increased - need to create new fund transaction
                    $fund = FundTransaction::create([
                        'direction'  => 'out',
                        'source'     => 'supplier_payment',
                        'source_id'  => null,
                        'amount'     => $paid_diff,
                        'note'       => 'Purchase payment update: '.$purchase->invoice_no,
                        'created_by' => Auth::id(),
                    ]);

                    $payment = SupplierPayment::create([
                        'supplier_id'        => $purchase->supplier_id,
                        'purchase_id'        => $purchase->id,
                        'amount'             => $paid_diff,
                        'payment_date'       => $request->purchase_date,
                        'method'             => 'fund',
                        'note'               => 'Payment adjustment',
                        'fund_transaction_id'=> $fund->id,
                        'created_by'         => Auth::id(),
                    ]);

                    $fund->source_id = $payment->id;
                    $fund->save();
                } else {
                    // Paid amount decreased - need to delete/update fund transactions
                    $amount_to_reduce = abs($paid_diff);
                    foreach ($payments as $payment) {
                        if ($amount_to_reduce <= 0) break;
                        
                        if ($payment->amount <= $amount_to_reduce) {
                            // Delete entire payment
                            if ($payment->fund_transaction_id) {
                                $fund = FundTransaction::find($payment->fund_transaction_id);
                                if ($fund) {
                                    $fund->delete();
                                }
                            }
                            $amount_to_reduce -= $payment->amount;
                            $payment->delete();
                        } else {
                            // Reduce payment amount
                            $payment->amount -= $amount_to_reduce;
                            $payment->save();
                            
                            if ($payment->fund_transaction_id) {
                                $fund = FundTransaction::find($payment->fund_transaction_id);
                                if ($fund) {
                                    $fund->amount -= $amount_to_reduce;
                                    $fund->save();
                                }
                            }
                            $amount_to_reduce = 0;
                        }
                    }
                }
            }

            // Update supplier due
            $supplier = $purchase->supplier;
            $supplier->current_due = Purchase::where('supplier_id', $supplier->id)->sum('due_amount');
            $supplier->save();

            // Calculate fund balance after update
            $fund_balance_after = $this->calculateFundBalance();

            // Create log entry
            $description = $this->generateEditDescription(
                $old_invoice_no, $old_purchase_date, $old_paid_amount, $old_grand_total, $old_note,
                $request->invoice_no, $request->purchase_date, $new_paid_amount, $purchase->grand_total, $request->note ?? null,
                $fund_balance_before, $fund_balance_after
            );

            PurchaseLog::create([
                'purchase_id' => $purchase->id,
                'action' => 'edit',
                'old_invoice_no' => $old_invoice_no,
                'new_invoice_no' => $request->invoice_no,
                'old_purchase_date' => $old_purchase_date,
                'new_purchase_date' => $request->purchase_date,
                'old_paid_amount' => $old_paid_amount,
                'new_paid_amount' => $new_paid_amount,
                'old_grand_total' => $old_grand_total,
                'new_grand_total' => $purchase->grand_total,
                'old_note' => $old_note,
                'new_note' => $request->note ?? null,
                'fund_balance_before' => $fund_balance_before,
                'fund_balance_after' => $fund_balance_after,
                'description' => $description,
                'performed_by' => Auth::id(),
            ]);

            return redirect()->route('purchases.index')
                             ->with('success', 'Purchase updated successfully! Fund balance adjusted automatically.');
        });
    }

    /**
     * Generate description for edit log
     */
    private function generateEditDescription($old_inv, $old_date, $old_paid, $old_total, $old_note,
                                             $new_inv, $new_date, $new_paid, $new_total, $new_note,
                                             $bal_before, $bal_after)
    {
        $parts = [];
        
        if ($old_inv != $new_inv) {
            $parts[] = "Invoice changed from '{$old_inv}' to '{$new_inv}'";
        }
        
        if ($old_date != $new_date) {
            $parts[] = "Date changed from {$old_date} to {$new_date}";
        }
        
        if ($old_paid != $new_paid) {
            $diff = $new_paid - $old_paid;
            $diff_sign = ($diff > 0) ? '+' : '';
            $parts[] = "Paid amount changed from {$old_paid} to {$new_paid} ({$diff_sign}{$diff})";
        }
        
        $balance_diff = $bal_after - $bal_before;
        $balance_sign = ($balance_diff > 0) ? '+' : '';
        $parts[] = "Fund balance changed from {$bal_before} to {$bal_after} ({$balance_sign}{$balance_diff})";
        
        return implode('. ', $parts);
    }

    /**
     * Delete Purchase (Admin only)
     */
    public function destroy($id)
    {
        if (!$this->isAdmin()) {
            abort(403, 'Only Admin can delete purchases.');
        }

        if (Purchase::findOrFail($id)->purchase_mode === 'historical') {
            throw ValidationException::withMessages(['purchase' => 'Historical purchases require a controlled reversal; ordinary deletion is disabled.']);
        }

        return DB::transaction(function () use ($id) {
            $purchase = Purchase::with(['items', 'payments'])->findOrFail($id);

            // Save purchase data for logging
            $old_invoice_no = $purchase->invoice_no;
            $old_purchase_date = $purchase->purchase_date;
            $old_paid_amount = $purchase->paid_amount;
            $old_grand_total = $purchase->grand_total;
            $old_note = $purchase->note;

            // Calculate fund balance before delete
            $fund_balance_before = $this->calculateFundBalance();

            // Calculate expected balance after delete
            // When purchase is deleted, all linked fund transactions (OUT) should be removed
            // So balance will increase by total paid amount
            $expected_balance_after = $fund_balance_before + $old_paid_amount;

            // Create log entry BEFORE deleting
            $balance_diff = $expected_balance_after - $fund_balance_before;
            $balance_sign = ($balance_diff > 0) ? '+' : '';
            $description = "Purchase deleted: Invoice '{$old_invoice_no}' (Paid: {$old_paid_amount}, Total: {$old_grand_total}). Fund balance changed from {$fund_balance_before} to {$expected_balance_after} ({$balance_sign}{$balance_diff})";

            PurchaseLog::create([
                'purchase_id' => $id,
                'action' => 'delete',
                'old_invoice_no' => $old_invoice_no,
                'new_invoice_no' => null,
                'old_purchase_date' => $old_purchase_date,
                'new_purchase_date' => null,
                'old_paid_amount' => $old_paid_amount,
                'new_paid_amount' => null,
                'old_grand_total' => $old_grand_total,
                'new_grand_total' => null,
                'old_note' => $old_note,
                'new_note' => null,
                'fund_balance_before' => $fund_balance_before,
                'fund_balance_after' => $expected_balance_after,
                'description' => $description,
                'performed_by' => Auth::id(),
            ]);

            // Delete linked fund transactions (supplier payments)
            foreach ($purchase->payments as $payment) {
                if ($payment->fund_transaction_id) {
                    $fund = FundTransaction::find($payment->fund_transaction_id);
                    if ($fund) {
                        $fund->delete();
                    }
                }
                $payment->delete();
            }

            // Reverse stock updates (audited: leaves correction movements in the ledger)
            \App\Services\InventoryService::reversePurchase($purchase, Auth::id());

            foreach ($purchase->items as $item) {
                if ($item->variant) {
                    $item->variant->stock = max(0, $item->variant->stock - ($item->qty - $item->returned_qty));
                    $item->variant->save();
                }
            }

            // Update supplier due
            $supplier = $purchase->supplier;
            $supplier->current_due = max(0, $supplier->current_due - $purchase->due_amount);
            $supplier->save();

            // Delete purchase items
            $purchase->items()->delete();

            // Now delete the purchase
            $purchase->delete();

            // Verify balance after delete
            $actual_balance_after = $this->calculateFundBalance();

            return redirect()->route('purchases.index')
                             ->with('success', 'Purchase deleted successfully! Fund balance adjusted automatically.');
        });
    }

    public function reverseHistorical(Request $request, $id)
    {
        if (! $this->isAdmin()) {
            abort(403);
        }
        $request->validate(['confirmation' => 'required|in:REVERSE HISTORICAL PURCHASE']);
        (new HistoricalPurchaseService())->reverse(Purchase::findOrFail($id), (int) Auth::guard('admin')->id());
        return redirect()->route('purchases.index')->with('success', 'Historical purchase reversed with no stock change.');
    }

    /**
     * Purchase Logs / Report
     */
    public function logs(Request $request)
    {
        $query = PurchaseLog::with(['purchase', 'performedBy'])
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
        $total_edits = PurchaseLog::where('action', 'edit')->count();
        $total_deletes = PurchaseLog::where('action', 'delete')->count();

        return view('backEnd.purchases.logs', compact('logs', 'total_edits', 'total_deletes'));
    }
}
