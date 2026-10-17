<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourierParcel;
use App\Models\Order;
use App\Services\CourierDispatchService;
use App\Services\CourierTracking\CourierReader;
use App\Services\CourierTracking\CourierStatusMap;
use App\Services\CourierTracking\ParcelRecorder;
use App\Services\CourierTracking\RefreshService;
use App\Services\InventoryService;
use App\Services\OrderPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Light courier tracking: link a parcel created in a courier portal to an order (after a preview the
 * admin confirms), and refresh a parcel's status on demand. Links live in courier_parcels only; the
 * order's own courier columns are never written here.
 */
class CourierParcelController extends Controller
{
    public function __construct(
        private readonly CourierReader $reader,
        private readonly ParcelRecorder $recorder,
        private readonly RefreshService $refresher,
    ) {}

    /** What the courier says about this parcel, plus warnings, before anything is saved. */
    public function preview(Request $request): JsonResponse
    {
        [$order, $courier, $code] = $this->input($request);
        if ($error = $this->linkError($order, $courier, $code)) {
            return response()->json(['ok' => false, 'message' => $error], 422);
        }

        $existing = $this->recorder->find($courier, $code, $code);
        $probe = $existing ?? new CourierParcel($this->ids($courier, $code));
        $probe->courier = $courier;
        $result = $this->reader->read($probe);

        $raw = $result['raw_status'] ?? $existing?->raw_status;
        $normalized = CourierStatusMap::normalize($courier, $raw) ?? $existing?->status;
        $info = config('courier_tracking.statuses.' . ($normalized ?: 'unknown'));
        $details = array_merge(
            array_filter($existing?->only(['recipient_name', 'recipient_phone', 'courier_cod', 'delivery_charge']) ?? []),
            $result['details'] ?? []
        );

        return response()->json([
            'ok'          => true,
            'courier'     => CourierDispatchService::label($courier),
            'reachable'   => (bool) $result['ok'],
            'status'      => $raw ? ($info['bn'] . ' (' . $raw . ')') : null,
            'tone'        => $info['tone'] ?? 'secondary',
            'details'     => [
                'recipient' => trim(($details['recipient_name'] ?? '') . ' ' . ($details['recipient_phone'] ?? '')) ?: null,
                'cod'       => isset($details['courier_cod']) ? '৳' . number_format((float) $details['courier_cod'], 2) : null,
                'events'    => $existing ? $existing->events()->count() : 0,
            ],
            'warnings'    => $this->warnings($order, $courier, $code, $result, $raw, $details),
        ]);
    }

    public function link(Request $request): JsonResponse
    {
        [$order, $courier, $code] = $this->input($request);
        if ($error = $this->linkError($order, $courier, $code)) {
            return response()->json(['ok' => false, 'message' => $error], 422);
        }

        $parcel = DB::transaction(function () use ($order, $courier, $code) {
            $parcel = $this->recorder->find($courier, $code, $code)
                ?? CourierParcel::create($this->ids($courier, $code) + ['courier' => $courier, 'status' => 'unknown']);

            CourierParcel::where('order_id', $order->id)->whereKeyNot($parcel->id)->update(['is_current' => false]);
            $parcel->forceFill([
                'order_id'    => $order->id,
                'invoice'     => $parcel->invoice ?: $order->invoice_id,
                'link_source' => 'link',
                'linked_by'   => Auth::id(),
                'linked_at'   => now(),
                'is_current'  => true,
            ])->save();

            // Timeline entries recorded before the link now belong to this order too.
            \App\Models\CourierTrackingEvent::where('courier_parcel_id', $parcel->id)->whereNull('order_id')->update(['order_id' => $order->id]);

            $this->recorder->record($parcel, [
                'message' => 'Linked to order #' . $order->invoice_id . ' by ' . (Auth::user()->name ?? 'admin'),
                'source'  => 'link',
            ]);

            return $parcel;
        });

        // Fetch the courier's status now; the order follows the existing rules (only if not already final).
        Cache::forget('courier-refresh:' . $parcel->id);
        $result = $this->refresher->refresh($parcel->fresh(), 'manual_refresh');

        return response()->json([
            'ok'      => true,
            'message' => 'পার্সেল লিংক হয়েছে। ' . $result['message'],
            'order_status_changed' => (bool) ($result['order_status_changed'] ?? false),
        ]);
    }

    public function refresh(CourierParcel $parcel): JsonResponse
    {
        $result = $this->refresher->refresh($parcel);
        $parcel->refresh();

        return response()->json([
            'ok'      => $result['ok'],
            'message' => $result['message'],
            'order_status_changed' => (bool) ($result['order_status_changed'] ?? false),
            'html'    => view('backEnd.order.partials.courier_tracking_box', [
                'parcel' => $parcel, 'order' => $parcel->order,
            ])->render(),
        ]);
    }

    /** @return array{0: ?Order, 1: string, 2: string} */
    private function input(Request $request): array
    {
        $request->validate([
            'order_id' => ['required', 'integer'],
            'courier'  => ['required', 'in:steadfast,pathao,redx'],
            'code'     => ['required', 'string', 'max:64'],
        ]);

        return [
            Order::with(['shipping', 'payment'])->find($request->integer('order_id')),
            (string) $request->input('courier'),
            trim((string) $request->input('code')),
        ];
    }

    private function linkError(?Order $order, string $courier, string $code): ?string
    {
        if (!$order) {
            return 'অর্ডার পাওয়া যায়নি';
        }
        if (!preg_match('/^[A-Za-z0-9_-]{3,64}$/', $code)) {
            return 'Consignment ID / tracking code সঠিক নয়';
        }

        $existing = $this->recorder->find($courier, $code, $code);
        if ($existing && $existing->order_id && (int) $existing->order_id !== (int) $order->id) {
            $other = Order::find($existing->order_id);

            return 'এই পার্সেলটি আগেই অর্ডার #' . ($other->invoice_id ?? $existing->order_id) . ' এর সাথে লিংক করা আছে';
        }

        return null;
    }

    /** Steadfast consignment ids are numbers; anything else is treated as a tracking code. */
    private function ids(string $courier, string $code): array
    {
        if ($courier === 'steadfast' && !ctype_digit($code)) {
            return ['consignment_id' => $code, 'tracking_code' => $code];
        }

        return ['consignment_id' => $code];
    }

    private function warnings(Order $order, string $courier, string $code, array $result, ?string $raw, array $details): array
    {
        $w = [];
        $label = CourierDispatchService::label($courier);

        if (!$result['ok']) {
            $w[] = "{$label} থেকে যাচাই করা যায়নি ({$result['error']}) — নম্বরটি ঠিক আছে কিনা পোর্টালে দেখে নিন।";
        }
        if ($courier === 'steadfast' && !ctype_digit($code)) {
            $w[] = 'Steadfast এর জন্য Consignment ID (শুধু সংখ্যা, যেমন 308914966) দিন — নাহলে Steadfast এর আপডেট এই অর্ডারে মিলবে না।';
        }
        if (filled($order->courier_tracking_id) && $order->courier_tracking_id !== $code) {
            $w[] = 'এই অর্ডারে আগে থেকেই অন্য একটি কুরিয়ার ID আছে: ' . $order->courier_tracking_id;
        }

        $orderPhone = substr(preg_replace('/\D+/', '', (string) optional($order->shipping)->phone), -11);
        $courierPhone = substr(preg_replace('/\D+/', '', (string) ($details['recipient_phone'] ?? '')), -11);
        if ($orderPhone !== '' && $courierPhone !== '' && $orderPhone !== $courierPhone) {
            $w[] = "ফোন মেলেনি: অর্ডারে {$orderPhone}, কুরিয়ারে {$courierPhone}";
        }

        if (isset($details['courier_cod'])) {
            $due = max(0, (float) $order->amount - (float) OrderPaymentService::state($order)['paid']);
            if (abs($due - (float) $details['courier_cod']) >= 1) {
                $w[] = 'COD মেলেনি: অর্ডারের বাকি ৳' . number_format($due, 2) . ', কুরিয়ারে ৳' . number_format((float) $details['courier_cod'], 2) . ' (অর্ডারের টাকা বদলানো হবে না)';
            }
        }

        $target = CourierStatusMap::orderStatusFor($courier, $raw);
        $final = in_array((int) $order->order_status, [InventoryService::COMPLETE_STATUS, InventoryService::CANCEL_STATUS], true);
        if ($target && !$final) {
            $w[] = 'কুরিয়ার বলছে পার্সেলটি ' . ($target === InventoryService::COMPLETE_STATUS ? 'ডেলিভারড' : 'বাতিল/ফেরত')
                . ' — লিংক করলে অর্ডারটি ' . ($target === InventoryService::COMPLETE_STATUS ? 'Delivered' : 'Cancelled')
                . ' হয়ে যাবে (পেমেন্ট ও SMS সহ, আগের নিয়মে)।';
        }

        return $w;
    }
}
