<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryDistrict;
use App\Models\DeliveryThana;
use App\Models\DeliveryDivision;
use App\Models\Order;
use App\Models\Shipping;
use App\Services\OrderPaymentService;
use App\Support\DeliveryLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Toastr;

class DeliveryThanaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:shipping-list|shipping-create|shipping-edit|shipping-delete', ['only' => ['index']]);
        $this->middleware('permission:shipping-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:shipping-edit', ['only' => ['edit', 'update', 'updateOrderShipping']]);
        $this->middleware('permission:shipping-delete', ['only' => ['destroy']]);
    }

    public function index(DeliveryDistrict $district)
    {
        $district->load('division');
        $show_data = $district->thanas()->get();

        return view('backEnd.delivery.thana_index', compact('district', 'show_data'));
    }

    public function create(DeliveryDistrict $district)
    {
        $district->load('division');

        return view('backEnd.delivery.thana_create', compact('district'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        DeliveryThana::create($data);

        Toastr::success('Thana added successfully.', 'Success');

        return redirect()->route('admin.delivery.thanas.index', $data['district_id']);
    }

    public function edit(DeliveryThana $thana)
    {
        $thana->load('district.division');
        $divisions = DeliveryDivision::with(['districts' => fn ($query) => $query->ordered()])
            ->ordered()
            ->get();

        return view('backEnd.delivery.thana_edit', ['edit_data' => $thana, 'divisions' => $divisions]);
    }

    public function update(Request $request)
    {
        $request->validate(['id' => 'required|integer|exists:thanas,id']);
        $thana = DeliveryThana::findOrFail($request->id);
        $data = $this->validated($request);
        $thana->update($data);

        Toastr::success('Thana updated successfully.', 'Success');

        return redirect()->route('admin.delivery.thanas.index', $data['district_id']);
    }

    public function destroy(Request $request)
    {
        $request->validate(['hidden_id' => 'required|integer|exists:thanas,id']);
        $thana = DeliveryThana::findOrFail($request->hidden_id);
        $districtId = $thana->district_id;

        $isInUse = $thana->shippings()->exists()
            || $thana->customerAddresses()->exists()
            || $thana->customers()->exists();

        if ($isInUse) {
            Toastr::error('This Thana is used by customer or order records. Disable it instead of deleting it.', 'Cannot Delete');

            return redirect()->route('admin.delivery.thanas.index', $districtId);
        }

        $thana->delete();

        Toastr::success('Thana deleted successfully.', 'Success');

        return redirect()->route('admin.delivery.thanas.index', $districtId);
    }

    public function updateOrderShipping(Request $request)
    {
        $data = $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'district_id' => 'required|integer|exists:districts,id',
            'thana_id' => 'required|integer|exists:thanas,id',
            'post_code' => 'nullable|string|max:20',
        ]);

        if (! DeliveryLocation::validateDistrictThana((int) $data['district_id'], (int) $data['thana_id'])) {
            return response()->json(['status' => 'error', 'message' => 'The selected Thana does not belong to this district.'], 422);
        }

        $updated = DB::transaction(function () use ($data) {
            $order = Order::whereKey($data['order_id'])->lockForUpdate()->firstOrFail();
            $shipping = Shipping::where('order_id', $order->id)->lockForUpdate()->first();
            if (! $shipping) {
                return false;
            }

            $oldCharge = (float) $order->shipping_charge;
            $newCharge = DeliveryLocation::chargeForThanaId((int) $data['thana_id']);

            $shipping->district_id = (int) $data['district_id'];
            $shipping->division_id = DeliveryLocation::divisionIdForDistrict((int) $data['district_id']);
            $shipping->thana_id = (int) $data['thana_id'];
            $shipping->upazila_id = (int) $data['thana_id']; // encoded OrderController compatibility
            $shipping->post_code = $data['post_code'] ?? null;
            $shipping->area = DeliveryLocation::shippingLabel((int) $data['district_id'], (int) $data['thana_id']);
            $shipping->save();

            $order->shipping_charge = $newCharge;
            $order->amount = max(0, (float) $order->amount - $oldCharge + $newCharge);
            $order->save();

            if ((int) $order->is_manual_order === 1) {
                OrderPaymentService::syncSnapshot($order);
            }

            return true;
        });

        if (! $updated) {
            return response()->json(['status' => 'error', 'message' => 'Shipping information was not found.'], 404);
        }

        return response()->json(['status' => 'success', 'message' => 'Thana and post code updated successfully.']);
    }

    public function cartShipping(Request $request)
    {
        $data = $request->validate(['thana_id' => 'required|integer|exists:thanas,id']);
        $charge = DeliveryLocation::chargeForThanaId((int) $data['thana_id']);
        Session::put('pos_shipping', $charge);

        return response()->json(['shipping_charge' => $charge]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'district_id' => 'required|integer|exists:districts,id',
            'name' => 'required|string|max:190',
            'name_bn' => 'nullable|string|max:190',
            'post_code' => 'nullable|string|max:20',
            'delivery_charge' => 'required|numeric|min:0',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['status'] = $request->boolean('status');

        return $data;
    }
}
