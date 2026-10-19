<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SteadfastPoliceStation;
use App\Models\ThanaSteadfastPoliceStation;
use App\Services\Steadfast\AreaMatcher;
use App\Services\Steadfast\PoliceStationSync;
use App\Services\Steadfast\SteadfastArea;
use App\Support\CourierSettings;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Admin → API Integration → Steadfast Settings: sending switches + thana → Steadfast area mapping. */
class SteadfastSettingsController extends Controller
{
    private const FILTERS = ['review', 'close', 'none', 'exact', 'manual', 'all'];

    public function index(Request $request)
    {
        $ready = Schema::hasTable('thana_steadfast_police_station') && Schema::hasTable('courier_settings');
        $filter = in_array($request->filter, self::FILTERS, true) ? $request->filter : 'review';
        $search = trim((string) $request->q);

        $rows = collect();
        $counts = [];
        $stations = [];
        $lastSync = null;
        if ($ready) {
            $base = DB::table('thanas')
                ->join('districts', 'districts.id', '=', 'thanas.district_id')
                ->leftJoin('thana_steadfast_police_station as m', 'm.thana_id', '=', 'thanas.id')
                ->leftJoin('steadfast_police_stations as s', 's.id', '=', 'm.steadfast_police_station_id');

            $counts = (clone $base)
                ->selectRaw("COALESCE(m.match_type, 'unsynced') as type, COUNT(*) as total")
                ->groupBy('type')
                ->pluck('total', 'type')
                ->all();

            $rows = (clone $base)
                ->select('thanas.id', 'thanas.name as thana', 'districts.name as district', 'm.match_type', 'm.score',
                    'm.steadfast_police_station_id as station_id', 's.name as station', 's.district_name as station_district', 's.status as station_status')
                ->when($filter === 'review', fn ($q) => $q->where(fn ($w) => $w->whereIn('m.match_type', ['close', 'none'])->orWhereNull('m.match_type')))
                ->when(in_array($filter, ['close', 'none', 'exact', 'manual'], true), fn ($q) => $q->where('m.match_type', $filter))
                ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('thanas.name', 'like', "%{$search}%")
                    ->orWhere('districts.name', 'like', "%{$search}%")->orWhere('s.name', 'like', "%{$search}%")))
                ->orderBy('districts.name')->orderBy('thanas.name')
                ->get();

            // The row's own Steadfast district(s) come first in its dropdown.
            $matcher = app(AreaMatcher::class);
            $sfDistricts = [];
            $rows->each(function ($row) use ($matcher, &$sfDistricts) {
                $row->sf_districts = $sfDistricts[$row->district] ??= $matcher->steadfastDistricts((string) $row->district);
            });

            // Searchable dropdown: every active Steadfast area, grouped by Steadfast district.
            $stations = SteadfastPoliceStation::where('status', true)->orderBy('district_name')->orderBy('name')
                ->get(['id', 'name', 'district_name'])
                ->groupBy('district_name')
                ->map(fn ($group) => $group->map(fn ($s) => ['id' => $s->id, 'text' => $s->name])->values())
                ->all();
            $lastSync = SteadfastPoliceStation::max('synced_at');
        }

        return view('backEnd.steadfast.settings', [
            'ready'    => $ready,
            'settings' => CourierSettings::all(),
            'filter'   => $filter,
            'search'   => $search,
            'rows'     => $rows,
            'counts'   => $counts,
            'stations' => $stations,
            'lastSync' => $lastSync,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'weight_max_kg'       => ['required', 'numeric', 'min:1', 'max:999'],
            'steadfast_total_lot' => ['required', 'in:items,packages'],
        ]);

        CourierSettings::set([
            'steadfast_area_in_address' => $request->boolean('steadfast_area_in_address') ? '1' : '0',
            'weight_required'           => $request->boolean('weight_required') ? '1' : '0',
            'weight_in_note'            => $request->boolean('weight_in_note') ? '1' : '0',
            'weight_max_kg'             => rtrim(rtrim(number_format((float) $data['weight_max_kg'], 2, '.', ''), '0'), '.'),
            'steadfast_total_lot'       => $data['steadfast_total_lot'],
            'steadfast_pickup_reminder' => $request->boolean('steadfast_pickup_reminder') ? '1' : '0',
        ]);

        Toastr::success('Steadfast settings saved', 'Success');

        return back();
    }

    /** "Sync Steadfast areas": read-only GET /police_stations, then auto-match (admin choices kept). */
    public function sync(PoliceStationSync $sync)
    {
        $result = $sync->sync();

        if (!$result['ok']) {
            Toastr::error($result['message'], 'Steadfast');

            return back();
        }

        $m = $result['match'];
        Toastr::success("{$result['message']} — মিলেছে {$m['exact']}, যাচাই দরকার {$m['close']}, মেলেনি {$m['none']}, ম্যানুয়াল {$m['manual']}", 'Steadfast');

        return back();
    }

    /** Admin choice for one thana: a Steadfast area, or none ("no Steadfast area fits"). */
    public function saveMapping(Request $request): JsonResponse
    {
        $data = $request->validate([
            'thana_id'   => ['required', 'integer', 'exists:thanas,id'],
            'station_id' => ['nullable', 'integer', 'exists:steadfast_police_stations,id'],
        ]);

        $row = ThanaSteadfastPoliceStation::updateOrCreate(['thana_id' => $data['thana_id']], [
            'steadfast_police_station_id' => $data['station_id'] ?? null,
            'match_type'                  => 'manual',
            'score'                       => null,
            'matched_by'                  => Auth::guard('admin')->id(),
        ]);
        SteadfastArea::forget();

        $station = $row->station;

        return response()->json([
            'status'  => 'success',
            'message' => $station ? 'সেভ হয়েছে: ' . $station->addressLabel() : 'সেভ হয়েছে: কোনো Steadfast এরিয়া নেই',
            'label'   => $station?->addressLabel(),
        ]);
    }
}
