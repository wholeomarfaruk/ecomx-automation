<?php

namespace App\Livewire\Admin\Marketing;

use App\Models\Customer;
use App\Models\Device;
use App\Models\Marketing\MarketingEvent;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin.admin')]
class JourneyExplorer extends Component
{
    /** Which sub-view this route represents: visitors | customers | anonymous. */
    public string $mode = 'visitors';

    public string $query = '';

    public function mount(string $mode = 'visitors'): void
    {
        $this->mode = $mode;
    }

    /**
     * Device IDs that have at least one marketing_events row — used both to
     * scope the "recent activity" default list and the search results, so
     * this screen never surfaces a device/customer with nothing to show on
     * its detail page.
     */
    private function trackedDeviceIds()
    {
        return MarketingEvent::query()->whereNotNull('device_id')->distinct()->pluck('device_id');
    }

    private function trackedCustomerIds()
    {
        return MarketingEvent::query()->whereNotNull('customer_id')->distinct()->pluck('customer_id');
    }

    /**
     * With no search query, show the most recently active devices/customers
     * as a starting point — searching by an opaque device fingerprint isn't
     * something an admin can realistically do from memory, so the page
     * must never be a blank box waiting for exact input.
     */
    private function results(): array
    {
        $devices = collect();
        $customers = collect();

        if ($this->mode !== 'customers') {
            $devices = Device::query()
                ->whereIn('id', $this->trackedDeviceIds())
                ->when($this->mode === 'anonymous', fn ($q) => $q->whereNull('customer_id'))
                ->when($this->query !== '', fn ($q) => $q->where(fn ($w) => $w
                    ->where('fingerprint', 'like', "%{$this->query}%")
                    ->orWhere('ip_address', 'like', "%{$this->query}%")
                    ->orWhere('device_type', 'like', "%{$this->query}%")
                    ->orWhere('browser', 'like', "%{$this->query}%")))
                ->orderByDesc('last_active_at')
                ->limit(25)
                ->get();
        }

        if ($this->mode !== 'anonymous') {
            $customers = Customer::query()
                ->whereIn('id', $this->trackedCustomerIds())
                ->when($this->query !== '', fn ($q) => $q->where(fn ($w) => $w
                    ->where('full_name', 'like', "%{$this->query}%")
                    ->orWhere('phone', 'like', "%{$this->query}%")
                    ->orWhere('email', 'like', "%{$this->query}%")))
                ->orderByDesc('updated_at')
                ->limit(25)
                ->get();
        }

        return ['devices' => $devices, 'customers' => $customers];
    }

    public function render()
    {
        $results = $this->results();

        $modeCopy = match ($this->mode) {
            'customers' => ['title' => 'Customer Journeys', 'placeholder' => 'Search by name, phone, or email…'],
            'anonymous' => ['title' => 'Anonymous Visitors', 'placeholder' => 'Search by fingerprint, IP, or device type…'],
            default => ['title' => 'Visitor Journeys', 'placeholder' => 'Search by device, IP, customer name, phone, or email…'],
        };

        return view('livewire.admin.marketing.journey-explorer', [
            'title' => $modeCopy['title'],
            'placeholder' => $modeCopy['placeholder'],
            'devices' => $results['devices'],
            'customers' => $results['customers'],
            'trackedDeviceCount' => $this->mode !== 'customers' ? $this->trackedDeviceIds()->count() : 0,
            'trackedCustomerCount' => $this->mode !== 'anonymous' ? $this->trackedCustomerIds()->count() : 0,
        ]);
    }
}
