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

// Accounts Panel: post-dated vouchers post themselves on their date
// (when every check passes; otherwise they wait in Posting Pending).
Artisan::command('accounts:post-scheduled', function () {
    [$posted, $skipped] = app(\App\Services\Accounts\Vouchers::class)->runScheduled();
    $this->info("Scheduled vouchers: {$posted} posted, {$skipped} waiting.");
})->purpose('Post scheduled Accounts Panel vouchers whose date has come')
    ->hourlyAt(5)
    ->withoutOverlapping();

// Accounts Panel: recurring bills (rent, electricity…) become expense drafts
// on their day; they wait in Posting Pending for approval.
Artisan::command('accounts:recurring-drafts', function () {
    $made = app(\App\Services\Accounts\PayablesSchedule::class)->draftDueRecurring();
    $this->info("Recurring bills: {$made} draft(s) created.");
})->purpose('Create Accounts Panel drafts for recurring bills that are due')
    ->dailyAt('06:00')
    ->withoutOverlapping();

// Accounts Panel: kisti reminders (1 day before + on the due date) and the
// late rule of overdue customer kisti (late fee draft / block new credit).
Artisan::command('accounts:kisti-daily', function () {
    $r = app(\App\Services\Accounts\KistiPlans::class)->runDaily();
    $this->info("Kisti: {$r['sms']} SMS, {$r['fees']} late fee draft(s), {$r['blocked']} blocked.");
})->purpose('Kisti reminders and late rules for the Accounts Panel')
    ->dailyAt('09:00')
    ->withoutOverlapping();

// Accounts Panel: month-end depreciation draft for assets in use (skipped
// when the month's run already exists).
Artisan::command('accounts:depreciation-draft', function () {
    try {
        $entry = app(\App\Services\Accounts\FixedAssets::class)->run(\Carbon\CarbonImmutable::today(), true);
        $this->info("Depreciation draft {$entry->ref()} created.");
    } catch (\RuntimeException $e) {
        $this->info($e->getMessage());
    }
})->purpose('Create the monthly depreciation draft for the Accounts Panel')
    ->lastDayOfMonth('22:00')
    ->withoutOverlapping();

// Accounts Panel: month-end commission / rebate accrual drafts
// (Dr Sales rebate or Commission expense / Cr Rebate payable).
Artisan::command('accounts:commission-accrual', function () {
    $made = 0;
    foreach (\App\Models\CommissionScheme::with('tiers')->where('status', 'active')->get() as $scheme) {
        $made += (int) (bool) rescue(fn () => app(\App\Services\Accounts\Commission::class)->accrue($scheme, \Carbon\CarbonImmutable::today(), true));
    }
    $this->info("Commission accrual: {$made} draft(s).");
})->purpose('Month-end commission accrual drafts for the Accounts Panel')
    ->lastDayOfMonth('22:30')
    ->withoutOverlapping();

// Accounts Panel: saved reports on their schedule (queued: archive + delivery).
foreach (['daily' => fn ($e) => $e->dailyAt('21:00'), 'weekly' => fn ($e) => $e->weeklyOn(6, '10:00'), 'monthly' => fn ($e) => $e->monthlyOn(1, '10:00'), 'month_end' => fn ($e) => $e->lastDayOfMonth('23:55')] as $slot => $when) {
    $when(Schedule::call(fn () => app(\App\Services\Accounts\SavedReportRunner::class)->dispatchDue($slot))->name("accounts:saved-reports-{$slot}")->withoutOverlapping());
}

// Accounts Panel: an open day closes itself at 23:59 (status "auto").
Artisan::command('accounts:auto-close-day', function () {
    try {
        app(\App\Services\Accounts\DailyReport::class)->close(\Carbon\CarbonImmutable::today(), null, true);
        $this->info('Day auto-closed.');
    } catch (\RuntimeException $e) {
        $this->info($e->getMessage());
    }
})->purpose('Auto-close the Accounts Panel day at 23:59')
    ->dailyAt('23:59')
    ->withoutOverlapping();

// Accounts Panel: delivered / returned orders post themselves every hour when
// "Auto-post" is on (Posting Pending › Orders). Blocked orders are left alone.
Artisan::command('accounts:post-orders', function () {
    [$posted, $skipped] = app(\App\Services\Accounts\OrderPosting::class)->runAuto();
    $this->info("Orders posted: {$posted}" . ($skipped ? ' · skipped ' . count($skipped) : ''));
})->purpose('Auto-post delivered / returned orders to the Accounts Panel')
    ->hourlyAt(15)
    ->withoutOverlapping();
