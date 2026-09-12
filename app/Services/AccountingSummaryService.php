<?php

namespace App\Services;

use App\Models\FundReconciliation;
use App\Models\FundTransaction;
use App\Models\InventoryStock;
use App\Models\Order;
use App\Models\Expense;
use App\Models\Purchase;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AccountingSummaryService
{
    public static function period(
        string $mode = 'current_month',
        ?int $year = null,
        ?int $month = null,
        ?string $fromDate = null,
        ?string $toDate = null
    ): array {
        $now = Carbon::now();
        $year ??= $now->year;
        $month ??= $now->month;

        return match ($mode) {
            'all' => ['from' => null, 'to' => null, 'label' => 'Full history', 'mode' => 'all'],
            'previous_month' => (function () use ($now) {
                $date = $now->copy()->subMonthNoOverflow();
                return [
                    'from' => $date->copy()->startOfMonth()->startOfDay(),
                    'to' => $date->copy()->endOfMonth()->endOfDay(),
                    'label' => $date->format('F Y'),
                    'mode' => 'previous_month',
                ];
            })(),
            'month' => (function () use ($year, $month) {
                $date = Carbon::create($year, $month, 1);
                return [
                    'from' => $date->copy()->startOfMonth()->startOfDay(),
                    'to' => $date->copy()->endOfMonth()->endOfDay(),
                    'label' => $date->format('F Y'),
                    'mode' => 'month',
                ];
            })(),
            'year' => [
                'from' => Carbon::create($year, 1, 1)->startOfDay(),
                'to' => Carbon::create($year, 12, 31)->endOfDay(),
                'label' => (string) $year,
                'mode' => 'year',
            ],
            'custom' => [
                'from' => Carbon::parse($fromDate)->startOfDay(),
                'to' => Carbon::parse($toDate)->endOfDay(),
                'label' => Carbon::parse($fromDate)->format('d/m/Y') . ' - ' . Carbon::parse($toDate)->format('d/m/Y'),
                'mode' => 'custom',
            ],
            default => [
                'from' => $now->copy()->startOfMonth()->startOfDay(),
                'to' => $now->copy()->endOfMonth()->endOfDay(),
                'label' => $now->format('F Y'),
                'mode' => 'current_month',
            ],
        };
    }

    public static function businessSummary(?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $saleCredits = FundTransaction::query()
            ->includedInAccounting()
            ->where('direction', 'in')
            ->where('source', 'sale')
            ->whereNotNull('source_id')
            ->selectRaw('source_id, MIN(created_at) as recognized_at')
            ->groupBy('source_id');

        $ordersQuery = Order::query()
            ->leftJoinSub($saleCredits, 'sale_credits', function ($join) {
                $join->on('sale_credits.source_id', '=', 'orders.id');
            })
            ->where('orders.order_status', InventoryService::COMPLETE_STATUS)
            ->whereRaw('LOWER(COALESCE(orders.payment_status, ?)) = ?', ['', 'paid']);

        self::applyDateRange(
            $ordersQuery,
            'COALESCE(sale_credits.recognized_at, orders.updated_at)',
            $from,
            $to,
            rawColumn: true
        );

        $orders = $ordersQuery->get(['orders.id', 'orders.amount']);
        $orderIds = $orders->pluck('id');

        $costs = DB::table('order_details as details')
            ->leftJoin('products', 'products.id', '=', 'details.product_id')
            ->whereIn('details.order_id', $orderIds)
            ->selectRaw('COALESCE(SUM(COALESCE(details.purchase_price, products.purchase_price, 0) * details.qty), 0) as cogs')
            ->selectRaw('SUM(CASE WHEN details.purchase_price IS NULL AND products.purchase_price IS NOT NULL THEN 1 ELSE 0 END) as fallback_lines')
            ->selectRaw('SUM(CASE WHEN COALESCE(details.purchase_price, products.purchase_price, 0) = 0 THEN 1 ELSE 0 END) as zero_cost_lines')
            ->first();

        $expenseQuery = Expense::query()->includedInAccounting();
        if ($from && $to) {
            $expenseQuery->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()]);
        }
        $expense = $expenseQuery
            ->selectRaw('COUNT(*) as expense_count, COALESCE(SUM(amount), 0) as total')
            ->first();

        $investmentQuery = FundTransaction::query()
            ->includedInAccounting()
            ->where('direction', 'in')
            ->whereIn('source', ['investment', 'manual_add']);
        self::applyDateRange(
            $investmentQuery,
            'COALESCE(transaction_date, DATE(created_at))',
            $from,
            $to,
            rawColumn: true
        );

        $periodInvestment = (float) $investmentQuery->sum('amount');
        $periodFundQuery = FundTransaction::query()->includedInAccounting();
        self::applyDateRange(
            $periodFundQuery,
            'COALESCE(transaction_date, DATE(created_at))',
            $from,
            $to,
            rawColumn: true
        );
        $periodFund = $periodFundQuery
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'in' THEN amount ELSE 0 END), 0) as total_in")
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'out' THEN amount ELSE 0 END), 0) as total_out")
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'out' AND source = 'withdraw' THEN amount ELSE 0 END), 0) as withdrawals")
            ->selectRaw("SUM(CASE WHEN direction = 'out' AND source = 'withdraw' THEN 1 ELSE 0 END) as withdrawal_count")
            ->first();
        $investmentTotals = FundTransaction::query()
            ->includedInAccounting()
            ->where('direction', 'in')
            ->whereIn('source', ['investment', 'manual_add'])
            ->selectRaw("COALESCE(SUM(CASE WHEN investment_type = 'initial' OR (investment_type IS NULL AND source = 'manual_add') THEN amount ELSE 0 END), 0) as initial_total")
            ->selectRaw("COALESCE(SUM(CASE WHEN investment_type = 'additional' THEN amount ELSE 0 END), 0) as additional_total")
            ->selectRaw('COALESCE(SUM(amount), 0) as total')
            ->first();

        $revenue = (float) $orders->sum('amount');
        $cogs = (float) ($costs->cogs ?? 0);
        $expenses = (float) ($expense->total ?? 0);
        $grossProfit = $revenue - $cogs;

        return [
            'total_investment' => (float) ($investmentTotals->total ?? 0),
            'initial_investment' => (float) ($investmentTotals->initial_total ?? 0),
            'additional_investment' => (float) ($investmentTotals->additional_total ?? 0),
            'period_investment' => $periodInvestment,
            'period_fund_in' => (float) ($periodFund->total_in ?? 0),
            'period_fund_out' => (float) ($periodFund->total_out ?? 0),
            'period_fund_change' => (float) ($periodFund->total_in ?? 0) - (float) ($periodFund->total_out ?? 0),
            'withdrawals' => (float) ($periodFund->withdrawals ?? 0),
            'withdrawal_count' => (int) ($periodFund->withdrawal_count ?? 0),
            'sales_orders' => $orders->count(),
            'sales_revenue' => $revenue,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'expense_count' => (int) ($expense->expense_count ?? 0),
            'expenses' => $expenses,
            'net_profit' => $grossProfit - $expenses,
            'cost_fallback_lines' => (int) ($costs->fallback_lines ?? 0),
            'zero_cost_lines' => (int) ($costs->zero_cost_lines ?? 0),
        ];
    }

    public static function applyDateRange($query, string $column, ?CarbonInterface $from, ?CarbonInterface $to, bool $rawColumn = false): void
    {
        if (!$from || !$to) {
            return;
        }

        if ($rawColumn) {
            $query->whereBetween(DB::raw($column), [$from, $to]);
            return;
        }

        $query->whereBetween($column, [$from, $to]);
    }

    public static function snapshot(): array
    {
        $fund = self::fundTotals();

        $inventory = InventoryStock::query()
            ->join('products', 'products.id', '=', 'inventory_stocks.product_id')
            ->selectRaw('COALESCE(SUM(inventory_stocks.on_hand * COALESCE(products.purchase_price, 0)), 0) as on_hand_cost')
            ->selectRaw('COALESCE(SUM(inventory_stocks.reserved * COALESCE(products.purchase_price, 0)), 0) as reserved_cost')
            ->selectRaw('COALESCE(SUM(CASE WHEN inventory_stocks.on_hand > inventory_stocks.reserved THEN (inventory_stocks.on_hand - inventory_stocks.reserved) * COALESCE(products.purchase_price, 0) ELSE 0 END), 0) as available_cost')
            ->selectRaw('COALESCE(SUM(CASE WHEN inventory_stocks.on_hand > inventory_stocks.reserved THEN (inventory_stocks.on_hand - inventory_stocks.reserved) * COALESCE(products.new_price, products.old_price, 0) ELSE 0 END), 0) as available_retail')
            ->first();

        $totalIn = $fund['in'];
        $totalOut = $fund['out'];
        $fundBalance = $totalIn - $totalOut;
        $inventoryOnHandCost = (float) ($inventory->on_hand_cost ?? 0);
        $inventoryReservedCost = (float) ($inventory->reserved_cost ?? 0);
        $inventoryAvailableCost = (float) ($inventory->available_cost ?? 0);
        $availableRetailValue = (float) ($inventory->available_retail ?? 0);
        $supplierDue = (float) Purchase::sum('due_amount');

        $duplicateSaleGroups = FundTransaction::query()
            ->includedInAccounting()
            ->where('direction', 'in')
            ->where('source', 'sale')
            ->whereNotNull('source_id')
            ->select('source_id')
            ->groupBy('source_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        $fundSources = FundTransaction::query()
            ->includedInAccounting()
            ->select(['source', 'direction'])
            ->selectRaw('SUM(amount) as total')
            ->selectRaw('COUNT(*) as transaction_count')
            ->groupBy('source', 'direction')
            ->orderBy('direction')
            ->orderBy('source')
            ->get()
            ->map(fn (FundTransaction $row) => [
                'source' => $row->source,
                'direction' => $row->direction,
                'total' => (float) $row->total,
                'transaction_count' => (int) $row->transaction_count,
            ])
            ->all();

        $unlinkedSales = FundTransaction::query()
            ->leftJoin('orders', 'orders.id', '=', 'fund_transactions.source_id')
            ->includedInAccounting()
            ->where('fund_transactions.direction', 'in')
            ->where('fund_transactions.source', 'sale')
            ->whereNotNull('fund_transactions.source_id')
            ->whereNull('orders.id')
            ->selectRaw('COUNT(*) as transaction_count, COALESCE(SUM(fund_transactions.amount), 0) as total')
            ->first();

        $invalidLinkedSales = FundTransaction::query()
            ->join('orders', 'orders.id', '=', 'fund_transactions.source_id')
            ->includedInAccounting()
            ->where('fund_transactions.direction', 'in')
            ->where('fund_transactions.source', 'sale')
            ->where(function ($query) {
                $query->where('orders.order_status', '!=', InventoryService::COMPLETE_STATUS)
                    ->orWhereRaw('LOWER(COALESCE(orders.payment_status, ?)) != ?', ['', 'paid'])
                    ->orWhereColumn('fund_transactions.amount', '!=', 'orders.amount');
            })
            ->count();

        $authoritativeOrderSales = Order::query()
            ->where('order_status', InventoryService::COMPLETE_STATUS)
            ->whereRaw('LOWER(COALESCE(payment_status, ?)) = ?', ['', 'paid'])
            ->selectRaw('COUNT(*) as order_count, COALESCE(SUM(amount), 0) as total')
            ->first();

        $missingSaleCredits = Order::query()
            ->where('order_status', InventoryService::COMPLETE_STATUS)
            ->whereRaw('LOWER(COALESCE(payment_status, ?)) = ?', ['', 'paid'])
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('fund_transactions')
                    ->whereNull('fund_transactions.excluded_from_accounting_at')
                    ->where('fund_transactions.direction', 'in')
                    ->where('fund_transactions.source', 'sale')
                    ->whereColumn('fund_transactions.source_id', 'orders.id')
                    ->whereColumn('fund_transactions.amount', 'orders.amount');
            })
            ->selectRaw('COUNT(*) as order_count, COALESCE(SUM(amount), 0) as total')
            ->first();

        $orphanRefundTransactions = FundTransaction::query()
            ->leftJoin('refunds', 'refunds.id', '=', 'fund_transactions.source_id')
            ->includedInAccounting()
            ->where('fund_transactions.direction', 'out')
            ->where('fund_transactions.source', 'refund')
            ->where(function ($query) {
                $query->whereNull('refunds.id')
                    ->orWhere('refunds.status', '!=', 'processed');
            })
            ->count();

        $expenseFundMismatches = DB::table('expenses as expenses')
            ->leftJoin('fund_transactions as fund', 'fund.id', '=', 'expenses.fund_transaction_id')
            ->whereNull('expenses.excluded_from_accounting_at')
            ->where(function ($query) {
                $query->whereNull('fund.id')
                    ->orWhereNotNull('fund.excluded_from_accounting_at')
                    ->orWhereIn('fund.source', FundTransaction::LEGACY_BUSINESS_SOURCES)
                    ->orWhere('fund.direction', '!=', 'out')
                    ->orWhere('fund.source', '!=', 'expense')
                    ->orWhereColumn('fund.amount', '!=', 'expenses.amount');
            })
            ->count();

        $supplierPaymentFundMismatches = DB::table('supplier_payments as payments')
            ->leftJoin('fund_transactions as fund', 'fund.id', '=', 'payments.fund_transaction_id')
            ->where(function ($query) {
                $query->whereNull('fund.id')
                    ->orWhereNotNull('fund.excluded_from_accounting_at')
                    ->orWhereIn('fund.source', FundTransaction::LEGACY_BUSINESS_SOURCES)
                    ->orWhere('fund.direction', '!=', 'out')
                    ->orWhere('fund.source', '!=', 'supplier_payment')
                    ->orWhereColumn('fund.amount', '!=', 'payments.amount');
            })
            ->count();

        return [
            'fund_in' => $totalIn,
            'fund_out' => $totalOut,
            'fund_balance' => $fundBalance,
            'owner_funding' => self::sourceTotal($fundSources, 'manual_add', 'in')
                + self::sourceTotal($fundSources, 'investment', 'in'),
            'sales_inflow' => self::sourceTotal($fundSources, 'sale', 'in'),
            'reconciliation_net' => self::sourceTotal($fundSources, 'reconciliation', 'in')
                - self::sourceTotal($fundSources, 'reconciliation', 'out'),
            'fund_sources' => $fundSources,
            'inventory_on_hand_cost' => $inventoryOnHandCost,
            'inventory_reserved_cost' => $inventoryReservedCost,
            'inventory_available_cost' => $inventoryAvailableCost,
            'available_retail_value' => $availableRetailValue,
            'potential_gross_margin' => $availableRetailValue - $inventoryAvailableCost,
            'supplier_due' => $supplierDue,
            'tracked_net_assets' => $fundBalance + $inventoryOnHandCost - $supplierDue,
            'duplicate_sale_groups' => $duplicateSaleGroups,
            'authoritative_order_sales_count' => (int) ($authoritativeOrderSales->order_count ?? 0),
            'authoritative_order_sales_total' => (float) ($authoritativeOrderSales->total ?? 0),
            'missing_sale_credit_orders' => (int) ($missingSaleCredits->order_count ?? 0),
            'missing_sale_credit_total' => (float) ($missingSaleCredits->total ?? 0),
            'unlinked_sale_transactions' => (int) ($unlinkedSales->transaction_count ?? 0),
            'unlinked_sale_total' => (float) ($unlinkedSales->total ?? 0),
            'invalid_linked_sales' => $invalidLinkedSales,
            'orphan_refund_transactions' => $orphanRefundTransactions,
            'expense_fund_mismatches' => $expenseFundMismatches,
            'supplier_payment_fund_mismatches' => $supplierPaymentFundMismatches,
            'purchases_missing_items' => Purchase::query()->doesntHave('items')->count(),
            'latest_reconciliation' => FundReconciliation::query()
                ->with('reconciledBy:id,name')
                ->latest('id')
                ->first(),
        ];
    }

    public static function fundBalance(): float
    {
        return self::fundTotals()['balance'];
    }

    public static function fundTotals(): array
    {
        $totals = FundTransaction::query()
            ->includedInAccounting()
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'in' THEN amount ELSE 0 END), 0) as total_in")
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'out' THEN amount ELSE 0 END), 0) as total_out")
            ->first();

        $totalIn = (float) ($totals->total_in ?? 0);
        $totalOut = (float) ($totals->total_out ?? 0);

        return [
            'in' => $totalIn,
            'out' => $totalOut,
            'balance' => $totalIn - $totalOut,
        ];
    }

    public static function lockedFundBalance(): float
    {
        $transactions = FundTransaction::query()
            ->includedInAccounting()
            ->lockForUpdate()
            ->get(['direction', 'amount']);

        $totalIn = $transactions->where('direction', 'in')->sum('amount');
        $totalOut = $transactions->where('direction', 'out')->sum('amount');

        return (float) $totalIn - (float) $totalOut;
    }

    public static function reconcile(array $balances, string $note, ?int $userId): FundReconciliation
    {
        return DB::transaction(function () use ($balances, $note, $userId) {
            $systemBalance = round(self::lockedFundBalance(), 2);
            $actualBalance = round(array_sum($balances), 2);
            $difference = round($actualBalance - $systemBalance, 2);
            $transaction = null;

            if (abs($difference) >= 0.01) {
                $transaction = FundTransaction::create([
                    'direction' => $difference > 0 ? 'in' : 'out',
                    'source' => 'reconciliation',
                    'source_id' => null,
                    'amount' => abs($difference),
                    'note' => Str::limit(
                        sprintf(
                            'Verified balance reconciliation: system %.2f, actual %.2f. %s',
                            $systemBalance,
                            $actualBalance,
                            $note
                        ),
                        255,
                        ''
                    ),
                    'created_by' => $userId,
                ]);
            }

            return FundReconciliation::create([
                'system_balance_before' => $systemBalance,
                'cash_balance' => $balances['cash_balance'],
                'bank_balance' => $balances['bank_balance'],
                'mobile_wallet_balance' => $balances['mobile_wallet_balance'],
                'other_balance' => $balances['other_balance'],
                'actual_balance' => $actualBalance,
                'difference' => $difference,
                'fund_transaction_id' => $transaction?->id,
                'note' => $note,
                'reconciled_by' => $userId,
            ]);
        });
    }

    private static function sourceTotal(array $sources, string $source, string $direction): float
    {
        foreach ($sources as $row) {
            if ($row['source'] === $source && $row['direction'] === $direction) {
                return $row['total'];
            }
        }

        return 0.0;
    }
}
