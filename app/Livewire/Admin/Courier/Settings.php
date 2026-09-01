<?php

namespace App\Livewire\Admin\Courier;

use App\Models\Setting;
use Livewire\Component;

class Settings extends Component
{
    public bool $auto_sync_enabled = true;
    public bool $webhook_enabled = true;
    public bool $queue_shipment_creation = true;

    public function mount(): void
    {
        $this->auto_sync_enabled = (bool) Setting::get('auto_sync_enabled', true, 'courier');
        $this->webhook_enabled = (bool) Setting::get('webhook_enabled', true, 'courier');
        $this->queue_shipment_creation = (bool) Setting::get('queue_shipment_creation', true, 'courier');
    }

    public function save(): void
    {
        if (! auth()->user()->can('courier_configuration.manage')) {
            abort(403, 'Unauthorized action.');
        }

        Setting::set('auto_sync_enabled', $this->auto_sync_enabled, 'courier');
        Setting::set('webhook_enabled', $this->webhook_enabled, 'courier');
        Setting::set('queue_shipment_creation', $this->queue_shipment_creation, 'courier');

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Courier settings saved.']);
    }

    public function render()
    {
        if (! auth()->user()->can('courier_configuration.view')) {
            return abort(403, 'Unauthorized action.');
        }

        return view('livewire.admin.courier.settings', [
            'webhookBaseUrl' => url('/api/webhooks/courier'),
        ])->layout('layouts.admin.admin');
    }
}
