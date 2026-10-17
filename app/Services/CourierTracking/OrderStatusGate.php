<?php

namespace App\Services\CourierTracking;

use App\Models\CourierParcel;
use App\Models\Order;
use App\Services\CourierWebhookOrderService;
use App\Services\InventoryService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * The only place a courier report may change an order's status. It uses the existing
 * courier → order mappings (CourierStatusMap::orderStatusFor) and the existing transition
 * (CourierWebhookOrderService::applyStatusChange: payment, fund, settlements, SMS), plus two guards:
 *  - a Delivered or Cancelled order is never changed by a courier;
 *  - an event older than the last one applied to this parcel is ignored.
 */
class OrderStatusGate
{
    private const FINAL = [InventoryService::COMPLETE_STATUS, InventoryService::CANCEL_STATUS];

    public function __construct(private readonly CourierWebhookOrderService $orders) {}

    public function apply(CourierParcel $parcel, ?string $rawStatus, CarbonInterface $eventTime, string $sourceLabel): bool
    {
        $target = CourierStatusMap::orderStatusFor($parcel->courier, $rawStatus);
        if ($target === null || !$parcel->order_id || !$parcel->is_current) {
            return false;
        }

        $lock = Cache::lock('courier-order-status:' . $parcel->order_id, 15);

        return (bool) $lock->block(10, function () use ($parcel, $rawStatus, $eventTime, $sourceLabel, $target) {
            $parcel->refresh();
            $order = Order::find($parcel->order_id);
            if (!$order) {
                return false;
            }

            $current = (int) $order->order_status;
            if (in_array($current, self::FINAL, true)) {
                if ($current !== $target) {
                    Log::info('Courier status not applied: order already final', [
                        'order_id' => $order->id, 'order_status' => $current, 'courier' => $parcel->courier,
                        'raw_status' => $rawStatus, 'would_be' => $target,
                    ]);
                }

                return false;
            }

            if ($parcel->order_status_applied_at && $eventTime->lt($parcel->order_status_applied_at)) {
                Log::info('Courier status not applied: older than the last applied event', [
                    'order_id' => $order->id, 'raw_status' => $rawStatus, 'event_time' => (string) $eventTime,
                    'last_applied_at' => (string) $parcel->order_status_applied_at,
                ]);

                return false;
            }

            $changed = $this->orders->applyStatusChange($order, $target, $sourceLabel);

            $parcel->forceFill(['order_status_applied' => $target, 'order_status_applied_at' => $eventTime])->save();

            return $changed;
        });
    }
}
