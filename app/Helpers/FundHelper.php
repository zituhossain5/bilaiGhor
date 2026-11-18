<?php

namespace App\Helpers;

use App\Models\FundTransaction;
use App\Models\Order;
use App\Services\AccountingSummaryService;
use Illuminate\Support\Facades\DB;
use LogicException;

class FundHelper
{
    public static function balance()
    {
        return AccountingSummaryService::fundBalance();
    }

    /** Credit an order once, even if more than one status-update path handles it. */
    public static function creditSale(Order $order, string $note, ?int $createdBy = null): FundTransaction
    {
        return DB::transaction(function () use ($order, $note, $createdBy) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            if (
                ($lockedOrder->order_purpose ?: 'sale') !== \App\Support\OrderPurpose::SALE ||
                (int) $lockedOrder->order_status !== \App\Services\InventoryService::COMPLETE_STATUS ||
                strtolower((string) $lockedOrder->payment_status) !== 'paid'
            ) {
                throw new LogicException('Only a completed, paid normal sale can enter the fund as sales revenue.');
            }

            $existing = FundTransaction::includedInAccounting()
                ->where('direction', 'in')
                ->where('source', 'sale')
                ->where('source_id', $lockedOrder->id)
                ->first();

            return $existing ?: FundTransaction::create([
                'direction' => 'in',
                'source' => 'sale',
                'source_id' => $lockedOrder->id,
                'amount' => $lockedOrder->amount,
                'note' => $note,
                'created_by' => $createdBy,
            ]);
        });
    }
}
