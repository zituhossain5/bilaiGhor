<?php

namespace App\Services\CourierTracking;

use App\Models\CourierParcel;
use App\Models\CourierTrackingEvent;
use App\Models\Order;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Writes courier_parcels / courier_tracking_events only. Never touches `orders`:
 * order status changes go through OrderStatusGate.
 */
class ParcelRecorder
{
    /** Statuses too generic to replace a more specific in-progress status (e.g. Steadfast "pending"). */
    private const WEAK = ['in_progress', 'approved', 'unknown'];
    private const REPLACEABLE_BY_WEAK = ['sent', 'in_review', 'unknown', 'pending_pickup', 'approved', 'in_progress'];

    private const STEP_COLUMNS = [
        'sent'                => 'entered_at',
        'in_review'           => 'entered_at',
        'approved'            => 'approved_at',
        'in_progress'         => 'approved_at',
        'pending_pickup'      => 'approved_at',
        'picked_up'           => 'picked_at',
        'out_for_delivery'    => 'out_for_delivery_at',
        'delivered'           => 'delivered_at',
        'partially_delivered' => 'delivered_at',
        'returned'            => 'returned_at',
    ];

    private const DETAIL_FIELDS = [
        'tracking_code', 'invoice', 'courier_cod', 'delivery_charge', 'delivery_charge_paid', 'weight', 'items_in_lot',
        'recipient_name', 'recipient_phone', 'recipient_address', 'recipient_area',
        'rider_name', 'rider_phone', 'hub_name', 'hub_phone', 'note',
    ];

    /**
     * The parcel recorded in an order's own courier columns (created in courier_parcels if missing).
     * The order's columns are only read.
     */
    public function forOrder(Order $order, string $linkSource = 'order'): ?CourierParcel
    {
        $courier = strtolower(trim((string) $order->courier_type));
        $consignment = trim((string) ($order->consignment_id ?: $order->courier_tracking_id));
        if ($consignment === '') {
            return null;
        }
        // Old rows with a bare tracking id were always Steadfast (same assumption as the order pages).
        $courier = $courier !== '' ? $courier : 'steadfast';

        $parcel = CourierParcel::firstOrCreate(
            ['courier' => $courier, 'consignment_id' => $consignment],
            [
                'order_id'      => $order->id,
                'tracking_code' => filled($order->courier_tracking_code) ? $order->courier_tracking_code : null,
                'invoice'       => $order->invoice_id,
                'status'        => 'sent',
                'status_at'     => $order->courier_sent_at,
                'entered_at'    => $order->courier_sent_at,
                'link_source'   => $linkSource,
            ]
        );

        // A parcel first seen through a webhook (no order yet) belongs to this order: its own columns name it.
        if (!$parcel->order_id) {
            $parcel->forceFill(['order_id' => $order->id, 'link_source' => $linkSource])->save();
        }

        return $parcel;
    }

    /** Find a known parcel by consignment id or tracking code; falls back to orders' courier columns. */
    public function find(string $courier, ?string $consignmentId, ?string $trackingCode = null): ?CourierParcel
    {
        $consignmentId = trim((string) $consignmentId);
        $trackingCode = trim((string) $trackingCode);

        $parcel = CourierParcel::where('courier', $courier)
            ->where(function ($q) use ($consignmentId, $trackingCode) {
                if ($consignmentId !== '') {
                    $q->orWhere('consignment_id', $consignmentId)->orWhere('tracking_code', $consignmentId);
                }
                if ($trackingCode !== '') {
                    $q->orWhere('tracking_code', $trackingCode)->orWhere('consignment_id', $trackingCode);
                }
            })
            ->when($consignmentId === '' && $trackingCode === '', fn ($q) => $q->whereRaw('1 = 0'))
            ->orderByDesc('id')
            ->first();

        return $parcel;
    }

    /** A parcel the site has no order for yet (e.g. entered in the courier portal). Shown under "unlinked". */
    public function unlinked(string $courier, string $consignmentId, ?string $trackingCode = null, ?string $invoice = null): CourierParcel
    {
        return CourierParcel::firstOrCreate(
            ['courier' => $courier, 'consignment_id' => $consignmentId],
            [
                'tracking_code' => $trackingCode ?: null,
                'invoice'       => $invoice ?: null,
                'status'        => 'unknown',
                'link_source'   => 'webhook',
            ]
        );
    }

    /**
     * Store one timeline event and move the parcel snapshot forward if the event is newer.
     *
     * @param  array{raw_status?: ?string, normalized_status?: ?string, message?: ?string, hub?: ?string,
     *               rider_name?: ?string, rider_phone?: ?string, event_time?: CarbonInterface|string|null,
     *               source: string, payload?: ?array, details?: array}  $e
     * @return CourierTrackingEvent|null  null when it was a duplicate / an unchanged sync result
     */
    public function record(CourierParcel $parcel, array $e): ?CourierTrackingEvent
    {
        $source = $e['source'];
        $raw = filled($e['raw_status'] ?? null) ? trim((string) $e['raw_status']) : null;
        $message = filled($e['message'] ?? null) ? trim((string) $e['message']) : null;
        $time = self::time($e['event_time'] ?? null);
        $fromCourier = $time !== null;
        $time ??= now();

        $normalized = $e['normalized_status']
            ?? CourierStatusMap::normalize($parcel->courier, $raw)
            ?? CourierStatusMap::fromMessage($parcel->courier, $message);

        // Polling finds the same status every 10 minutes: only a change is an event.
        if (in_array($source, ['sync', 'manual_refresh'], true) && $message === null
            && $raw !== null && CourierStatusMap::key($raw) === CourierStatusMap::key($parcel->raw_status)) {
            $parcel->forceFill(['last_synced_at' => now()])->save();

            return null;
        }

        // Webhooks without a courier time are deduped by their payload (a resend is identical).
        $when = $fromCourier
            ? $time->copy()->utc()->format('Y-m-d H:i:s')
            : ($source === 'webhook' ? 'p:' . md5(json_encode($e['payload'] ?? [])) : $source . ':' . now()->format('Y-m-d H:i:s.u'));

        $hash = hash('sha256', implode('|', [
            $parcel->courier, $parcel->consignment_id ?: 'p' . $parcel->id, CourierStatusMap::key($raw), $when, (string) $message,
        ]));

        try {
            $event = CourierTrackingEvent::create([
                'courier_parcel_id'       => $parcel->id,
                'order_id'                => $parcel->order_id,
                'courier'                 => $parcel->courier,
                'consignment_id'          => $parcel->consignment_id,
                'tracking_code'           => $parcel->tracking_code,
                'raw_status'              => $raw,
                'normalized_status'       => $normalized,
                'message'                 => $message,
                'hub'                     => $e['hub'] ?? null,
                'rider_name'              => $e['rider_name'] ?? null,
                'rider_phone'             => $e['rider_phone'] ?? null,
                'event_time'              => $time,
                'event_time_from_courier' => $fromCourier,
                'source'                  => $source,
                'raw_payload'             => $e['payload'] ?? null,
                'dedupe_hash'             => $hash,
            ]);
        } catch (UniqueConstraintViolationException) {
            return null; // already stored (duplicate webhook / history entry)
        }

        $this->advance($parcel, $event, $e['details'] ?? []);

        return $event;
    }

    /** Courier-reported details fetched just now (refresh / link preview): stored on the parcel only. */
    public function updateDetails(CourierParcel $parcel, array $details): void
    {
        $fill = array_filter(
            array_intersect_key($details, array_flip(self::DETAIL_FIELDS)),
            fn ($v) => $v !== null && $v !== ''
        );
        if ($fill) {
            $parcel->forceFill($fill)->save();
        }
    }

    private function advance(CourierParcel $parcel, CourierTrackingEvent $event, array $details): void
    {
        DB::transaction(function () use ($parcel, $event, $details) {
            $p = CourierParcel::whereKey($parcel->id)->lockForUpdate()->first();
            $time = $event->event_time;
            $newer = !$p->status_at || $time->gte($p->status_at);
            $status = $event->normalized_status;

            $updates = [];
            if (!$p->last_event_at || $time->gt($p->last_event_at)) {
                $updates['last_event_at'] = $time;
            }

            $weakDowngrade = in_array($status, self::WEAK, true) && !in_array($p->status, self::REPLACEABLE_BY_WEAK, true);
            if ($status && $newer && !$weakDowngrade) {
                $updates['status'] = $status;
                $updates['status_at'] = $time;
                if ($event->raw_status !== null) {
                    $updates['raw_status'] = $event->raw_status;
                }
            } elseif ($event->raw_status !== null && $newer) {
                $updates['raw_status'] = $event->raw_status;
            }

            $column = self::STEP_COLUMNS[$status] ?? null;
            if ($column && !$p->{$column}) {
                $updates[$column] = $time;
            }

            // Rider/hub/money: the newest report wins; an older event only fills gaps.
            $details += array_filter([
                'rider_name'  => $event->rider_name,
                'rider_phone' => $event->rider_phone,
                'hub_name'    => $event->hub,
            ]);
            foreach (array_intersect_key($details, array_flip(self::DETAIL_FIELDS)) as $field => $value) {
                if ($value !== null && $value !== '' && ($newer || blank($p->{$field}))) {
                    $updates[$field] = $value;
                }
            }

            if ($updates) {
                $p->forceFill($updates)->save();
            }
            $parcel->setRawAttributes($p->getAttributes(), true);
        });
    }

    /** Courier timestamps are Bangladesh time unless they carry an offset. */
    public static function time(CarbonInterface|string|int|null $value): ?Carbon
    {
        if ($value instanceof CarbonInterface) {
            return Carbon::instance($value);
        }
        if (blank($value)) {
            return null;
        }

        try {
            return is_numeric($value)
                ? Carbon::createFromTimestamp((int) (strlen((string) $value) > 10 ? $value / 1000 : $value), config('app.timezone'))
                : Carbon::parse((string) $value, config('app.timezone'));
        } catch (\Throwable) {
            return null;
        }
    }
}
