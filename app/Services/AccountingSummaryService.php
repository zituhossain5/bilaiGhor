<?php

namespace App\Services;

use App\Models\FundTransaction;
use App\Models\InventoryStock;
use App\Models\Purchase;

final class AccountingSummaryService
{
    public static function snapshot(): array
    {
        $fund = FundTransaction::query()
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'in' THEN amount ELSE 0 END), 0) as total_in")
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'out' THEN amount ELSE 0 END), 0) as total_out")
            ->first();

        $inventory = InventoryStock::query()
            ->join('products', 'products.id', '=', 'inventory_stocks.product_id')
            ->selectRaw('COALESCE(SUM(inventory_stocks.on_hand * COALESCE(products.purchase_price, 0)), 0) as on_hand_cost')
            ->selectRaw('COALESCE(SUM(inventory_stocks.reserved * COALESCE(products.purchase_price, 0)), 0) as reserved_cost')
            ->selectRaw('COALESCE(SUM(CASE WHEN inventory_stocks.on_hand > inventory_stocks.reserved THEN (inventory_stocks.on_hand - inventory_stocks.reserved) * COALESCE(products.purchase_price, 0) ELSE 0 END), 0) as available_cost')
            ->selectRaw('COALESCE(SUM(CASE WHEN inventory_stocks.on_hand > inventory_stocks.reserved THEN (inventory_stocks.on_hand - inventory_stocks.reserved) * COALESCE(products.new_price, products.old_price, 0) ELSE 0 END), 0) as available_retail')
            ->first();

        $totalIn = (float) ($fund->total_in ?? 0);
        $totalOut = (float) ($fund->total_out ?? 0);
        $fundBalance = $totalIn - $totalOut;
        $inventoryOnHandCost = (float) ($inventory->on_hand_cost ?? 0);
        $inventoryReservedCost = (float) ($inventory->reserved_cost ?? 0);
        $inventoryAvailableCost = (float) ($inventory->available_cost ?? 0);
        $availableRetailValue = (float) ($inventory->available_retail ?? 0);
        $supplierDue = (float) Purchase::sum('due_amount');

        $duplicateSaleGroups = FundTransaction::query()
            ->where('direction', 'in')
            ->where('source', 'sale')
            ->whereNotNull('source_id')
            ->select('source_id')
            ->groupBy('source_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        return [
            'fund_in' => $totalIn,
            'fund_out' => $totalOut,
            'fund_balance' => $fundBalance,
            'inventory_on_hand_cost' => $inventoryOnHandCost,
            'inventory_reserved_cost' => $inventoryReservedCost,
            'inventory_available_cost' => $inventoryAvailableCost,
            'available_retail_value' => $availableRetailValue,
            'potential_gross_margin' => $availableRetailValue - $inventoryAvailableCost,
            'supplier_due' => $supplierDue,
            'tracked_net_assets' => $fundBalance + $inventoryOnHandCost - $supplierDue,
            'duplicate_sale_groups' => $duplicateSaleGroups,
            'purchases_missing_items' => Purchase::query()->doesntHave('items')->count(),
        ];
    }

    public static function fundBalance(): float
    {
        $totals = FundTransaction::query()
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'in' THEN amount ELSE 0 END), 0) as total_in")
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'out' THEN amount ELSE 0 END), 0) as total_out")
            ->first();

        return (float) ($totals->total_in ?? 0) - (float) ($totals->total_out ?? 0);
    }

    public static function lockedFundBalance(): float
    {
        $transactions = FundTransaction::query()
            ->lockForUpdate()
            ->get(['direction', 'amount']);

        $totalIn = $transactions->where('direction', 'in')->sum('amount');
        $totalOut = $transactions->where('direction', 'out')->sum('amount');

        return (float) $totalIn - (float) $totalOut;
    }
}
