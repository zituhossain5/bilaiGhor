<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\CronJobSetting;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks (Laravel 12)
|--------------------------------------------------------------------------
| In Laravel 11/12 the legacy App\Console\Kernel::schedule() is no longer
| invoked, so the schedule MUST be defined here (bootstrap/app.php wires this
| file as `commands:`). This is what makes `php artisan schedule:run` — driven
| by a once-per-minute server cron — actually fire the courier sync.
|
| Frequency / enable / limit are read live from the admin Cron Job panel
| (cron_job_settings), so changing them there needs no code change.
*/
try {
    $courierCron = CronJobSetting::forKey('courier_status_sync');
} catch (\Throwable $e) {
    // Table may not exist yet (fresh install / mid-migration) — fall back to defaults.
    $courierCron = null;
}

$courierEnabled = $courierCron ? (bool) $courierCron->is_enabled          : true;
$courierFreq    = $courierCron ? (int) $courierCron->frequency_minutes    : 10;
$courierLimit   = $courierCron ? (int) $courierCron->order_limit           : 50;

if ($courierEnabled) {
    $job = Schedule::command("courier:check-status --limit={$courierLimit}")
        ->withoutOverlapping()   // never run two batches at once
        ->runInBackground();

    match (true) {
        $courierFreq <= 1  => $job->everyMinute(),
        $courierFreq <= 2  => $job->everyTwoMinutes(),
        $courierFreq <= 5  => $job->everyFiveMinutes(),
        $courierFreq <= 10 => $job->everyTenMinutes(),
        $courierFreq <= 15 => $job->everyFifteenMinutes(),
        $courierFreq <= 30 => $job->everyThirtyMinutes(),
        $courierFreq <= 60 => $job->hourly(),
        default            => $job->everyTwoHours(),
    };
}
