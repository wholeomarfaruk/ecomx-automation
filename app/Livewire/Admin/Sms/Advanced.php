<?php

namespace App\Livewire\Admin\Sms;

use App\Models\SmsGatewayConfig;
use App\Models\SmsLog;
use App\Sms\SmsManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Advanced extends Component
{
    public function retryFailed(): void
    {
        if (! auth()->user()->can('sms_configuration.manage')) {
            abort(403, 'Unauthorized action.');
        }

        Artisan::call('queue:retry', ['id' => ['all']]);

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Failed jobs re-queued.']);
    }

    public function render()
    {
        if (! auth()->user()->can('sms_configuration.view')) {
            return abort(403, 'Unauthorized action.');
        }

        // Usage Analytics
        $dailyTrend = SmsLog::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total, SUM(status IN ("sent", "delivered")) as sent, SUM(status = "failed") as failed')
            ->where('created_at', '>=', now()->subDays(14))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $byGateway = SmsLog::query()
            ->select('driver_key', DB::raw('COUNT(*) as total'))
            ->groupBy('driver_key')
            ->get();

        // Queue Monitor
        $pending = null;
        $failedJobs = collect();

        if (config('queue.default') === 'database') {
            try {
                $pending = DB::table('jobs')->where('payload', 'like', '%SendSmsJob%')->count();
                $failedJobs = DB::table('failed_jobs')->where('payload', 'like', '%SendSmsJob%')->get();
            } catch (\Throwable $e) {
                $pending = null;
                $failedJobs = collect();
            }
        }

        // Developer Info
        $driverKey = SmsGatewayConfig::active()?->driver_key ?? config('sms.default');
        $manager = app(SmsManager::class);
        $meta = collect($manager->installedGateways())->firstWhere('key', $driverKey);
        $features = $meta ? $manager->driverFor($driverKey)->supportedFeatures() : [];

        return view('livewire.admin.sms.advanced', [
            'dailyTrend' => $dailyTrend,
            'byGateway' => $byGateway,
            'pending' => $pending,
            'failedJobs' => $failedJobs,
            'meta' => $meta,
            'features' => $features,
        ])->layout('layouts.admin.admin');
    }
}
