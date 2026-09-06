<?php

namespace App\Console\Commands;

use App\Models\AccountingCleanupRun;
use App\Models\Expense;
use App\Models\FundTransaction;
use App\Models\Order;
use App\Models\Refund;
use App\Services\AccountingSummaryService;
use App\Services\InventoryService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class CleanupLegacyAccounting extends Command
{
    protected $signature = 'accounting:cleanup-legacy
        {--dry-run : Inspect candidates without changing any records}
        {--cutoff= : Exclude historical expenses created before this date/time (defaults to today at 00:00)}
        {--keep-expenses : Keep historical expenses instead of starting expense tracking fresh}
        {--exclude-withdrawal=* : Explicit fund transaction ID(s) for verified fake/manual withdrawals}
        {--exclude-manual-add=* : Explicit fund transaction ID(s) for verified fake owner-funding entries}
        {--delete : Permanently delete cleanup candidates after writing the JSON snapshot}
        {--force : Execute without the interactive production confirmation}';

    protected $description = 'Audit and exclude or delete verified legacy accounting entries';

    public function handle(): int
    {
        if (! $this->schemaIsReady()) {
            $this->error('Run php artisan migrate first. Accounting audit columns are not installed.');

            return self::FAILURE;
        }

        try {
            $cutoff = $this->cutoff();
        } catch (Throwable $exception) {
            $this->error('Invalid --cutoff value: '.$exception->getMessage());

            return self::FAILURE;
        }

        try {
            $plan = $this->buildPlan($cutoff);
        } catch (Throwable $exception) {
            $this->error('Cleanup plan could not be built: '.$exception->getMessage());

            return self::FAILURE;
        }
        $this->renderPlan($plan, $cutoff);

        if ($this->option('dry-run')) {
            $this->info('Dry run complete. No database records were changed.');

            return self::SUCCESS;
        }

        $deleteMode = (bool) $this->option('delete');
        $confirmation = $deleteMode
            ? 'Create a JSON snapshot and permanently DELETE exactly the candidates listed above?'
            : 'Create a JSON snapshot and exclude exactly the candidates listed above from accounting?';

        if (! $this->option('force') && ! $this->confirm($confirmation, false)) {
            $this->warn('Cleanup cancelled. No database records were changed.');

            return self::SUCCESS;
        }

        if ($plan['fund_candidates'] === [] && $plan['expense_candidates'] === [] && $plan['fund_adjustments'] === []) {
            $this->info('Nothing needs cleanup.');

            return self::SUCCESS;
        }

        $runId = (string) Str::uuid();
        $snapshotPath = 'accounting-cleanup/'.now()->format('Ymd-His').'-'.$runId.'.json';
        $snapshot = [
            'run_id' => $runId,
            'generated_at' => now()->toIso8601String(),
            'cutoff_at' => $cutoff->toIso8601String(),
            'database' => DB::connection()->getDatabaseName(),
            'options' => $this->snapshotOptions(),
            'plan' => $plan,
            'balance_before' => AccountingSummaryService::fundTotals(),
        ];

        if (! Storage::disk('local')->put(
            $snapshotPath,
            json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        )) {
            $this->error('Could not write the accounting snapshot. Cleanup was not started.');

            return self::FAILURE;
        }

        try {
            DB::transaction(function () use ($plan, $runId, $snapshotPath, $cutoff, $deleteMode) {
                if ($deleteMode) {
                    foreach ($plan['expense_candidates'] as $candidate) {
                        Expense::query()
                            ->whereKey($candidate['id'])
                            ->whereNull('excluded_from_accounting_at')
                            ->delete();
                    }

                    foreach ($plan['fund_candidates'] as $candidate) {
                        FundTransaction::query()
                            ->whereKey($candidate['id'])
                            ->whereNull('excluded_from_accounting_at')
                            ->delete();
                    }
                } else {
                    foreach ($plan['fund_candidates'] as $candidate) {
                        FundTransaction::query()
                            ->whereKey($candidate['id'])
                            ->whereNull('excluded_from_accounting_at')
                            ->update([
                                'excluded_from_accounting_at' => now(),
                                'accounting_exclusion_reason' => Str::limit($candidate['reason'], 255, ''),
                                'accounting_cleanup_run_id' => $runId,
                                'updated_at' => now(),
                            ]);
                    }

                    foreach ($plan['expense_candidates'] as $candidate) {
                        Expense::query()
                            ->whereKey($candidate['id'])
                            ->whereNull('excluded_from_accounting_at')
                            ->update([
                                'excluded_from_accounting_at' => now(),
                                'accounting_exclusion_reason' => Str::limit($candidate['reason'], 255, ''),
                                'accounting_cleanup_run_id' => $runId,
                                'updated_at' => now(),
                            ]);
                    }
                }

                foreach ($plan['fund_adjustments'] as $adjustment) {
                    FundTransaction::query()
                        ->whereKey($adjustment['id'])
                        ->whereNull('excluded_from_accounting_at')
                        ->where('amount', $adjustment['old_amount'])
                        ->update([
                            'amount' => $adjustment['new_amount'],
                            'accounting_cleanup_run_id' => $runId,
                            'updated_at' => now(),
                        ]);
                }

                AccountingCleanupRun::create([
                    'id' => $runId,
                    'cutoff_at' => $cutoff,
                    'snapshot_path' => $snapshotPath,
                    'options' => $this->snapshotOptions(),
                    'summary' => [
                        'cleanup_mode' => $deleteMode ? 'delete' : 'exclude',
                        'fund_entries_excluded' => $deleteMode ? 0 : count($plan['fund_candidates']),
                        'fund_entries_deleted' => $deleteMode ? count($plan['fund_candidates']) : 0,
                        'fund_amount_removed' => array_sum(array_column($plan['fund_candidates'], 'amount')),
                        'expenses_excluded' => $deleteMode ? 0 : count($plan['expense_candidates']),
                        'expenses_deleted' => $deleteMode ? count($plan['expense_candidates']) : 0,
                        'expense_amount_removed' => array_sum(array_column($plan['expense_candidates'], 'amount')),
                        'fund_entries_corrected' => count($plan['fund_adjustments']),
                        'fund_net_correction' => array_sum(array_column($plan['fund_adjustments'], 'balance_effect')),
                    ],
                    'executed_at' => now(),
                ]);
            });
        } catch (Throwable $exception) {
            Log::error('Accounting legacy cleanup failed', [
                'run_id' => $runId,
                'snapshot_path' => $snapshotPath,
                'error' => $exception->getMessage(),
            ]);
            $this->error('Cleanup failed and the database transaction was rolled back: '.$exception->getMessage());

            return self::FAILURE;
        }

        $totals = AccountingSummaryService::fundTotals();
        Log::warning('Accounting legacy cleanup completed', [
            'run_id' => $runId,
            'snapshot_path' => $snapshotPath,
            'summary' => $plan['summary'],
            'balance_after' => $totals,
        ]);

        $this->info($deleteMode
            ? 'Cleanup completed. Candidate rows were permanently deleted after the snapshot was written.'
            : 'Cleanup completed. Original rows were retained and marked as excluded.'
        );
        $this->line('Run ID: '.$runId);
        $this->line('Snapshot: storage/app/'.$snapshotPath);
        $this->line('Active fund balance: BDT '.number_format($totals['balance'], 2));

        return self::SUCCESS;
    }

    private function buildPlan(CarbonImmutable $cutoff): array
    {
        $fundCandidates = [];
        $expenseCandidates = [];
        $fundAdjustments = [];

        $fundRows = FundTransaction::query()
            ->whereNull('excluded_from_accounting_at')
            ->orderBy('id')
            ->get();

        foreach ($fundRows->whereIn('source', FundTransaction::LEGACY_BUSINESS_SOURCES) as $transaction) {
            $this->addFundCandidate($fundCandidates, $transaction, 'Vendor/reseller source is not part of Bilai Ghor accounting');
        }

        $sales = $fundRows
            ->where('source', 'sale')
            ->where('direction', 'in');
        $orders = Order::query()
            ->whereIn('id', $sales->pluck('source_id')->filter()->unique())
            ->get(['id', 'invoice_id', 'amount', 'order_status', 'payment_status'])
            ->keyBy('id');
        $validSales = collect();

        foreach ($sales as $sale) {
            $order = $orders->get($sale->source_id);
            $context = $order ? [
                'order_id' => $order->id,
                'invoice_id' => $order->invoice_id,
                'order_amount' => (float) $order->amount,
                'order_status' => $order->order_status,
                'payment_status' => $order->payment_status,
            ] : ['order_id' => null];

            if (! $order) {
                $this->addFundCandidate($fundCandidates, $sale, 'Sale credit has no matching order', $context);
            } elseif ((int) $order->order_status !== InventoryService::COMPLETE_STATUS
                || strtolower((string) $order->payment_status) !== 'paid') {
                $this->addFundCandidate($fundCandidates, $sale, 'Sale credit is not linked to a complete and paid order', $context);
            } elseif (abs((float) $sale->amount - (float) $order->amount) >= 0.01) {
                $this->addFundCandidate($fundCandidates, $sale, 'Sale credit amount does not match the order amount', $context);
            } else {
                $validSales->push($sale);
            }
        }

        foreach ($validSales->groupBy('source_id') as $orderSales) {
            foreach ($orderSales->sortBy('id')->skip(1) as $duplicate) {
                $this->addFundCandidate($fundCandidates, $duplicate, 'Duplicate sale credit; earliest valid credit is retained');
            }
        }

        $refundTransactions = $fundRows
            ->where('source', 'refund')
            ->where('direction', 'out');
        $refunds = Refund::query()
            ->whereIn('id', $refundTransactions->pluck('source_id')->filter()->unique())
            ->get(['id', 'refund_id', 'order_id', 'status', 'amount', 'shipping_charge', 'processed_at'])
            ->keyBy('id');
        $invalidRefundSourceIds = [];

        foreach ($refundTransactions as $transaction) {
            $refund = $refunds->get($transaction->source_id);
            $context = $refund ? [
                'refund_id' => $refund->refund_id,
                'order_id' => $refund->order_id,
                'refund_status' => $refund->status,
                'processed_at' => optional($refund->processed_at)->toIso8601String(),
            ] : ['refund_id' => null];

            if (! $refund) {
                $invalidRefundSourceIds[] = $transaction->source_id;
                $this->addFundCandidate($fundCandidates, $transaction, 'Refund transaction has no matching refund record', $context);
            } elseif ($refund->status !== 'processed') {
                $invalidRefundSourceIds[] = $transaction->source_id;
                $this->addFundCandidate($fundCandidates, $transaction, 'Refund has not been marked as genuinely processed', $context);
            }
        }

        foreach ($fundRows->where('source', 'refund_reversal') as $reversal) {
            if (! $refunds->has($reversal->source_id) || in_array($reversal->source_id, $invalidRefundSourceIds, true)) {
                $this->addFundCandidate($fundCandidates, $reversal, 'Reversal belongs to an invalid/orphan refund transaction');
            }
        }

        if (! $this->option('keep-expenses')) {
            $expenses = Expense::query()
                ->includedInAccounting()
                ->where(function ($query) use ($cutoff) {
                    $query->where('created_at', '<', $cutoff)
                        ->orWhere(function ($nested) use ($cutoff) {
                            $nested->whereNull('created_at')
                                ->whereDate('expense_date', '<', $cutoff->toDateString());
                        });
                })
                ->orderBy('id')
                ->get();

            foreach ($expenses as $expense) {
                $expenseCandidates[$expense->id] = [
                    'id' => (int) $expense->id,
                    'title' => $expense->title,
                    'amount' => (float) $expense->amount,
                    'expense_date' => $expense->expense_date
                        ? CarbonImmutable::parse($expense->expense_date)->format('Y-m-d')
                        : null,
                    'created_at' => optional($expense->created_at)->toIso8601String(),
                    'fund_transaction_id' => $expense->fund_transaction_id,
                    'reason' => 'Historical expense reset requested; created before cleanup cutoff',
                ];

                foreach ($fundRows->filter(fn ($fund) => ($fund->source === 'expense' && (int) $fund->source_id === (int) $expense->id)
                    || (int) $fund->id === (int) $expense->fund_transaction_id
                ) as $fund) {
                    $this->addFundCandidate($fundCandidates, $fund, 'Fund entry belongs to an excluded historical expense');
                }
            }
        }

        $this->addExplicitCandidates(
            $fundCandidates,
            $fundRows,
            'withdraw',
            (array) $this->option('exclude-withdrawal'),
            'Explicitly confirmed legacy/manual withdrawal'
        );
        $this->addExplicitCandidates(
            $fundCandidates,
            $fundRows,
            'manual_add',
            (array) $this->option('exclude-manual-add'),
            'Explicitly confirmed legacy/manual owner-funding entry'
        );

        $supplierPayments = DB::table('supplier_payments as payments')
            ->join('fund_transactions as fund', 'fund.id', '=', 'payments.fund_transaction_id')
            ->whereNull('fund.excluded_from_accounting_at')
            ->where('fund.source', 'supplier_payment')
            ->where('fund.direction', 'out')
            ->whereColumn('fund.amount', '!=', 'payments.amount')
            ->select([
                'fund.id as fund_id',
                'fund.amount as fund_amount',
                'payments.id as payment_id',
                'payments.purchase_id',
                'payments.amount as payment_amount',
            ])
            ->get();

        foreach ($supplierPayments as $payment) {
            $fundAdjustments[] = [
                'id' => (int) $payment->fund_id,
                'source' => 'supplier_payment',
                'source_id' => (int) $payment->payment_id,
                'purchase_id' => (int) $payment->purchase_id,
                'old_amount' => (float) $payment->fund_amount,
                'new_amount' => (float) $payment->payment_amount,
                'balance_effect' => (float) $payment->fund_amount - (float) $payment->payment_amount,
                'reason' => 'Fund debit corrected to the authoritative supplier payment amount',
            ];
        }

        $fundCandidates = array_values($fundCandidates);
        usort($fundCandidates, fn ($a, $b) => $a['id'] <=> $b['id']);

        $reviewWithdrawals = $fundRows
            ->where('source', 'withdraw')
            ->where('created_at', '<', $cutoff)
            ->reject(fn ($fund) => collect($fundCandidates)->contains('id', $fund->id))
            ->map(fn ($fund) => $this->fundRecord($fund, 'Needs business evidence; not automatically excluded'))
            ->values()
            ->all();
        $reviewOwnerFunding = $fundRows
            ->where('source', 'manual_add')
            ->where('created_at', '<', $cutoff)
            ->reject(fn ($fund) => collect($fundCandidates)->contains('id', $fund->id))
            ->map(fn ($fund) => $this->fundRecord($fund, 'Needs bank/cash evidence; not automatically excluded'))
            ->values()
            ->all();

        return [
            'fund_candidates' => $fundCandidates,
            'expense_candidates' => array_values($expenseCandidates),
            'fund_adjustments' => $fundAdjustments,
            'review_only_withdrawals' => $reviewWithdrawals,
            'review_only_owner_funding' => $reviewOwnerFunding,
            'summary' => [
                'fund_entries' => count($fundCandidates),
                'fund_amount' => array_sum(array_column($fundCandidates, 'amount')),
                'expenses' => count($expenseCandidates),
                'expense_amount' => array_sum(array_column($expenseCandidates, 'amount')),
                'fund_adjustments' => count($fundAdjustments),
                'fund_net_correction' => array_sum(array_column($fundAdjustments, 'balance_effect')),
                'review_withdrawals' => count($reviewWithdrawals),
                'review_owner_funding' => count($reviewOwnerFunding),
            ],
        ];
    }

    private function addExplicitCandidates(
        array &$candidates,
        $fundRows,
        string $expectedSource,
        array $ids,
        string $reason
    ): void {
        foreach (array_unique(array_filter(array_map('intval', $ids))) as $id) {
            $transaction = $fundRows->firstWhere('id', $id);
            if (! $transaction) {
                throw new \InvalidArgumentException("Fund transaction {$id} does not exist or is already excluded.");
            }
            if ($transaction->source !== $expectedSource) {
                throw new \InvalidArgumentException(
                    "Fund transaction {$id} is {$transaction->source}, not {$expectedSource}; nothing was changed."
                );
            }

            $this->addFundCandidate($candidates, $transaction, $reason);
        }
    }

    private function addFundCandidate(array &$candidates, FundTransaction $transaction, string $reason, array $context = []): void
    {
        if (isset($candidates[$transaction->id])) {
            return;
        }

        $candidates[$transaction->id] = $this->fundRecord($transaction, $reason, $context);
    }

    private function fundRecord(FundTransaction $transaction, string $reason, array $context = []): array
    {
        return [
            'id' => (int) $transaction->id,
            'source' => $transaction->source,
            'source_id' => $transaction->source_id,
            'direction' => $transaction->direction,
            'amount' => (float) $transaction->amount,
            'note' => $transaction->note,
            'created_at' => optional($transaction->created_at)->toIso8601String(),
            'reason' => $reason,
            'context' => $context,
        ];
    }

    private function renderPlan(array $plan, CarbonImmutable $cutoff): void
    {
        $this->newLine();
        $this->info('Accounting cleanup audit');
        $this->line('Database: '.DB::connection()->getDatabaseName());
        $this->line('Historical expense cutoff: '.$cutoff->toDateTimeString());
        $this->line('No products, orders, customers, payments, purchases, or inventory records are candidates.');

        $this->newLine();
        $this->warn('Fund entries proposed for reversible exclusion');
        $this->table(
            ['ID', 'Created', 'Source', 'Source ID', 'Dir', 'Amount', 'Reason'],
            array_map(fn ($row) => [
                $row['id'],
                $row['created_at'] ?: '-',
                $row['source'],
                $row['source_id'] ?? '-',
                strtoupper($row['direction']),
                number_format($row['amount'], 2),
                $row['reason'],
            ], $plan['fund_candidates'])
        );

        if ($plan['expense_candidates'] !== []) {
            $this->warn('Expense rows proposed for reversible exclusion');
            $this->table(
                ['ID', 'Expense date', 'Created', 'Title', 'Amount', 'Fund ID'],
                array_map(fn ($row) => [
                    $row['id'], $row['expense_date'], $row['created_at'] ?: '-', $row['title'],
                    number_format($row['amount'], 2), $row['fund_transaction_id'] ?? '-',
                ], $plan['expense_candidates'])
            );
        }

        if ($plan['fund_adjustments'] !== []) {
            $this->warn('Verified linked fund entries proposed for correction');
            $this->table(
                ['Fund ID', 'Source', 'Source ID', 'Purchase ID', 'Old amount', 'Correct amount', 'Balance effect'],
                array_map(fn ($row) => [
                    $row['id'], $row['source'], $row['source_id'], $row['purchase_id'],
                    number_format($row['old_amount'], 2), number_format($row['new_amount'], 2),
                    number_format($row['balance_effect'], 2),
                ], $plan['fund_adjustments'])
            );
        }

        $this->renderReviewTable('Manual withdrawals requiring evidence (not excluded)', $plan['review_only_withdrawals']);
        $this->renderReviewTable('Owner funding requiring cash/bank evidence (not excluded)', $plan['review_only_owner_funding']);
    }

    private function renderReviewTable(string $title, array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $this->newLine();
        $this->comment($title);
        $this->table(
            ['ID', 'Created', 'Source', 'Amount', 'Note'],
            array_map(fn ($row) => [
                $row['id'], $row['created_at'] ?: '-', $row['source'], number_format($row['amount'], 2), $row['note'],
            ], $rows)
        );
    }

    private function cutoff(): CarbonImmutable
    {
        $value = $this->option('cutoff');

        return $value
            ? CarbonImmutable::parse($value, config('app.timezone'))
            : CarbonImmutable::today(config('app.timezone'));
    }

    private function snapshotOptions(): array
    {
        return [
            'keep_expenses' => (bool) $this->option('keep-expenses'),
            'exclude_withdrawal' => array_values((array) $this->option('exclude-withdrawal')),
            'exclude_manual_add' => array_values((array) $this->option('exclude-manual-add')),
            'delete' => (bool) $this->option('delete'),
        ];
    }

    private function schemaIsReady(): bool
    {
        return Schema::hasTable('accounting_cleanup_runs')
            && Schema::hasColumn('fund_transactions', 'excluded_from_accounting_at')
            && Schema::hasColumn('expenses', 'excluded_from_accounting_at');
    }
}
