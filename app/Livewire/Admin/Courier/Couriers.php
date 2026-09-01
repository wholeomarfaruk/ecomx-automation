<?php

namespace App\Livewire\Admin\Courier;

use App\Models\Courier;
use Livewire\Component;

class Couriers extends Component
{
    public function toggleActive(int $courierId): void
    {
        $this->guardManage();

        $courier = Courier::findOrFail($courierId);

        $hasDriver = array_key_exists($courier->driver_key, config('courier.drivers', []));

        if (! $courier->is_active && ! $hasDriver) {
            $this->dispatch('toast', ['type' => 'error', 'message' => "{$courier->name} has no driver wired up yet."]);

            return;
        }

        $courier->update(['is_active' => ! $courier->is_active]);

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => "{$courier->name} is now " . ($courier->is_active ? 'active' : 'inactive') . '.',
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

        $couriers = Courier::withCount(['accounts', 'shipments'])
            ->orderBy('sort_order')
            ->get();

        $installedDrivers = array_keys(config('courier.drivers', []));

        return view('livewire.admin.courier.couriers', [
            'couriers' => $couriers,
            'installedDrivers' => $installedDrivers,
        ])->layout('layouts.admin.admin');
    }
}
