<?php

namespace App\Services\CourierTracking;

use App\Services\RedXService;
use App\Support\PathaoStatus;
use App\Support\SteadfastWebhookStatus;
use Illuminate\Support\Facades\Log;

/**
 * Raw courier status → normalized display status (config/courier_tracking.php), and
 * raw courier status → order status id using the EXISTING per-courier rules.
 */
class CourierStatusMap
{
    public static function key(?string $raw): string
    {
        $s = strtolower(trim((string) $raw));
        $s = preg_replace('/^order\./', '', $s);

        return trim(str_replace(['-', ' '], '_', $s), '_');
    }

    /** Normalized status, or null when there is no raw status. Unmapped values become "unknown" and are logged. */
    public static function normalize(string $courier, ?string $raw): ?string
    {
        $key = self::key($raw);
        if ($key === '') {
            return null;
        }

        $normalized = config("courier_tracking.map.{$courier}.{$key}");
        if ($normalized) {
            return $normalized;
        }

        Log::warning('Courier tracking: unmapped courier status', ['courier' => $courier, 'raw_status' => $raw]);

        return 'unknown';
    }

    /** Display status suggested by a free-text tracking message (Steadfast tracking_update), or null. */
    public static function fromMessage(string $courier, ?string $message): ?string
    {
        $message = trim((string) $message);
        if ($message === '') {
            return null;
        }

        foreach (config("courier_tracking.message_patterns.{$courier}", []) as $pattern => $status) {
            if (preg_match($pattern, $message)) {
                return $status;
            }
        }

        return null;
    }

    /**
     * Order status (6 Delivered / 11 Cancelled) a raw courier status maps to under the current rules, or null.
     * Only explicit statuses count: free-text messages never move an order.
     */
    public static function orderStatusFor(string $courier, ?string $raw): ?int
    {
        if (blank($raw)) {
            return null;
        }

        static $redx = null;

        return match ($courier) {
            'steadfast' => SteadfastWebhookStatus::toOrderStatusId($raw),
            'redx'      => ($redx ??= new RedXService())->mapStatusToOrderStatus((string) $raw),
            'pathao'    => PathaoStatus::toOrderStatusId($raw),
            default     => null,
        };
    }
}
