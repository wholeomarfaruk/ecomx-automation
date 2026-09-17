<?php

namespace App\Livewire\Admin\Courier;

use App\Models\Courier;
use App\Models\Setting;
use Livewire\Component;

class Settings extends Component
{
    public bool $auto_sync_enabled = true;
    public bool $webhook_enabled = true;
    public bool $queue_shipment_creation = true;

    /** @var array<int, string> courier_id => cod_fee_rate input */
    public array $codFeeRates = [];

    public function mount(): void
    {
        $this->auto_sync_enabled = (bool) Setting::get('auto_sync_enabled', true, 'courier');
        $this->webhook_enabled = (bool) Setting::get('webhook_enabled', true, 'courier');
        $this->queue_shipment_creation = (bool) Setting::get('queue_shipment_creation', true, 'courier');

        foreach (Courier::all() as $courier) {
            $this->codFeeRates[$courier->id] = (string) $courier->cod_fee_rate;
        }
    }

    /**
     * How much of the COD amount a courier charges as their collection fee
     * — used to compute an order's total courier cost (delivery fee + COD
     * fee) when a shipment is booked. Saved per-courier since each courier
     * sets its own rate (Pathao is 1% today, others may differ or charge
     * nothing).
     */
    public function saveCodFeeRate(int $courierId): void
    {
        if (! auth()->user()->can('courier_configuration.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $rate = (float) ($this->codFeeRates[$courierId] ?? 0);

        $courier = Courier::findOrFail($courierId);
        $courier->update(['cod_fee_rate' => max(0, $rate)]);

        $this->dispatch('toast', ['type' => 'success', 'message' => "COD fee rate saved for {$courier->name}."]);
    }

    /** No courier in this system issues its own webhook secret — this app generates one so the endpoint can reject forged requests. See Courier::generateWebhookSecret(). */
    public function generateSecret(int $courierId): void
    {
        if (! auth()->user()->can('courier_configuration.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $courier = Courier::findOrFail($courierId);
        $courier->generateWebhookSecret();

        $this->dispatch('toast', ['type' => 'success', 'message' => "Webhook secret generated for {$courier->name}. Update the callback URL in their merchant panel."]);
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
            'couriers' => Courier::orderBy('sort_order')->get(),
        ])->layout('layouts.admin.admin');
    }
}
