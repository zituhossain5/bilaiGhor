<?php

namespace App\Support;

use App\Models\CourierSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Manual Fraud Check page settings, stored as extra keys in the courier_settings key/value table
 * (no new columns). Every key has a default, so the page works before that table exists.
 */
class FraudCheckSettings
{
    private const CACHE_KEY = 'fraud_check_settings.all';

    public const RATE_METHODS = ['average', 'weighted'];

    public const DEFAULTS = [
        // Overall rate: "average" = plain average of each courier's rate, "weighted" = weighted by parcel count.
        'fraud_rate_method'     => 'average',
        // Show the searched number as 019****0165 on the result.
        'fraud_mask_phone'      => '0',
        // Reuse a BD Courier answer for this many minutes before a new (paid) search. 0 = always search.
        'fraud_cache_minutes'   => '360',
        // Risk badge: overall rate at or above "safe" = Safe, at or above "medium" = Medium, below = High.
        'fraud_risk_safe_min'   => '90',
        'fraud_risk_medium_min' => '70',
    ];

    public static function ready(): bool
    {
        return rescue(fn () => Schema::hasTable('courier_settings'), false, false);
    }

    public static function all(): array
    {
        // Once per request (the container is fresh for every request and every test).
        if (app()->bound(self::CACHE_KEY)) {
            return app(self::CACHE_KEY);
        }

        $stored = rescue(
            fn () => self::ready()
                ? Cache::rememberForever(self::CACHE_KEY, fn () => CourierSetting::whereIn('key', array_keys(self::DEFAULTS))->pluck('value', 'key')->all())
                : [],
            [],
            false
        );

        $all = array_merge(self::DEFAULTS, array_intersect_key(array_filter($stored, fn ($v) => $v !== null), self::DEFAULTS));
        app()->instance(self::CACHE_KEY, $all);

        return $all;
    }

    public static function set(array $values): void
    {
        foreach (array_intersect_key($values, self::DEFAULTS) as $key => $value) {
            CourierSetting::updateOrCreate(['key' => $key], ['value' => $value === null ? null : (string) $value]);
        }
        Cache::forget(self::CACHE_KEY);
        app()->forgetInstance(self::CACHE_KEY);
    }

    public static function rateMethod(): string
    {
        $method = self::all()['fraud_rate_method'];

        return in_array($method, self::RATE_METHODS, true) ? $method : 'average';
    }

    public static function maskPhone(): bool
    {
        return self::all()['fraud_mask_phone'] === '1';
    }

    public static function cacheMinutes(): int
    {
        return max(0, (int) self::all()['fraud_cache_minutes']);
    }

    /** @return array{safe: float, medium: float} */
    public static function riskThresholds(): array
    {
        $all = self::all();

        return ['safe' => (float) $all['fraud_risk_safe_min'], 'medium' => (float) $all['fraud_risk_medium_min']];
    }
}
