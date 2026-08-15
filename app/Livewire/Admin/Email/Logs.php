<?php

namespace App\Livewire\Admin\Email;

use App\Models\EmailLog;
use Livewire\Component;
use Livewire\WithPagination;

class Logs extends Component
{
    use WithPagination;

    public string $statusFilter = '';

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        if (! auth()->user()->can('email_configuration.view')) {
            return abort(403, 'Unauthorized action.');
        }

        $logs = EmailLog::query()
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->latest()
            ->paginate(20);

        return view('livewire.admin.email.logs', [
            'logs' => $logs,
        ])->layout('layouts.admin.admin');
    }
}
