<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Read-only fingerprint of the orders table, used before/after the courier-tracking migrations and backfill
 * to prove no existing order data changed. Covers status, payment status, money and the courier/tracking ids.
 */
class OrderIntegritySnapshot
{
    public static function take(): array
    {
        $fields = "CONCAT_WS('|', id, IFNULL(order_status,''), IFNULL(payment_status,''), IFNULL(amount,''),
            IFNULL(discount,''), IFNULL(shipping_charge,''), IFNULL(courier_type,''), IFNULL(consignment_id,''),
            IFNULL(courier_tracking_id,''), IFNULL(courier_tracking_code,''))";

        $checksum = DB::table('orders')
            ->selectRaw("COUNT(*) AS n, SUM(CRC32({$fields})) AS s, BIT_XOR(CRC32({$fields})) AS x")
            ->first();

        return [
            'orders'             => (int) $checksum->n,
            'per_order_status'   => DB::table('orders')->select('order_status', DB::raw('COUNT(*) AS n'))
                ->groupBy('order_status')->orderBy('order_status')->pluck('n', 'order_status')->map(fn ($n) => (int) $n)->all(),
            'per_payment_status' => DB::table('orders')->select('payment_status', DB::raw('COUNT(*) AS n'))
                ->groupBy('payment_status')->orderBy('payment_status')->pluck('n', 'payment_status')->map(fn ($n) => (int) $n)->all(),
            'with_courier_id'    => DB::table('orders')->where(fn ($q) => $q->whereNotNull('courier_tracking_id')
                ->orWhereNotNull('consignment_id'))->count(),
            'sum_amount'         => (int) DB::table('orders')->sum('amount'),
            'checksum'           => sprintf('%s-%s', $checksum->s ?? 0, $checksum->x ?? 0),
        ];
    }
}
