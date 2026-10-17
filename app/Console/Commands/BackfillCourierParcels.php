<?php

namespace App\Console\Commands;

use App\Models\CourierParcel;
use App\Models\CourierTrackingEvent;
use App\Models\Order;
use App\Support\OrderIntegritySnapshot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Creates a courier_parcels row (+ one "Sent to courier" timeline event) for every order that already has a
 * courier/tracking id. Reads `orders`, writes only the two new tables. Dry-run unless --execute.
 */
class BackfillCourierParcels extends Command
{
    protected $signature = 'courier:backfill-parcels
        {--execute : Write the rows (default is a dry run that writes nothing)}
        {--chunk=200 : Orders per transaction}';

    protected $description = 'Create courier parcel rows from existing order courier data (dry run by default)';

    public function handle(): int
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('courier_parcels') || !\Illuminate\Support\Facades\Schema::hasTable('courier_tracking_events')) {
            $this->error('Run the courier tracking migrations first (php artisan migrate).');

            return self::FAILURE;
        }

        $execute = (bool) $this->option('execute');
        $this->info($execute ? 'EXECUTE mode — rows will be written to courier_parcels / courier_tracking_events.' : 'DRY RUN — nothing will be written. Add --execute to write.');

        $before = OrderIntegritySnapshot::take();
        $this->printSnapshot('Before', $before);

        $stats = ['orders_seen' => 0, 'created' => 0, 'already_had_parcel' => 0, 'assumed_steadfast' => 0, 'conflicts' => 0];

        Order::query()
            ->where(fn ($q) => $q->whereNotNull('courier_tracking_id')->where('courier_tracking_id', '!=', '')
                ->orWhere(fn ($q) => $q->whereNotNull('consignment_id')->where('consignment_id', '!=', '')))
            ->select(['id', 'invoice_id', 'order_status', 'courier_type', 'consignment_id', 'courier_tracking_id',
                'courier_tracking_code', 'courier_sent_at', 'created_at', 'updated_at'])
            ->chunkById(max(1, (int) $this->option('chunk')), function ($orders) use ($execute, &$stats) {
                $work = function () use ($orders, $execute, &$stats) {
                    foreach ($orders as $order) {
                        $stats['orders_seen']++;
                        $courier = strtolower(trim((string) $order->courier_type));
                        if ($courier === '') {
                            $courier = 'steadfast'; // same assumption the order pages make for a bare tracking id
                            $stats['assumed_steadfast']++;
                        }
                        $consignment = trim((string) ($order->consignment_id ?: $order->courier_tracking_id));

                        $existing = CourierParcel::where('courier', $courier)->where('consignment_id', $consignment)->first();
                        if ($existing) {
                            $existing->order_id && (int) $existing->order_id !== (int) $order->id
                                ? $stats['conflicts']++
                                : $stats['already_had_parcel']++;
                            continue;
                        }

                        $stats['created']++;
                        if (!$execute) {
                            continue;
                        }

                        $sentAt = $order->courier_sent_at ?? $order->created_at;
                        $status = match ((int) $order->order_status) {
                            6       => 'delivered',
                            11      => 'cancelled',
                            default => 'sent',
                        };

                        $parcel = CourierParcel::create([
                            'order_id'      => $order->id,
                            'courier'       => $courier,
                            'consignment_id'=> $consignment,
                            'tracking_code' => filled($order->courier_tracking_code) ? $order->courier_tracking_code : null,
                            'invoice'       => $order->invoice_id,
                            'status'        => $status,
                            'status_at'     => $status === 'sent' ? $sentAt : $order->updated_at,
                            'entered_at'    => $sentAt,
                            'delivered_at'  => $status === 'delivered' ? $order->updated_at : null,
                            'last_event_at' => $sentAt,
                            'link_source'   => 'backfill',
                            'is_current'    => true,
                        ]);

                        CourierTrackingEvent::create([
                            'courier_parcel_id'       => $parcel->id,
                            'order_id'                => $order->id,
                            'courier'                 => $courier,
                            'consignment_id'          => $consignment,
                            'tracking_code'           => $parcel->tracking_code,
                            'normalized_status'       => 'sent',
                            'message'                 => 'Sent to courier (from existing order data)',
                            'event_time'              => $sentAt,
                            'event_time_from_courier' => false,
                            'source'                  => 'backfill',
                            'dedupe_hash'             => hash('sha256', "backfill|{$courier}|{$consignment}"),
                        ]);
                    }
                };

                $execute ? DB::transaction($work) : $work();
            });

        $after = OrderIntegritySnapshot::take();
        $this->printSnapshot('After', $after);

        $this->table(['', 'count'], collect($stats)->map(fn ($n, $k) => [str_replace('_', ' ', $k), $n])->values()->all());
        if (!$execute) {
            $this->warn('Dry run: the "created" figure is what --execute would create. Nothing was written.');
        }

        if ($before !== $after) {
            $this->error('ORDER DATA CHANGED between before and after — investigate before continuing.');

            return self::FAILURE;
        }
        $this->info('Order data unchanged (counts and checksum identical).');

        return self::SUCCESS;
    }

    private function printSnapshot(string $label, array $s): void
    {
        $this->line(sprintf('%s: orders=%d · with courier id=%d · per status=%s · checksum=%s',
            $label, $s['orders'], $s['with_courier_id'], json_encode($s['per_order_status']), $s['checksum']));
    }
}
