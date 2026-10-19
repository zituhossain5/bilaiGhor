<?php

namespace App\Support;

use App\Models\Order;

/**
 * Pickup reminder: Steadfast parcels sent from the panel today and whether an admin ticked
 * "Pickup requested" (the pickup itself is requested by hand in the Steadfast portal).
 */
class SteadfastPickup
{
    public static function sentToday(): int
    {
        return Order::where('courier_type', 'steadfast')
            ->where('courier_sent_at', '>=', today())
            ->count();
    }

    public static function doneToday(): bool
    {
        return CourierSettings::get('steadfast_pickup_done_on') === today()->toDateString();
    }

    public static function markDone(bool $done): void
    {
        CourierSettings::set(['steadfast_pickup_done_on' => $done ? today()->toDateString() : null]);
    }

    /**
     * What the reminder shows, or null when it is switched off or nothing was sent today.
     *
     * @return array{count:int, done:bool, portal_url:?string}|null
     */
    public static function reminder(): ?array
    {
        if (!CourierSettings::bool('steadfast_pickup_reminder')) {
            return null;
        }
        $count = self::sentToday();
        if ($count === 0) {
            return null;
        }

        return [
            'count'      => $count,
            'done'       => self::doneToday(),
            'portal_url' => config('courier_tracking.portal_url.steadfast'),
        ];
    }
}
