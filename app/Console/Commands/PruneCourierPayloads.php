<?php

namespace App\Console\Commands;

use App\Models\CourierTrackingEvent;
use Illuminate\Console\Command;

class PruneCourierPayloads extends Command
{
    protected $signature = 'courier:prune-payloads';

    protected $description = 'Clear raw courier payloads older than courier_tracking.payload_retention_days (the timeline events stay)';

    public function handle(): int
    {
        $days = max(1, (int) config('courier_tracking.payload_retention_days', 90));

        $cleared = CourierTrackingEvent::whereNotNull('raw_payload')
            ->where('created_at', '<', now()->subDays($days))
            ->update(['raw_payload' => null]);

        $this->info("Cleared raw payloads on {$cleared} tracking events older than {$days} days.");

        return self::SUCCESS;
    }
}
