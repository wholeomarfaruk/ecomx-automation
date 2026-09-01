<?php

namespace App\Livewire\Admin\Courier;

use App\Models\CourierApiLog;
use App\Models\CourierWebhookLog;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Logs extends Component
{
    use WithPagination;

    #[Url]
    public string $type = 'api';

    #[Url]
    public string $outcome = '';

    public function updatingType(): void
    {
        $this->resetPage();
        $this->outcome = '';
    }

    public function updatingOutcome(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        if (! auth()->user()->can('courier_configuration.view')) {
            return abort(403, 'Unauthorized action.');
        }

        $apiLogs = null;
        $webhookLogs = null;

        if ($this->type === 'api') {
            $apiLogs = CourierApiLog::with('courier')
                ->when($this->outcome === 'success', fn ($q) => $q->where('success', true))
                ->when($this->outcome === 'failed', fn ($q) => $q->where('success', false))
                ->latest()
                ->paginate(20);
        } else {
            $webhookLogs = CourierWebhookLog::with('courier')
                ->when($this->outcome, fn ($q) => $q->where('status', $this->outcome))
                ->latest()
                ->paginate(20);
        }

        return view('livewire.admin.courier.logs', [
            'apiLogs' => $apiLogs,
            'webhookLogs' => $webhookLogs,
        ])->layout('layouts.admin.admin');
    }
}
