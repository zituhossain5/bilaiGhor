<?php

namespace App\Console\Commands;

use App\Services\Steadfast\PoliceStationSync;
use Illuminate\Console\Command;

class SyncSteadfastAreas extends Command
{
    protected $signature = 'steadfast:sync-areas
        {--rematch : Recompute automatic matches too (admin choices are always kept)}';

    protected $description = 'Download Steadfast police stations (read-only API call) and match them to our thanas';

    public function handle(PoliceStationSync $sync): int
    {
        $result = $sync->sync((bool) $this->option('rematch'));

        if (!$result['ok']) {
            $this->error($result['message']);

            return self::FAILURE;
        }

        $this->info("{$result['stations']} stations in {$result['districts']} districts; {$result['deactivated']} switched off.");
        $this->table(array_keys($result['match']), [array_values($result['match'])]);

        return self::SUCCESS;
    }
}
