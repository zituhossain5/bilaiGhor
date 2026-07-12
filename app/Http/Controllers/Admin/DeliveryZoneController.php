<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryDistrict;
use App\Models\DeliveryDivision;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Shipping;
use App\Support\DeliveryLocation;
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

    /**
     * Persist Zone + Post Code from the admin Order Edit page.
     *
     * Split from OrderController::order_update() because that controller is
     * IonCube-encoded (source unreadable/unmodifiable) and its legacy validation
     * hard-requires a division_id/district_id/upazila_id triplet with no knowledge
     * of zone_id or post_code. district_id itself is still submitted to — and
     * saved by — the legacy form as before; this endpoint only adds the two new
     * columns, so the two writes never touch the same field.
     */
    public function updateOrderShipping(Request $request)
    {
        $request->validate([
            'order_id'    => 'required|integer|exists:orders,id',
            'district_id' => 'required|integer|exists:districts,id',
            'zone_id'     => 'required|integer|exists:delivery_zones,id',
            'post_code'   => 'nullable|string|max:20',
        ]);

        if (! DeliveryLocation::validateDistrictZone((int) $request->district_id, (int) $request->zone_id)) {
            return response()->json(['status' => 'error', 'message' => 'নির্বাচিত জোনটি এই জেলার অন্তর্ভুক্ত নয়।'], 422);
        }

        $order    = Order::findOrFail($request->order_id);
        $shipping = Shipping::where('order_id', $order->id)->first();
        if (! $shipping) {
            return response()->json(['status' => 'error', 'message' => 'শিপিং তথ্য পাওয়া যায়নি।'], 404);
        }

        $shipping->zone_id   = (int) $request->zone_id;
        $shipping->post_code = $request->post_code;
        $shipping->save();

        return response()->json(['status' => 'success', 'message' => 'জোন ও পোস্ট কোড আপডেট হয়েছে']);
    }
}
