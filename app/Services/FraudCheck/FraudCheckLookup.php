<?php

namespace App\Services\FraudCheck;

use App\Services\BdCourierService;
use App\Support\FraudCheckSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

/**
 * BD Courier answers, kept in the cache store so repeat checks of a number don't use paid searches.
 * Used by Manual Fraud Check, the order list / quick view "ফ্রড চেক" buttons and checkout.
 * Refresh skips the cache; a failed search falls back to the last answer.
 */
class FraudCheckLookup
{
    /**
     * @param  string  $phone  Normalized 01XXXXXXXXX (FraudPhone::normalize()).
     * @param  ?int  $adminId  Admin making the check (per-admin limit on new searches); null for checkout.
     * @return array{ok: bool, payload: ?array, checked_at: ?Carbon, from_cache: bool, stale: bool, error: ?string}
     */
    public static function bdCourier(string $phone, bool $refresh, ?int $adminId): array
    {
        $saved = Cache::get(self::resultKey($phone));
        $saved = is_array($saved) && is_array($saved['payload'] ?? null) ? $saved : null;
        $minutes = FraudCheckSettings::cacheMinutes();

        if (!$refresh && $saved && $minutes > 0 && $saved['checked_at'] > now()->subMinutes($minutes)->timestamp) {
            // Orders placed since the search still get the figures.
            BdCourierService::applyCourierPayloadToOrders($phone, (array) ($saved['payload']['data'] ?? []));

            return self::result($saved, fromCache: true);
        }

        if ($adminId !== null) {
            $limitKey = 'fraud-check:' . $adminId;
            $perMinute = (int) config('fraud_check.fresh_checks_per_minute', 10);
            if (RateLimiter::tooManyAttempts($limitKey, $perMinute)) {
                $error = "এক মিনিটে সর্বোচ্চ {$perMinute} বার নতুন চেক করা যায়। " . RateLimiter::availableIn($limitKey) . ' সেকেন্ড পরে আবার চেষ্টা করুন।';

                return self::fallback($saved, $error);
            }
            RateLimiter::hit($limitKey, 60);
        }

        $check = BdCourierService::fetchCourierCheck($phone, true);
        if (!$check['success']) {
            return self::fallback($saved, $check['message'] ?: 'BD Courier থেকে তথ্য পাওয়া যায়নি।');
        }

        $saved = ['payload' => $check['payload'], 'checked_at' => now()->timestamp];
        Cache::put(self::resultKey($phone), $saved, now()->addDays((int) config('fraud_check.keep_days', 7)));

        return self::result($saved, fromCache: false);
    }

    /** @return string[] The admin's last searched numbers, newest first. */
    public static function recent(int $adminId): array
    {
        $recent = Cache::get(self::recentKey($adminId));

        return is_array($recent) ? $recent : [];
    }

    public static function remember(int $adminId, string $phone): void
    {
        $recent = array_values(array_diff(self::recent($adminId), [$phone]));
        array_unshift($recent, $phone);
        Cache::forever(self::recentKey($adminId), array_slice($recent, 0, (int) config('fraud_check.recent_searches', 5)));
    }

    public static function resultKey(string $phone): string
    {
        return 'fraud_check:bdcourier:' . $phone;
    }

    private static function recentKey(int $adminId): string
    {
        return 'fraud_check:recent:' . $adminId;
    }

    private static function fallback(?array $saved, string $error): array
    {
        return $saved
            ? self::result($saved, fromCache: true, stale: true, error: $error)
            : ['ok' => false, 'payload' => null, 'checked_at' => null, 'from_cache' => false, 'stale' => false, 'error' => $error];
    }

    private static function result(array $saved, bool $fromCache, bool $stale = false, ?string $error = null): array
    {
        return [
            'ok'         => true,
            'payload'    => $saved['payload'],
            'checked_at' => Carbon::createFromTimestamp($saved['checked_at'])->timezone(config('app.timezone')),
            'from_cache' => $fromCache,
            'stale'      => $stale,
            'error'      => $error,
        ];
    }
}
