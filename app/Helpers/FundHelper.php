<?php

namespace App\Helpers;

use App\Models\FundTransaction;
use App\Models\Order;
use App\Services\AccountingSummaryService;

class FundHelper
{
    public static function balance()
    {
        return AccountingSummaryService::fundBalance();
    }

    /** Credit an order once, even if more than one status-update path handles it. */
    public static function creditSale(Order $order, string $note, ?int $createdBy = null): FundTransaction
    {
        return FundTransaction::firstOrCreate(
            [
                'direction' => 'in',
                'source' => 'sale',
                'source_id' => $order->id,
            ],
            [
                'amount' => $order->amount,
                'note' => $note,
                'created_by' => $createdBy,
            ]
        );
    }
}
