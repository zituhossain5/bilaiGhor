<?php

namespace App\Services\CourierTracking;

use App\Models\CourierParcel;
use App\Services\CourierDispatchService;
use Illuminate\Support\Facades\Cache;

/**
 * Ask the courier for a parcel's state now and store what changed. Shared by the "Refresh" button,
 * bulk refresh and the 10-minute courier:check-status job. One lookup per parcel per cooldown window.
 */
class RefreshService
{
    public function __construct(
        private readonly CourierReader $reader,
        private readonly ParcelRecorder $recorder,
        private readonly OrderStatusGate $gate,
    ) {}

    /**
     * @param  string  $source  manual_refresh | sync
     * @return array{ok: bool, throttled?: bool, message: string, order_status_changed?: bool, new_events?: int}
     */
    public function refresh(CourierParcel $parcel, string $source = 'manual_refresh'): array
    {
        $label = CourierDispatchService::label($parcel->courier);
        $cooldown = max(1, (int) config('courier_tracking.refresh_cooldown', 120));

        if (!Cache::add('courier-refresh:' . $parcel->id, now()->timestamp, $cooldown)) {
            return ['ok' => true, 'throttled' => true, 'message' => "Checked with {$label} less than " . ceil($cooldown / 60) . ' min ago'];
        }

        $result = $this->reader->read($parcel);

        if (!$result['ok']) {
            $parcel->forceFill(['last_sync_error' => mb_substr((string) ($result['error'] ?? 'Unknown error'), 0, 500), 'last_sync_error_at' => now()])->save();
            $since = $parcel->last_synced_at ?? $parcel->last_event_at;

            return [
                'ok'      => false,
                'message' => "Couldn't reach {$label} — showing data from "
                    . ($since ? $since->timezone(config('app.timezone'))->format('d M Y, h:i A') : 'before'),
            ];
        }

        $new = 0;
        foreach ($result['history'] ?? [] as $row) {
            $new += $this->recorder->record($parcel, [
                'message'    => $row['message'] ?? null,
                'event_time' => $row['event_time'] ?? null,
                'source'     => 'api_history',
                'payload'    => $row['payload'] ?? null,
            ]) ? 1 : 0;
        }

        $raw = $result['raw_status'] ?? null;
        $eventTime = ParcelRecorder::time($result['event_time'] ?? null) ?? now();
        if ($raw !== null) {
            $new += $this->recorder->record($parcel, [
                'raw_status' => $raw,
                'event_time' => $result['event_time'] ?? null,
                'source'     => $source,
                'payload'    => ['raw_status' => $raw, 'checked_at' => now()->toIso8601String()],
                'details'    => $result['details'] ?? [],
            ]) ? 1 : 0;
        }

        $this->recorder->updateDetails($parcel, $result['details'] ?? []);
        $parcel->forceFill(['last_synced_at' => now(), 'last_sync_error' => null, 'last_sync_error_at' => null])->save();

        $changed = $raw !== null
            && $this->gate->apply($parcel, $raw, $eventTime, $label . ($source === 'sync' ? ' Cron' : ' Refresh'));

        return ['ok' => true, 'message' => "Updated from {$label}", 'order_status_changed' => $changed, 'new_events' => $new];
    }
}
