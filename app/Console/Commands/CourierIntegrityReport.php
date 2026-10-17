<?php

namespace App\Console\Commands;

use App\Support\OrderIntegritySnapshot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class CourierIntegrityReport extends Command
{
    protected $signature = 'courier:integrity-report';

    protected $description = 'Read-only: order counts, per-status counts and a checksum of order data, plus courier tracking table sizes';

    public function handle(): int
    {
        $s = OrderIntegritySnapshot::take();

        $this->info('Orders: ' . $s['orders'] . ' · with courier/tracking id: ' . $s['with_courier_id'] . ' · sum(amount): ' . $s['sum_amount']);
        $this->table(['order_status', 'orders'], collect($s['per_order_status'])->map(fn ($n, $k) => [$k, $n])->values()->all());
        $this->table(['payment_status', 'orders'], collect($s['per_payment_status'])->map(fn ($n, $k) => [$k === '' ? '(empty)' : $k, $n])->values()->all());
        $this->info('Order data checksum: ' . $s['checksum']);

        foreach (['courier_parcels', 'courier_tracking_events'] as $table) {
            $this->line($table . ': ' . (Schema::hasTable($table) ? \DB::table($table)->count() . ' rows' : 'not created yet'));
        }

        return self::SUCCESS;
    }
}
