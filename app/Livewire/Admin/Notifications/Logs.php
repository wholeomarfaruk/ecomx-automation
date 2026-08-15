<?php

namespace App\Livewire\Admin\Notifications;

use App\Models\NotificationLog;
use Livewire\Component;
use Livewire\WithPagination;

class Logs extends Component
{
    use WithPagination;

    public string $channelFilter = '';
    public string $statusFilter = '';

    public function updatedChannelFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        if (! auth()->user()->can('notification_configuration.view')) {
            return abort(403, 'Unauthorized action.');
        }

        $logs = NotificationLog::query()
            ->when($this->channelFilter, fn ($query) => $query->where('channel', $this->channelFilter))
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->latest()
            ->paginate(20);

        return view('livewire.admin.notifications.logs', [
            'logs' => $logs,
        ])->layout('layouts.admin.admin');
    }
}
