<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CourierDispatchService;
use App\Services\OrderPaymentService;
use App\Support\CourierSettings;
use App\Support\SteadfastPickup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Steadfast send dialog data, the order's parcel weight, and the daily pickup reminder. */
class CourierSendController extends Controller
{
    /** What the Steadfast send dialog shows for the chosen orders (nothing is sent here). */
    public function preview(Request $request): JsonResponse
    {
        $ids = collect((array) $request->input('order_ids', []))->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'কমপক্ষে একটি অর্ডার সিলেক্ট করুন'], 422);
        }

        $service = new CourierDispatchService();
        $orders = Order::with(['shipping', 'orderdetails', 'payment'])->whereIn('id', $ids)->get()
            ->sortBy(fn ($o) => $ids->search($o->id))
            ->map(function (Order $order) use ($service) {
                return [
                    'id'      => $order->id,
                    'invoice' => (string) $order->invoice_id,
                    'name'    => (string) optional($order->shipping)->name,
                    'phone'   => (string) optional($order->shipping)->phone,
                    'cod'     => (float) OrderPaymentService::state($order)['due'],
                    'qty'     => max(1, (int) $order->orderdetails->sum('qty')),
                    // Only what an admin saved on the order — never calculated from products.
                    'weight'  => $order->parcel_weight_kg !== null ? CourierDispatchService::formatWeight((float) $order->parcel_weight_kg) : null,
                    'reason'  => $service->cannotSend($order),
                ];
            })
            ->values();

        return response()->json([
            'status'   => 'success',
            'orders'   => $orders,
            'settings' => [
                'weight_required' => CourierSettings::bool('weight_required'),
                'weight_in_note'  => CourierSettings::bool('weight_in_note'),
                'weight_max'      => CourierSettings::weightMax(),
                'lot_mode'        => CourierSettings::lotMode(),
            ],
        ]);
    }

    /** Save (or clear) an order's parcel weight. Only changes orders.parcel_weight_kg. */
    public function saveWeight(Request $request, Order $order): JsonResponse
    {
        if (!CourierDispatchService::hasWeightColumn()) {
            return response()->json(['status' => 'error', 'message' => 'আগে `php artisan migrate` চালান'], 422);
        }

        $typed = trim((string) $request->input('weight', ''));
        if ($typed !== '' && ($error = CourierDispatchService::weightError($typed))) {
            return response()->json(['status' => 'error', 'message' => $error], 422);
        }

        $weight = $typed === '' ? null : CourierDispatchService::parseWeight($typed);
        $order->forceFill(['parcel_weight_kg' => $weight])->save();

        return response()->json([
            'status'  => 'success',
            'message' => $weight === null ? 'ওজন মুছে ফেলা হয়েছে' : 'ওজন সেভ হয়েছে',
            'weight'  => $weight === null ? null : CourierDispatchService::formatWeight($weight),
        ]);
    }

    /** "Pickup requested" tick on the reminder: hides it for the rest of today. */
    public function pickupDone(Request $request): JsonResponse
    {
        SteadfastPickup::markDone($request->boolean('done', true));

        return response()->json(['status' => 'success', 'reminder' => SteadfastPickup::reminder()]);
    }
}
