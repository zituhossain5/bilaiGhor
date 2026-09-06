<?php

namespace App\Services;

use App\Models\FundReconciliation;
use App\Models\FundTransaction;
use App\Models\InventoryStock;
use App\Models\Order;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AccountingSummaryService
{
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
            'owner_funding' => self::sourceTotal($fundSources, 'manual_add', 'in'),
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
