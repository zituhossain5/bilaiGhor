<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\CronJobSetting;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        // NOTE (Laravel 12): this legacy Kernel is NOT loaded by the framework's
        // default bootstrap/app.php, so this method never runs. The courier-sync
        // schedule now lives in routes/console.php (wired via `commands:`), which is
        // the supported location. Kept intentionally empty to avoid a second,
        // conflicting registration if anyone ever re-binds the old console kernel.
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
