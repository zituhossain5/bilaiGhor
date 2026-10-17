<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CourierTracking\OrderStatusGate;
use App\Services\CourierTracking\ParcelRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RedXWebhookController extends Controller
{
    public function __construct(
        private readonly ParcelRecorder $recorder,
        private readonly OrderStatusGate $gate,
    ) {}

    /**
     * RedX can't send a custom header, so the webhook URL carries a secret (?token=…) derived from
     * APP_KEY. Without it anyone could mark orders delivered or cancelled.
     */
    public static function secret(): string
    {
        return substr(hash_hmac('sha256', 'redx-webhook', (string) config('app.key')), 0, 40);
    }

    /** The URL to paste into the RedX merchant panel. */
    public static function webhookUrl(): string
    {
        return rtrim((string) config('app.url'), '/') . '/api/redx/webhook?token=' . self::secret();
    }

    /**
     * Every RedX update is stored on the parcel's tracking timeline. The order status changes only
     * through OrderStatusGate: the existing RedX mapping (RedXService::mapStatusToOrderStatus) applied
     * with the shared transition (payment, fund, vendor/reseller settlement, SMS), never to an order
     * that is already Delivered/Cancelled, and never from an event older than the last one applied.
     */
    public function handleWebhook(Request $request)
    {
        if (! hash_equals(self::secret(), (string) $request->query('token', ''))) {
            Log::warning('RedX Webhook rejected: missing or wrong token', ['ip' => $request->ip()]);

            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            Log::info('RedX Webhook Received', [
                'payload' => $request->all(),
            ]);

            $trackingNumber = trim((string) $request->input('tracking_number', ''));
            $status = trim((string) $request->input('status', ''));
            $invoiceNumber = trim((string) $request->input('invoice_number', ''));

            if ($trackingNumber === '' || $status === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required fields: tracking_number or status',
                ], 400);
            }

            // By tracking number first; the invoice only when one was sent (an empty invoice used to
            // match any order whose invoice_id is NULL).
            $order = Order::where('courier_tracking_id', $trackingNumber)
                ->orWhere('consignment_id', $trackingNumber)
                ->orderByDesc('id')
                ->first();
            if (! $order && $invoiceNumber !== '') {
                $order = Order::where('invoice_id', $invoiceNumber)->orderByDesc('id')->first();
            }

            $parcel = $order ? $this->recorder->forOrder($order) : null;
            $parcel ??= $this->recorder->find('redx', $trackingNumber)
                ?? $this->recorder->unlinked('redx', $trackingNumber, null, $invoiceNumber ?: null);

            $this->recorder->record($parcel, [
                'raw_status' => $status,
                'message'    => $request->input('message_en') ?: $request->input('message_bn'),
                'event_time' => $request->input('timestamp'),
                'source'     => 'webhook',
                'payload'    => $request->all(),
            ]);

            $eventTime = ParcelRecorder::time($request->input('timestamp')) ?? now();
            $changed = $this->gate->apply($parcel, $status, $eventTime, 'RedX');

            Log::info('RedX Webhook processed', [
                'order_id'       => $order?->id,
                'tracking_id'    => $trackingNumber,
                'redx_status'    => $status,
                'status_changed' => $changed,
                'linked'         => (bool) $parcel->order_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully',
            ], 200);

        } catch (\Exception $e) {
            Log::error('RedX Webhook Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
            ], 500);
        }
    }
}
