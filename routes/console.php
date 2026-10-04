<?php

use App\Jobs\SyncCourierShipmentJob;
use App\Models\CourierShipment;
use App\Models\RequestLog;
use App\Services\LicenseService;
use App\Services\UpdateService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('scheduler:heartbeat', function () {
    Cache::put('scheduler_last_ran_at', now());
})->purpose('Record a heartbeat used to verify the scheduler is running')
    ->everyMinute();

// With QUEUE_CONNECTION=database every queued job (Meta CAPI delivery, SMS,
// courier, notifications) just sits in the jobs table until a worker runs.
// Hosts without Supervisor only have the schedule:run cron, so drain the
// queue from here each minute; a Supervisor worker can run alongside safely.
Schedule::command('queue:work --queue=default,meta --stop-when-empty --max-time=55 --sleep=3')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->runInBackground();

Artisan::command('license:check', function () {
    app(LicenseService::class)->check();
})->purpose('Re-validate the license with the remote server')
    ->daily();

Artisan::command('app:auto-update', function () {
    app(UpdateService::class)->run();
})->purpose('Check for and automatically apply application updates')
    ->hourly()
    ->withoutOverlapping();

Artisan::command('courier:sync-tracking', function () {
    // Webhooks are the primary source of truth (near-instant); this is the
    // fallback for couriers with no/unreliable webhook and for any shipment
    // that hasn't heard back in a while. Never touches final states.
    $shipments = CourierShipment::whereNotIn('status', ['delivered', 'cancelled', 'returned'])
        ->whereNotNull('tracking_number')
        ->get();

    foreach ($shipments as $shipment) {
        SyncCourierShipmentJob::dispatch($shipment->id);
    }

    $this->comment("Queued tracking sync for {$shipments->count()} shipment(s).");
})->purpose('Sync tracking status for every non-final courier shipment')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

Artisan::command('request-logs:prune', function () {
    // Visits Url request log — keeps config('request-logs.retention_days').
    $this->call('model:prune', ['--model' => [RequestLog::class]]);
})->purpose('Delete request_logs rows past their retention window')
    ->daily()
    ->withoutOverlapping();
