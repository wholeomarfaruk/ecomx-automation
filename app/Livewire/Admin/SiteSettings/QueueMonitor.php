<?php

namespace App\Livewire\Admin\SiteSettings;

use App\Services\QueueHealthService;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;

/**
 * Live status panel at the top of Site Settings → Queue. A separate
 * component so its wire:poll refreshes only this panel, not the whole
 * settings form.
 */
class QueueMonitor extends Component
{
    public function sendTestJob(): void
    {
        QueueHealthService::dispatchTestJob();

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Test job sent — watch for it to be processed']);
    }

    public function retryFailed(string $key): void
    {
        Artisan::call('queue:retry', ['id' => [$key]]);

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Job pushed back onto the queue']);
    }

    public function forgetFailed(string $key): void
    {
        Artisan::call('queue:forget', ['id' => $key]);

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Failed job deleted']);
    }

    public function retryAllFailed(): void
    {
        Artisan::call('queue:retry', ['id' => ['all']]);

        $this->log('All failed queue jobs were retried');
        $this->dispatch('toast', ['type' => 'success', 'message' => 'All failed jobs pushed back onto the queue']);
    }

    public function flushFailed(): void
    {
        Artisan::call('queue:flush');

        $this->log('All failed queue jobs were deleted');
        $this->dispatch('toast', ['type' => 'success', 'message' => 'All failed jobs deleted']);
    }

    private function log(string $description): void
    {
        activity('settings')
            ->causedBy(auth()->user())
            ->event('updated')
            ->log($description);
    }

    public function render()
    {
        return view('livewire.admin.site-settings.queue-monitor', [
            'health' => QueueHealthService::snapshot(),
            'staleAfterMinutes' => intdiv(QueueHealthService::WORKER_STALE_AFTER_SECONDS, 60),
        ]);
    }
}
