<?php

namespace App\Services\Steadfast;

use App\Models\ThanaSteadfastPoliceStation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/** The Steadfast area to add to a parcel address, from the thana mapping (exact or admin-chosen only). */
class SteadfastArea
{
    /** @return array{station_id:int, label:string}|null */
    public static function forThana(?int $thanaId): ?array
    {
        if (!$thanaId) {
            return null;
        }

        return self::map()[$thanaId] ?? null;
    }

    /**
     * Why a thana has no usable area: not_synced (Steadfast areas never synced), close (suggestion waiting for
     * an admin), none (nothing found or "no area fits"), or null when it is matched.
     */
    public static function problem(?int $thanaId): ?string
    {
        if (self::forThana($thanaId)) {
            return null;
        }
        if (!self::ready()) {
            return 'not_synced';
        }
        $row = $thanaId ? ThanaSteadfastPoliceStation::where('thana_id', $thanaId)->first() : null;

        return $row && $row->match_type === 'close' ? 'close' : 'none';
    }

    /** True once Steadfast areas were synced and matched. */
    public static function ready(): bool
    {
        return self::map() !== [] || rescue(fn () => Schema::hasTable('thana_steadfast_police_station')
            && ThanaSteadfastPoliceStation::exists(), false, false);
    }

    /** thana_id => [station_id, label]. Cached for a day; cleared on every sync and admin change. */
    private static function map(): array
    {
        if (app()->bound(AreaMatcher::MAP_CACHE_KEY)) {
            return app(AreaMatcher::MAP_CACHE_KEY);
        }

        $map = rescue(function () {
            if (!Schema::hasTable('thana_steadfast_police_station') || !Schema::hasTable('steadfast_police_stations')) {
                return [];
            }

            return Cache::remember(AreaMatcher::MAP_CACHE_KEY, now()->addDay(), function () {
                return ThanaSteadfastPoliceStation::with('station')
                    ->whereIn('match_type', ThanaSteadfastPoliceStation::USABLE)
                    ->whereNotNull('steadfast_police_station_id')
                    ->get()
                    ->filter(fn ($row) => $row->station && $row->station->status)
                    ->mapWithKeys(fn ($row) => [$row->thana_id => [
                        'station_id' => (int) $row->steadfast_police_station_id,
                        'label'      => $row->station->addressLabel(),
                    ]])
                    ->all();
            });
        }, [], false);

        app()->instance(AreaMatcher::MAP_CACHE_KEY, $map);

        return $map;
    }

    public static function forget(): void
    {
        AreaMatcher::forgetCache();
        app()->forgetInstance(AreaMatcher::MAP_CACHE_KEY);
    }
}
