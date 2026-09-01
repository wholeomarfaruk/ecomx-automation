<?php

use App\Jobs\SyncCourierShipmentJob;
use App\Models\CourierShipment;
use App\Services\LicenseService;
use App\Services\UpdateService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('scheduler:heartbeat', function () {
    Cache::put('scheduler_last_ran_at', now());
})->purpose('Record a heartbeat used to verify the scheduler is running')
    ->everyMinute();

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
