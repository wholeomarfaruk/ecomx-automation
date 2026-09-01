<?php

namespace App\Livewire\Admin\Courier;

use App\Courier\CourierManager;
use App\Models\CourierShipment;
use Livewire\Component;

class ShipmentDetail extends Component
{
    public CourierShipment $shipment;

    public function mount(int $shipment): void
    {
        if (! auth()->user()->can('courier_configuration.view')) {
            abort(403, 'Unauthorized action.');
        }

        $this->shipment = CourierShipment::findOrFail($shipment);
    }

    public function syncTracking(): void
    {
        $this->guardManage();

        $response = app(CourierManager::class)->syncTracking($this->shipment);

        $this->shipment->refresh();

        $this->dispatch('toast', [
            'type' => $response->success ? 'success' : 'error',
            'message' => $response->success ? 'Tracking synced.' : ($response->errorMessage ?? 'Sync failed.'),
        ]);
    }

    public function cancelShipment(): void
    {
        $this->guardManage();

        $response = app(CourierManager::class)->cancelShipment($this->shipment);

        $this->shipment->refresh();

        $this->dispatch('toast', [
            'type' => $response->success ? 'success' : 'error',
            'message' => $response->success ? 'Shipment cancelled.' : ($response->errorMessage ?? 'Cancellation failed.'),
        ]);
    }

    protected function guardManage(): void
    {
        if (! auth()->user()->can('courier_configuration.manage')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function render()
    {
        $this->shipment->load(['order', 'courier', 'courierAccount', 'trackingEvents']);

        return view('livewire.admin.courier.shipment-detail')
            ->layout('layouts.admin.admin');
    }
}
