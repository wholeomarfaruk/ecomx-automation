<?php

namespace App\Livewire\Admin\Courier;

use App\Courier\CourierManager;
use App\Enums\Sales\CourierStatus;
use App\Models\Courier;
use App\Models\CourierShipment;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Shipments extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $courierFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingCourierFilter(): void
    {
        $this->resetPage();
    }

    public function syncTracking(int $shipmentId): void
    {
        $this->guardManage();

        $shipment = CourierShipment::findOrFail($shipmentId);
        $response = app(CourierManager::class)->syncTracking($shipment);

        $this->dispatch('toast', [
            'type' => $response->success ? 'success' : 'error',
            'message' => $response->success ? 'Tracking synced.' : ($response->errorMessage ?? 'Sync failed.'),
        ]);
    }

    public function cancelShipment(int $shipmentId): void
    {
        $this->guardManage();

        $shipment = CourierShipment::findOrFail($shipmentId);
        $response = app(CourierManager::class)->cancelShipment($shipment);

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
        if (! auth()->user()->can('courier_configuration.view')) {
            return abort(403, 'Unauthorized action.');
        }

        $shipments = CourierShipment::with(['order', 'courier'])
            ->when($this->search, fn ($q) => $q->where(function ($sub) {
                $sub->where('tracking_number', 'like', "%{$this->search}%")
                    ->orWhere('consignment_id', 'like', "%{$this->search}%")
                    ->orWhereHas('order', fn ($o) => $o->where('id', $this->search));
            }))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->courierFilter, fn ($q) => $q->where('courier_id', $this->courierFilter))
            ->latest()
            ->paginate(15);

        return view('livewire.admin.courier.shipments', [
            'shipments' => $shipments,
            'statuses' => CourierStatus::cases(),
            'couriers' => Courier::orderBy('sort_order')->get(),
        ])->layout('layouts.admin.admin');
    }
}
