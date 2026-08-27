<?php

namespace App\Livewire\Admin\Marketing;

use App\Marketing\Enums\MarketingEventName;
use App\Models\Marketing\MarketingEvent;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin.admin')]
class EventExplorer extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    #[Url]
    public string $search = '';

    #[Url]
    public string $eventName = '';

    #[Url]
    public string $source = '';

    #[Url]
    public string $campaign = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    /** Deep-link filters — set when arriving from a device or customer profile, not exposed as visible filter controls (the URL itself is the "filter"). */
    #[Url]
    public ?int $deviceId = null;

    #[Url]
    public ?int $customerId = null;

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingEventName(): void { $this->resetPage(); }
    public function updatingSource(): void { $this->resetPage(); }
    public function updatingDateFrom(): void { $this->resetPage(); }
    public function updatingDateTo(): void { $this->resetPage(); }

    public function resetFilters(): void
    {
        $this->reset(['search', 'eventName', 'source', 'campaign', 'dateFrom', 'dateTo', 'deviceId', 'customerId']);
        $this->resetPage();
    }

    public function render()
    {
        $events = MarketingEvent::query()
            ->with(['device', 'customer'])
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('event_id', 'like', "%{$this->search}%")
                ->orWhere('content_name', 'like', "%{$this->search}%")
                ->orWhere('page_url', 'like', "%{$this->search}%")
                ->orWhereHas('customer', fn ($c) => $c->where('full_name', 'like', "%{$this->search}%"))
            ))
            ->when($this->eventName !== '', fn ($q) => $q->where('event_name', $this->eventName))
            ->when($this->source !== '', fn ($q) => $q->where('utm_source', $this->source))
            ->when($this->campaign !== '', fn ($q) => $q->where('utm_campaign', $this->campaign))
            ->when($this->deviceId, fn ($q) => $q->where('device_id', $this->deviceId))
            ->when($this->customerId, fn ($q) => $q->where('customer_id', $this->customerId))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('occurred_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('occurred_at', '<=', $this->dateTo))
            ->orderByDesc('occurred_at')
            ->paginate(25);

        $sources = MarketingEvent::query()
            ->whereNotNull('utm_source')
            ->distinct()
            ->orderBy('utm_source')
            ->pluck('utm_source');

        return view('livewire.admin.marketing.event-explorer', [
            'events' => $events,
            'eventNames' => MarketingEventName::cases(),
            'sources' => $sources,
            'totalCount' => MarketingEvent::count(),
        ]);
    }
}
