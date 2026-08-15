<?php

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
