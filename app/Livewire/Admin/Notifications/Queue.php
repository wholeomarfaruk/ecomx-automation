<?php

namespace App\Livewire\Admin\Notifications;

use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Queue extends Component
{
    public bool $queue_enabled = true;
    public int $retry_count = 3;
    public int $retry_delay = 60;
    public int $timeout = 30;

    public function mount(): void
    {
        $this->queue_enabled = (bool) Setting::get('queue_enabled', true, 'notifications');
        $this->retry_count = (int) Setting::get('retry_count', 3, 'notifications');
        $this->retry_delay = (int) Setting::get('retry_delay', 60, 'notifications');
        $this->timeout = (int) Setting::get('timeout', 30, 'notifications');
    }

    protected function guardManage(): void
    {
        if (! auth()->user()->can('notification_configuration.manage')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function save(): void
    {
        $this->guardManage();

        Setting::set('queue_enabled', $this->queue_enabled, 'notifications');
        Setting::set('retry_count', $this->retry_count, 'notifications');
        Setting::set('retry_delay', $this->retry_delay, 'notifications');
        Setting::set('timeout', $this->timeout, 'notifications');

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Queue settings saved.']);
    }

    public function retryFailed(): void
    {
        $this->guardManage();

        Artisan::call('queue:retry', ['id' => ['all']]);

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Failed jobs re-queued.']);
    }

    public function render()
    {
        if (! auth()->user()->can('notification_configuration.view')) {
            return abort(403, 'Unauthorized action.');
        }

        $failedJobs = collect();

        if (config('queue.default') === 'database') {
            try {
                $failedJobs = DB::table('failed_jobs')
                    ->where('payload', 'like', '%SendNotificationChannelJob%')
                    ->latest('failed_at')
                    ->get();
            } catch (\Throwable $e) {
                $failedJobs = collect();
            }
        }

        return view('livewire.admin.notifications.queue', [
            'failedJobs' => $failedJobs,
        ])->layout('layouts.admin.admin');
    }
}
