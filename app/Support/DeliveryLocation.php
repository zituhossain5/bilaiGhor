<?php

namespace App\Support;

use App\Models\DeliveryDistrict;
use App\Models\DeliveryThana;

class DeliveryLocation
{
    public static function validateDistrictThana(?int $districtId, ?int $thanaId): bool
    {
        if (! $districtId || ! $thanaId) {
            return false;
        }

        if (! DeliveryDistrict::query()->whereKey($districtId)->where('status', 1)->exists()) {
            return false;
        }

        return DeliveryThana::query()
            ->whereKey($thanaId)
            ->where('district_id', $districtId)
            ->where('status', 1)
            ->exists();
    }

    public static function validateChain(?int $divisionId, ?int $districtId, ?int $thanaId): bool
    {
        if (! $divisionId || ! $districtId || ! $thanaId) {
            return false;
        }

        $district = DeliveryDistrict::query()
            ->whereKey($districtId)
            ->where('division_id', $divisionId)
            ->where('status', 1)
            ->first();

        return $district && self::validateDistrictThana($districtId, $thanaId);
    }

    public static function shippingLabel(?int $districtId, ?int $thanaId): string
    {
        $thana = $thanaId ? DeliveryThana::find($thanaId) : null;
        $district = $districtId ? DeliveryDistrict::with('division')->find($districtId) : null;

        $parts = array_filter([
            $thana?->name,
            $district?->name,
            $district?->division?->name,
        ]);

        return $parts ? implode(', ', $parts) : '';
    }

    public static function divisionIdForDistrict(?int $districtId): ?int
    {
        if (! $districtId) {
            return null;
        }

        $divisionId = DeliveryDistrict::query()->whereKey($districtId)->value('division_id');

        return $divisionId ? (int) $divisionId : null;
    }

    public static function chargeForThanaId(?int $thanaId): float
    {
        if (! $thanaId) {
            return 0;
        }

        return (float) (DeliveryThana::query()
            ->whereKey($thanaId)
            ->where('status', 1)
            ->value('delivery_charge') ?? 0);
    }
}
