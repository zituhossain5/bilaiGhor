<?php

namespace App\Support;

use App\Models\DeliveryDistrict;
use App\Models\DeliveryDivision;
use App\Models\DeliveryUpazila;
use App\Models\DeliveryZone;

class DeliveryLocation
{
    /**
     * District → Zone (checkout / customer addresses).
     * The Division → District → Upazila helpers below are kept for the admin panel
     * and older data; they are simply no longer used by the checkout frontend.
     */
    public static function validateDistrictZone(?int $districtId, ?int $zoneId): bool
    {
        if (! $districtId || ! $zoneId) {
            return false;
        }

        if (! DeliveryDistrict::query()->whereKey($districtId)->where('status', 1)->exists()) {
            return false;
        }

        return DeliveryZone::query()
            ->whereKey($zoneId)
            ->where('district_id', $districtId)
            ->where('status', 1)
            ->exists();
    }

    /** "Zone, District" label — used for shippings.area and payment-gateway city. */
    public static function shippingLabelForZone(?int $districtId, ?int $zoneId): string
    {
        $zone     = $zoneId ? DeliveryZone::find($zoneId) : null;
        $district = $districtId ? DeliveryDistrict::find($districtId) : null;

        $parts = array_filter([$zone?->name, $district?->name]);

        return $parts ? implode(', ', $parts) : '';
    }

    /** The division a district belongs to — keeps shippings.division_id populated. */
    public static function divisionIdForDistrict(?int $districtId): ?int
    {
        if (! $districtId) {
            return null;
        }
        $district = DeliveryDistrict::find($districtId);

        return $district ? (int) $district->division_id : null;
    }

    public static function validateChain(?int $divisionId, ?int $districtId, ?int $upazilaId): bool
    {
        if (! $divisionId || ! $districtId || ! $upazilaId) {
            return false;
        }

        $district = DeliveryDistrict::query()
            ->whereKey($districtId)
            ->where('division_id', $divisionId)
            ->where('status', 1)
            ->first();

        if (! $district) {
            return false;
        }

        return DeliveryUpazila::query()
            ->whereKey($upazilaId)
            ->where('district_id', $districtId)
            ->where('status', 1)
            ->exists();
    }

    public static function shippingLabel(?int $divisionId, ?int $districtId, ?int $upazilaId): string
    {
        $upazila  = $upazilaId ? DeliveryUpazila::find($upazilaId) : null;
        $district = $districtId ? DeliveryDistrict::find($districtId) : null;
        $division = $divisionId ? DeliveryDivision::find($divisionId) : null;

        $parts = array_filter([
            $upazila?->name,
            $district?->name,
            $division?->name,
        ]);

        return $parts ? implode(', ', $parts) : '';
    }

    public static function chargeForDistrictId(?int $districtId): int
    {
        if (! $districtId) {
            return 0;
        }
        $district = DeliveryDistrict::query()->whereKey($districtId)->where('status', 1)->first();

        return $district ? (int) $district->delivery_charge : 0;
    }
}
