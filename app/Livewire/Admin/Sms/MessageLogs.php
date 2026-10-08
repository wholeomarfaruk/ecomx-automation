<?php

namespace App\Livewire\Admin\Sms;

use App\Models\SmsLog;
use Livewire\Component;
use Livewire\WithPagination;

class MessageLogs extends Component
{
    use WithPagination;

    public string $statusFilter = '';

    public bool $drawerOpen = false;
    public ?int $viewLogId = null;

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function viewLog(int $id): void
    {
        $this->viewLogId = $id;
        $this->drawerOpen = true;
    }

    public function closeDrawer(): void
    {
        $this->drawerOpen = false;
        $this->viewLogId = null;
    }

    public function render()
    {
        if (! auth()->user()->can('sms_configuration.view')) {
            return abort(403, 'Unauthorized action.');
        }

        $logs = SmsLog::query()
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->latest()
            ->paginate(20);

        return view('livewire.admin.sms.message-logs', [
            'logs' => $logs,
            'viewingLog' => $this->drawerOpen && $this->viewLogId ? SmsLog::find($this->viewLogId) : null,
        ])->layout('layouts.admin.admin');
    }
}
