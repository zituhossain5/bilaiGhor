<?php

namespace App\Support;

/**
 * Pathao status slug → order_status id. The same lists courier:check-status always used,
 * shared now with the Pathao webhook so both paths follow one rule.
 */
class PathaoStatus
{
    /** "order.delivery-failed" (webhook event) or "Delivery_Failed" (info API) → "delivery_failed". */
    public static function slug(?string $value): string
    {
        $s = strtolower(trim((string) $value));
        $s = preg_replace('/^order\./', '', $s);

        return trim(str_replace(['-', ' '], '_', $s), '_ ');
    }

    public static function toOrderStatusId(?string $value): ?int
    {
        $slug = self::slug($value);

        // পাঠাও ডেলিভার্ড / পার্শিয়াল / কমপ্লিট → Completed (৬); ক্যানসেল / রিটার্ন টাইপ → Cancelled (১১)
        $delivered = [
            'delivered', 'completed', 'partial_delivered', 'partially_delivered',
            'partial_delivery',
        ];
        $cancel = [
            'cancelled', 'canceled', 'cancellation', 'cancellation_request',
            'returned_to_merchant', 'return_to_merchant', 'returned', 'picked_return',
            'pickup_failed', 'delivery_failed',
        ];

        if (in_array($slug, $delivered, true)) {
            return 6;
        }
        if (in_array($slug, $cancel, true)) {
            return 11;
        }

        return null;
    }
}
