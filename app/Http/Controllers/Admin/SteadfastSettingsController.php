<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\CourierSettings;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/** Admin sending settings for Steadfast parcels. */
class SteadfastSettingsController extends Controller
{
    public function index()
    {
        return view('backEnd.steadfast.settings', [
            'ready' => Schema::hasTable('courier_settings'),
            'settings' => CourierSettings::all(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'weight_max_kg'       => ['required', 'numeric', 'min:1', 'max:999'],
            'steadfast_total_lot' => ['required', 'in:items,packages'],
        ]);

        CourierSettings::set([
            'weight_required'           => $request->boolean('weight_required') ? '1' : '0',
            'weight_in_note'            => $request->boolean('weight_in_note') ? '1' : '0',
            'weight_max_kg'             => rtrim(rtrim(number_format((float) $data['weight_max_kg'], 2, '.', ''), '0'), '.'),
            'steadfast_total_lot'       => $data['steadfast_total_lot'],
            'steadfast_pickup_reminder' => $request->boolean('steadfast_pickup_reminder') ? '1' : '0',
        ]);

        Toastr::success('Steadfast settings saved', 'Success');

        return back();
    }
}
