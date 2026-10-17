<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CourierTracking\OrderStatusGate;
use App\Services\CourierTracking\ParcelRecorder;
use App\Support\PathaoStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Pathao merchant webhook (Pathao merchant panel → Developers API → Webhook integration).
 * Pathao sends the secret entered in its panel in the X-PATHAO-Signature header and expects a 202 that
 * carries its integration header back (behaviour taken from Pathao's own WooCommerce plugin).
 */
class PathaoWebhookController extends Controller
{
    public function __construct(
        private readonly ParcelRecorder $recorder,
        private readonly OrderStatusGate $gate,
    ) {}

    /** Secret to paste into the Pathao panel; derived from APP_KEY like the RedX webhook token. */
    public static function secret(): string
    {
        return substr(hash_hmac('sha256', 'pathao-webhook', (string) config('app.key')), 0, 40);
    }

    public static function webhookUrl(): string
    {
        return rtrim((string) config('app.url'), '/') . '/api/pathao/webhook';
    }

    public function handle(Request $request): JsonResponse
    {
        $provided = (string) $request->header('X-PATHAO-Signature', '');
        if ($provided === '' || ! hash_equals(self::secret(), $provided)) {
            Log::warning('Pathao Webhook rejected: missing or wrong signature', ['ip' => $request->ip()]);

            return $this->reply('Unauthorized', 401);
        }

        $event = strtolower(trim((string) $request->input('event', '')));

        // Pathao's "test / integrate" button.
        if ($event === 'webhook_integration') {
            return $this->reply('Successfully accepted webhook_integration');
        }

        try {
            Log::info('Pathao Webhook Received', ['payload' => $request->all()]);

            $consignmentId = trim((string) $request->input('consignment_id', ''));
            $merchantOrderId = trim((string) $request->input('merchant_order_id', ''));
            if ($consignmentId === '') {
                return $this->reply('consignment_id is required', 400);
            }

            // Event name ("order.delivery-failed") or order_status ("Delivery_Failed") → slug.
            $raw = PathaoStatus::slug($request->input('order_status') ?: $event);
            $infoOnly = in_array($raw, ['updated', 'order_updated', 'paid', 'payment_invoice', 'exchanged', 'exchange'], true);

            $order = Order::where('consignment_id', $consignmentId)
                ->orWhere('courier_tracking_id', $consignmentId)
                ->orderByDesc('id')
                ->first();
            if (! $order && $merchantOrderId !== '') {
                $order = Order::where('invoice_id', $merchantOrderId)->where('courier_type', 'pathao')->orderByDesc('id')->first();
            }

            $parcel = ($order ? $this->recorder->forOrder($order) : null)
                ?? $this->recorder->find('pathao', $consignmentId)
                ?? $this->recorder->unlinked('pathao', $consignmentId, null, $merchantOrderId ?: null);

            $time = $request->input('updated_at') ?: $request->input('timestamp');

            $this->recorder->record($parcel, [
                'raw_status' => $infoOnly ? null : $raw,
                'message'    => $request->input('reason') ?: ($infoOnly ? str_replace('_', ' ', ucfirst($raw)) : null),
                'event_time' => $time,
                'source'     => 'webhook',
                'payload'    => $request->all(),
                'details'    => array_filter([
                    'delivery_charge' => $request->input('delivery_fee'),
                    'courier_cod'     => $request->input('collected_amount') ?? $request->input('amount_to_collect'),
                    'invoice'         => $merchantOrderId,
                ], fn ($v) => $v !== null && $v !== ''),
            ]);

            $changed = ! $infoOnly
                && $this->gate->apply($parcel, $raw, ParcelRecorder::time($time) ?? now(), 'Pathao');

            Log::info('Pathao Webhook processed', [
                'order_id' => $order?->id, 'consignment_id' => $consignmentId, 'event' => $event, 'status_changed' => $changed,
            ]);

            return $this->reply('Order status updated');
        } catch (\Throwable $e) {
            Log::error('Pathao Webhook Error', ['error' => $e->getMessage(), 'payload' => $request->all()]);

            return $this->reply('Internal server error', 500);
        }
    }

    private function reply(string $message, int $status = 202): JsonResponse
    {
        return response()
            ->json(['message' => $message], $status)
            ->header('X-Pathao-Merchant-Webhook-Integration-Secret', (string) config('courier_tracking.pathao_integration_header'));
    }
}
