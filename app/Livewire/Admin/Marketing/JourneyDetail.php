<?php

namespace App\Livewire\Admin\Marketing;

use App\Marketing\Enums\MarketingEventName;
use App\Models\Customer;
use App\Models\Device;
use App\Models\Marketing\MarketingEvent;
use App\Models\Order;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.admin.admin')]
class JourneyDetail extends Component
{
    #[Url]
    public ?int $deviceId = null;

    #[Url]
    public ?int $customerId = null;

    /** overview | timeline | attribution */
    public string $tab = 'overview';

    public function mount(): void
    {
        if (! $this->deviceId && ! $this->customerId) {
            abort(404);
        }
    }

    private function baseQuery()
    {
        return MarketingEvent::query()
            ->when($this->deviceId, fn ($q) => $q->where('device_id', $this->deviceId))
            ->when($this->customerId, fn ($q) => $q->where('customer_id', $this->customerId));
    }

    private function stats(): array
    {
        $events = $this->baseQuery();

        $counts = (clone $events)
            ->selectRaw('event_name, COUNT(*) as total')
            ->groupBy('event_name')
            ->pluck('total', 'event_name');

        $purchases = (clone $events)->where('event_name', MarketingEventName::PURCHASE->value);

        // min()/max() return raw strings from the aggregate query, not
        // Carbon instances (Eloquent only casts attributes on hydrated
        // models, and these bypass that) — the view calls ->format()/
        // ->diffForHumans() on them, so they're parsed back into Carbon here.
        $firstSeen = (clone $events)->min('occurred_at');
        $lastSeen = (clone $events)->max('occurred_at');

        return [
            'first_seen' => $firstSeen ? \Illuminate\Support\Carbon::parse($firstSeen) : null,
            'last_seen' => $lastSeen ? \Illuminate\Support\Carbon::parse($lastSeen) : null,
            'total_events' => (clone $events)->count(),
            'page_views' => (int) ($counts[MarketingEventName::PAGE_VIEW->value] ?? 0),
            'product_views' => (int) ($counts[MarketingEventName::VIEW_CONTENT->value] ?? 0),
            'add_to_cart' => (int) ($counts[MarketingEventName::ADD_TO_CART->value] ?? 0),
            'checkouts' => (int) ($counts[MarketingEventName::INITIATE_CHECKOUT->value] ?? 0),
            'purchases' => (clone $purchases)->count(),
            'revenue' => (float) (clone $purchases)->sum('value'),
            'distinct_sessions' => (clone $events)->whereNotNull('session_id')->distinct('session_id')->count('session_id'),
        ];
    }

    /** Timeline grouped into visits (30+ min gap = new visit). */
    private function timeline(): array
    {
        $events = $this->baseQuery()->with(['device', 'customer', 'attribution', 'items'])->orderBy('occurred_at')->get();

        $visits = [];
        $current = null;
        $lastTime = null;

        foreach ($events as $event) {
            if (! $lastTime || $event->occurred_at->diffInMinutes($lastTime) > 30) {
                if ($current) {
                    $visits[] = $current;
                }
                $current = ['started_at' => $event->occurred_at, 'events' => []];
            }

            $current['events'][] = $event;
            $lastTime = $event->occurred_at;
        }

        if ($current) {
            $visits[] = $current;
        }

        return array_reverse($visits);
    }

    /** All distinct attribution touches recorded across this profile's events, most recent first. */
    private function attributionHistory(): Collection
    {
        return $this->baseQuery()
            ->with('attribution')
            ->whereHas('attribution')
            ->orderByDesc('occurred_at')
            ->get()
            ->pluck('attribution')
            ->filter();
    }

    /** Last 5 events, most recent first — a compact preview, not the full Timeline tab replay. */
    private function recentActivity(): Collection
    {
        return $this->baseQuery()
            ->orderByDesc('occurred_at')
            ->limit(5)
            ->get();
    }

    /** The single most recent attribution snapshot — "what's driving this profile right now", vs the full history table on the Attribution tab. */
    private function currentAttribution(): ?\App\Models\Marketing\MarketingAttribution
    {
        return $this->baseQuery()
            ->with('attribution')
            ->whereHas('attribution')
            ->orderByDesc('occurred_at')
            ->first()
            ?->attribution;
    }

    /** Products viewed most often (by content_name), across ViewContent/AddToCart/Purchase events. */
    private function topProducts(): Collection
    {
        return $this->baseQuery()
            ->whereNotNull('content_name')
            ->selectRaw('content_name, COUNT(*) as total')
            ->groupBy('content_name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
    }

    /** Real orders for this customer — bridges marketing tracking to actual commerce data. Anonymous devices have no order relationship, so this is customer-only. */
    private function orders(): Collection
    {
        if (! $this->customerId) {
            return collect();
        }

        return Order::where('customer_id', $this->customerId)
            ->latest()
            ->limit(10)
            ->get();
    }

    /**
     * All devices actually owned by this customer — via devices.customer_id,
     * the real FK (same source of truth as Admin > Users > Device tab, see
     * UserDetail::render()). Deliberately NOT inferred from
     * marketing_events.device_id where customer_id matches: an event's
     * customer_id reflects who was logged in at that moment, which is not
     * proof the device itself belongs to that customer, and demo/seeded
     * event data in particular can carry a customer_id with no matching
     * devices.customer_id at all.
     */
    private function linkedDevices(): Collection
    {
        if (! $this->customerId) {
            return collect();
        }

        return Device::where('customer_id', $this->customerId)->get();
    }

    public function render()
    {
        $device = $this->deviceId ? Device::with('customer')->find($this->deviceId) : null;
        $customer = $this->customerId ? Customer::find($this->customerId) : null;

        if (! $device && ! $customer) {
            abort(404);
        }

        return view('livewire.admin.marketing.journey-detail', [
            'device' => $device,
            'customer' => $customer,
            'stats' => $this->stats(),
            'visits' => $this->tab === 'timeline' ? $this->timeline() : [],
            'attributionHistory' => $this->tab === 'attribution' ? $this->attributionHistory() : collect(),
            'linkedDevices' => $this->linkedDevices(),
            'recentActivity' => $this->tab === 'overview' ? $this->recentActivity() : collect(),
            'currentAttribution' => $this->tab === 'overview' ? $this->currentAttribution() : null,
            'topProducts' => $this->tab === 'overview' ? $this->topProducts() : collect(),
            'orders' => $this->tab === 'overview' ? $this->orders() : collect(),
        ]);
    }
}
