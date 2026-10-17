<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Courierapi;
use App\Models\Order;
use App\Services\CourierTracking\OrderStatusGate;
use App\Services\CourierTracking\ParcelRecorder;
use App\Services\CourierWebhookOrderService;
use App\Support\SteadfastWebhookStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SteadfastWebhookController extends Controller
{
    public function __construct(
        private readonly CourierWebhookOrderService $webhookOrders,
        private readonly ParcelRecorder $recorder,
        private readonly OrderStatusGate $gate,
    ) {}

    /**
     * Steadfast webhook — delivery_status | tracking_update
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            Log::info('Steadfast Webhook Received', [
                'payload' => $request->all(),
                'ip'      => $request->ip(),
            ]);

            if (! $this->authorizeWebhook($request)) {
                return $this->jsonError('Unauthorized', 401);
            }

            $type = strtolower(trim((string) $request->input('notification_type', '')));

            return match ($type) {
                'delivery_status' => $this->handleDeliveryStatus($request),
                'tracking_update' => $this->handleTrackingUpdate($request),
                default           => $this->jsonError('Unknown or missing notification_type', 400),
            };
        } catch (\Throwable $e) {
            Log::error('Steadfast Webhook Error', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);

            return $this->jsonError('Internal server error', 500);
        }
    }

    private function handleDeliveryStatus(Request $request): JsonResponse
    {
        $consignmentId = $request->input('consignment_id');
        $invoice         = $request->input('invoice');
        $status          = $request->input('status');

        if ($consignmentId === null && $invoice === null) {
            return $this->jsonError('consignment_id or invoice is required', 400);
        }

        if ($status === null || trim((string) $status) === '') {
            return $this->jsonError('status is required', 400);
        }

        $trackingMessage = trim((string) $request->input('tracking_message', ''));
        $order = $this->findOrder($consignmentId, $invoice);

        if (! $order) {
            // A parcel the site has no order for (e.g. entered in the Steadfast portal): keep its timeline
            // so it can be linked to an order later. No order is touched.
            $this->recordUnlinked($request, (string) $status, $trackingMessage);

            Log::warning('Steadfast Webhook: no order with this consignment/invoice (recorded on its parcel)', [
                'consignment_id' => $consignmentId,
                'invoice'          => $invoice,
            ]);

            return $this->jsonSuccess();
        }

        if ($consignmentId !== null && empty($order->consignment_id)) {
            $order->consignment_id = (string) $consignmentId;
        }

        if ($consignmentId !== null && empty($order->courier_tracking_id)) {
            $order->courier_tracking_id = (string) $consignmentId;
        }

        $order->courier_type = $order->courier_type ?: 'steadfast';
        $order->save();

        if ($trackingMessage !== '') {
            $this->webhookOrders->appendCourierNote($order, $trackingMessage);
            $order->refresh();
        }

        $parcel = $this->recorder->forOrder($order);
        $eventTime = ParcelRecorder::time($request->input('updated_at')) ?? now();

        if ($parcel) {
            $this->recorder->record($parcel, [
                'raw_status' => (string) $status,
                'message'    => $trackingMessage,
                'event_time' => $request->input('updated_at'),
                'source'     => 'webhook',
                'payload'    => $request->all(),
                'details'    => array_filter([
                    'tracking_code'   => $request->input('tracking_code'),
                    'courier_cod'     => $request->input('cod_amount'),
                    'delivery_charge' => $request->input('delivery_charge'),
                ], fn ($v) => $v !== null && $v !== ''),
            ]);
        }

        // Only an explicit delivery_status may complete/cancel the order (never the free-text message),
        // and never an order that is already Delivered/Cancelled or an event older than the last applied.
        $changed = $parcel
            ? $this->gate->apply($parcel, (string) $status, $eventTime, 'Steadfast')
            : false;

        Log::info('Steadfast Webhook: order status '.($changed ? 'updated' : 'unchanged'), [
            'order_id'         => $order->id,
            'invoice_id'       => $order->invoice_id,
            'steadfast_status' => (string) $status,
            'order_status_id'  => SteadfastWebhookStatus::toOrderStatusId((string) $status),
            'status_changed'   => $changed,
            'cod_amount'       => $request->input('cod_amount'),
            'delivery_charge'  => $request->input('delivery_charge'),
        ]);

        return $this->jsonSuccess();
    }

    private function handleTrackingUpdate(Request $request): JsonResponse
    {
        $consignmentId = $request->input('consignment_id');
        $invoice       = $request->input('invoice');
        $message       = trim((string) $request->input('tracking_message', ''));

        if ($consignmentId === null && $invoice === null) {
            return $this->jsonError('consignment_id or invoice is required', 400);
        }

        if ($message === '') {
            return $this->jsonError('tracking_message is required', 400);
        }

        $order = $this->findOrder($consignmentId, $invoice);

        if (! $order) {
            $this->recordUnlinked($request, null, $message);

            return $this->jsonSuccess();
        }

        if ($consignmentId !== null && empty($order->consignment_id)) {
            $order->consignment_id = (string) $consignmentId;
            $order->save();
        }

        $this->webhookOrders->appendCourierNote($order, $message);
        $order->refresh();

        // Timeline only: a tracking message never changes the order status
        // (an "Assigned for delivery" message used to be read as Delivered).
        if ($parcel = $this->recorder->forOrder($order)) {
            $this->recorder->record($parcel, [
                'message'    => $message,
                'event_time' => $request->input('updated_at'),
                'source'     => 'webhook',
                'payload'    => $request->all(),
            ]);
        }

        Log::info('Steadfast Webhook: tracking_update (timeline only)', [
            'order_id'   => $order->id,
            'invoice_id' => $order->invoice_id,
            'message'    => $message,
        ]);

        return $this->jsonSuccess();
    }

    private function recordUnlinked(Request $request, ?string $status, string $message): void
    {
        $cid = trim((string) $request->input('consignment_id', ''));
        if ($cid === '') {
            return;
        }

        $parcel = $this->recorder->find('steadfast', $cid, $request->input('tracking_code'))
            ?? $this->recorder->unlinked('steadfast', $cid, $request->input('tracking_code'), $request->input('invoice'));

        $this->recorder->record($parcel, [
            'raw_status' => $status,
            'message'    => $message,
            'event_time' => $request->input('updated_at'),
            'source'     => 'webhook',
            'payload'    => $request->all(),
            'details'    => array_filter([
                'courier_cod'     => $request->input('cod_amount'),
                'delivery_charge' => $request->input('delivery_charge'),
            ], fn ($v) => $v !== null && $v !== ''),
        ]);

        // A parcel linked to an order from the admin panel (the order has no courier columns of its own):
        // the order follows the same rules as any other Steadfast order.
        if ($status !== null && $parcel->order_id) {
            $this->gate->apply($parcel, $status, ParcelRecorder::time($request->input('updated_at')) ?? now(), 'Steadfast');
        }
    }

    private function findOrder(mixed $consignmentId, mixed $invoice): ?Order
    {
        if ($consignmentId !== null && $consignmentId !== '') {
            $cid = trim((string) $consignmentId);

            $byCourier = Order::query()
                ->where(function ($q) use ($cid) {
                    $q->where('consignment_id', $cid)
                        ->orWhere('courier_tracking_id', $cid)
                        ->orWhere('courier_tracking_code', $cid);
                })
                ->orderByDesc('id')
                ->first();

            if ($byCourier) {
                return $byCourier;
            }
        }

        if ($invoice !== null && trim((string) $invoice) !== '') {
            $inv = trim((string) $invoice);

            return Order::query()
                ->where(function ($q) use ($inv) {
                    $q->where('invoice_id', $inv);
                    if (ctype_digit($inv)) {
                        $q->orWhere('id', (int) $inv);
                    }
                })
                ->orderByDesc('id')
                ->first();
        }

        return null;
    }

    private function authorizeWebhook(Request $request): bool
    {
        $cfg = Courierapi::where('type', 'steadfast')->where('status', 1)->first()
            ?? Courierapi::where('type', 'steadfast')->first();

        if (! $cfg || empty(trim((string) $cfg->token))) {
            return true;
        }

        $expected = trim((string) $cfg->token);
        $expected = preg_replace('/^Bearer\s+/i', '', $expected);

        $provided = $request->bearerToken();
        if ($provided === null) {
            $header = $request->header('Authorization', '');
            if (preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
                $provided = trim($m[1]);
            }
        }

        return $provided !== null && hash_equals($expected, $provided);
    }

    private function jsonSuccess(): JsonResponse
    {
        return response()->json([
            'status'  => 'success',
            'message' => 'Webhook received successfully.',
        ], 200);
    }

    private function jsonError(string $message, int $code): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => $message,
        ], $code);
    }
}
