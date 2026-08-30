<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\DeliveryDistrict;
use App\Models\DeliveryDivision;
use App\Models\DeliveryThana;

class DeliveryAjaxController extends Controller
{
    public function districts(int $divisionId)
    {
        $division = DeliveryDivision::query()->whereKey($divisionId)->where('status', 1)->first();
        if (! $division) {
            return response()->json(['data' => []]);
        }

        $rows = DeliveryDistrict::query()
            ->where('division_id', $division->id)
            ->where('status', 1)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'delivery_charge']);

        return response()->json(['data' => $rows]);
    }

    public function thanas(int $districtId)
    {
        $district = DeliveryDistrict::query()->whereKey($districtId)->where('status', 1)->first();
        if (! $district) {
            return response()->json(['data' => []]);
        }

        $rows = DeliveryThana::query()
            ->where('district_id', $district->id)
            ->where('status', 1)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'name_bn', 'post_code', 'delivery_charge']);

        return response()->json(['data' => $rows]);
    }
}
