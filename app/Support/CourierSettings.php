<?php

namespace App\Support;

use App\Models\CourierSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Switches for how parcels are sent (Admin → API Integration → Steadfast Settings).
 * Every key has a default, so the site works before the courier_settings table exists.
 */
class CourierSettings
{
    private const CACHE_KEY = 'courier_settings.all';

    public const DEFAULTS = [
        // A parcel weight must be entered before an order can be sent to Steadfast.
        'weight_required'           => '1',
        // Steadfast's API has no weight field: write "Weight: 1.5 kg" at the end of the note.
        'weight_in_note'            => '1',
        'weight_max_kg'             => '30',
        // total_lot: "items" = total item quantity (old behaviour), "packages" = number of packages (default 1).
        'steadfast_total_lot'       => 'items',
        // Dashboard reminder to request a pickup in the Steadfast portal on days parcels were sent.
        'steadfast_pickup_reminder' => '1',
        // Y-m-d of the day the reminder was ticked "Pickup requested".
        'steadfast_pickup_done_on'  => null,
    ];

    public static function all(): array
    {
        // Once per request (the container is fresh for every request and every test).
        if (app()->bound(self::CACHE_KEY)) {
            return app(self::CACHE_KEY);
        }

        // Not cached until the table exists (code deployed before `php artisan migrate`).
        $stored = rescue(
            fn () => Schema::hasTable('courier_settings')
                ? Cache::rememberForever(self::CACHE_KEY, fn () => CourierSetting::pluck('value', 'key')->all())
                : [],
            [],
            false
        );

        $all = array_merge(self::DEFAULTS, array_intersect_key($stored, self::DEFAULTS));
        app()->instance(self::CACHE_KEY, $all);

        return $all;
    }

    public static function get(string $key): ?string
    {
        return self::all()[$key] ?? null;
    }

    public static function bool(string $key): bool
    {
        return (string) self::get($key) === '1';
    }

    public static function set(array $values): void
    {
        foreach (array_intersect_key($values, self::DEFAULTS) as $key => $value) {
            CourierSetting::updateOrCreate(['key' => $key], ['value' => $value === null ? null : (string) $value]);
        }
        Cache::forget(self::CACHE_KEY);
        app()->forgetInstance(self::CACHE_KEY);
    }

    public static function weightMax(): float
    {
        $max = (float) self::get('weight_max_kg');

        return $max > 0 ? $max : (float) self::DEFAULTS['weight_max_kg'];
    }

    public static function lotMode(): string
    {
        return self::get('steadfast_total_lot') === 'packages' ? 'packages' : 'items';
    }
}
