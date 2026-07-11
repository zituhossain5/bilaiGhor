<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryDistrict;
use App\Models\DeliveryDivision;
use App\Models\DeliveryZone;
use Illuminate\Http\Request;
use Toastr;

class DeliveryZoneController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:shipping-list|shipping-create|shipping-edit|shipping-delete', ['only' => ['index']]);
        $this->middleware('permission:shipping-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:shipping-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:shipping-delete', ['only' => ['destroy']]);
    }

    public function index($district)
    {
        $district  = DeliveryDistrict::with('division')->findOrFail($district);
        $show_data = DeliveryZone::query()
            ->where('district_id', $district->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('backEnd.delivery.zone_index', compact('district', 'show_data'));
    }

    public function create($district)
    {
        $district = DeliveryDistrict::with('division')->findOrFail($district);

        return view('backEnd.delivery.zone_create', compact('district'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'district_id'     => 'required|exists:districts,id',
            'name'            => 'required|string|max:190',
            'name_bn'         => 'nullable|string|max:190',
            'post_code'       => 'nullable|string|max:20',
            'delivery_charge' => 'nullable|numeric|min:0',
            'sort_order'      => 'nullable|integer',
        ]);

        DeliveryZone::create([
            'district_id'     => $request->district_id,
            'name'            => $request->name,
            'name_bn'         => $request->name_bn,
            'post_code'       => $request->post_code,
            'delivery_charge' => $request->input('delivery_charge', 0) ?? 0,
            'sort_order'      => (int) $request->input('sort_order', 0),
            'status'          => $request->boolean('status') ? 1 : 0,
        ]);

        Toastr::success('জোন যোগ করা হয়েছে', 'সফল');

        return redirect()->route('admin.delivery.zones.index', $request->district_id);
    }

    public function edit($id)
    {
        $edit_data = DeliveryZone::with('district.division')->findOrFail($id);
        $divisions = DeliveryDivision::with(['districts' => function ($q) {
            $q->orderBy('sort_order')->orderBy('name');
        }])->orderBy('sort_order')->orderBy('name')->get();

        return view('backEnd.delivery.zone_edit', compact('edit_data', 'divisions'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'id'              => 'required|exists:delivery_zones,id',
            'district_id'     => 'required|exists:districts,id',
            'name'            => 'required|string|max:190',
            'name_bn'         => 'nullable|string|max:190',
            'post_code'       => 'nullable|string|max:20',
            'delivery_charge' => 'nullable|numeric|min:0',
            'sort_order'      => 'nullable|integer',
        ]);

        $row = DeliveryZone::findOrFail($request->id);
        $row->update([
            'district_id'     => $request->district_id,
            'name'            => $request->name,
            'name_bn'         => $request->name_bn,
            'post_code'       => $request->post_code,
            'delivery_charge' => $request->input('delivery_charge', 0) ?? 0,
            'sort_order'      => (int) $request->input('sort_order', 0),
            'status'          => $request->boolean('status') ? 1 : 0,
        ]);

        Toastr::success('আপডেট হয়েছে', 'সফল');

        return redirect()->route('admin.delivery.zones.index', $request->district_id);
    }

    public function destroy(Request $request)
    {
        $row        = DeliveryZone::findOrFail($request->hidden_id);
        $districtId = $row->district_id;
        $row->delete();
        Toastr::success('মুছে ফেলা হয়েছে', 'সফল');

        return redirect()->route('admin.delivery.zones.index', $districtId);
    }
}
