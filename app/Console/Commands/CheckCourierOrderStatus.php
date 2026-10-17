<?php

namespace App\Console\Commands;

use App\Models\CourierParcel;
use App\Models\CronJobSetting;
use App\Models\Order;
use App\Services\CourierTracking\ParcelRecorder;
use App\Services\CourierTracking\RefreshService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckCourierOrderStatus extends Command
{
    protected $signature = 'courier:check-status {--limit=50 : Maximum parcels to check} {--force : Run even if disabled in admin}';

    protected $description = 'Check parcel status from Pathao, Steadfast, and RedX courier APIs, record changes on the tracking timeline and update order status automatically';

    public function handle(ParcelRecorder $recorder, RefreshService $refresh)
    {
        $setting = CronJobSetting::forKey('courier_status_sync');

        // Respect admin enable/disable unless --force passed
        if ($setting && !$setting->is_enabled && !$this->option('force')) {
            $this->warn("⏸  Courier status sync is DISABLED from admin panel. Use --force to override.");
            return Command::SUCCESS;
        }

        // Limit: option overrides DB, DB overrides default 50
        $limit = (int) $this->option('limit');
        if ($limit === 50 && $setting && $setting->order_limit > 0) {
            $limit = $setting->order_limit;
        }

        // Mark as running
        if ($setting) {
            $setting->update([
                'last_run_at'     => now(),
                'last_run_status' => 'running',
                'last_run_result' => 'চলছে...',
            ]);
        }

        $this->info("Courier status sync started — limit: {$limit}");

        $parcels = $this->parcelsToCheck($recorder, $limit);

        if ($parcels->isEmpty()) {
            $this->info("No pending orders found.");
            if ($setting) {
                $setting->update([
                    'last_run_status'    => 'success',
                    'last_run_result'    => 'চেক করার মতো কোনো অর্ডার নেই।',
                    'last_updated_count' => 0,
                    'last_failed_count'  => 0,
                ]);
            }
            return Command::SUCCESS;
        }

        $this->info("Found {$parcels->count()} parcels to check");

        $updated   = 0;
        $failed    = 0;
        $unchanged = 0;

        foreach ($parcels as $parcel) {
            try {
                // Records a timeline event only when the courier's status changed; the order status goes
                // through OrderStatusGate (existing mappings; final orders and older events are left alone).
                $result = $refresh->refresh($parcel, 'sync');

                if (!$result['ok']) {
                    $failed++;
                    $this->error("Parcel {$parcel->courier} {$parcel->consignment_id}: " . $result['message']);
                    continue;
                }

                if (!empty($result['order_status_changed'])) {
                    $updated++;
                    $this->info("Order #{$parcel->order_id} ({$parcel->courier}) status updated");
                    Log::info("Courier order status auto-updated", [
                        'order_id'     => $parcel->order_id,
                        'courier_type' => $parcel->courier,
                        'tracking_id'  => $parcel->consignment_id,
                        'new_status'   => $parcel->fresh()->order_status_applied,
                    ]);
                } else {
                    $unchanged++;
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->error("Error {$parcel->courier} {$parcel->consignment_id}: " . $e->getMessage());

                Log::error("Courier status check failed", [
                    'parcel_id'    => $parcel->id,
                    'order_id'     => $parcel->order_id,
                    'courier_type' => $parcel->courier,
                    'error'        => $e->getMessage(),
                ]);
            }
        }

        $resultText = "মোট চেক: {$parcels->count()} | আপডেট: {$updated} | অপরিবর্তিত: {$unchanged} | ব্যর্থ: {$failed}";
        $this->info($resultText);

        if ($setting) {
            $setting->update([
                'last_run_status'    => $failed > 0 && $updated === 0 && $unchanged === 0 ? 'failed' : 'success',
                'last_run_result'    => $resultText,
                'last_updated_count' => $updated,
                'last_failed_count'  => $failed,
            ]);
        }

        return Command::SUCCESS;
    }

    /**
     * The same orders as before (In Courier, with a Pathao/Steadfast/RedX tracking id) — their parcel rows are
     * created on the fly in courier_parcels — plus linked/unlinked parcels still on their way. Least recently
     * checked first, so a long list is covered over several runs.
     */
    private function parcelsToCheck(ParcelRecorder $recorder, int $limit)
    {
        $orders = Order::where('order_status', 5)
            ->whereNotNull('courier_type')
            ->whereNotNull('courier_tracking_id')
            ->whereIn('courier_type', ['pathao', 'steadfast', 'redx'])
            ->get();

        $ids = [];
        foreach ($orders as $order) {
            if ($parcel = $recorder->forOrder($order)) {
                $ids[] = $parcel->id;
            }
        }

        $finalStatuses = collect(config('courier_tracking.statuses'))->filter(fn ($s) => $s['final'])->keys()->all();

        return CourierParcel::query()
            ->where(function ($q) use ($ids, $finalStatuses) {
                $q->whereIn('id', $ids ?: [0])
                    ->orWhere(fn ($q) => $q->where('is_current', true)->whereNotIn('status', $finalStatuses));
            })
            ->whereIn('courier', ['pathao', 'steadfast', 'redx'])
            ->orderByRaw('last_synced_at IS NOT NULL, last_synced_at ASC')
            ->limit($limit)
            ->get();
    }
}
