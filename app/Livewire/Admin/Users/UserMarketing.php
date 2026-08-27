<?php

namespace App\Livewire\Admin\Users;

use App\Marketing\Enums\MarketingEventName;
use App\Models\Device;
use App\Models\Marketing\MarketingEvent;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Embedded "Marketing" tab inside Admin > Users > User Details. Scoped to
 * this one customer/user's tracked devices — a narrower, profile-local
 * cousin of App\Livewire\Admin\Marketing\JourneyDetail (which is the
 * standalone full-page version reachable from the Marketing module).
 */
class UserMarketing extends Component
{
    public ?int $customerId = null;

    public ?int $userId = null;

    /** @var array<int> */
    public array $deviceIds = [];

    /** overview | timeline | attribution | sources | campaigns | devices */
    public string $subTab = 'overview';

    private function baseQuery()
    {
        return MarketingEvent::query()
            ->where(function ($q) {
                if ($this->customerId) {
                    $q->orWhere('customer_id', $this->customerId);
                }
                if (! empty($this->deviceIds)) {
                    $q->orWhereIn('device_id', $this->deviceIds);
                }
            });
    }

    private function stats(): array
    {
        $events = $this->baseQuery();

        $counts = (clone $events)
            ->selectRaw('event_name, COUNT(*) as total')
            ->groupBy('event_name')
            ->pluck('total', 'event_name');

        $purchases = (clone $events)->where('event_name', MarketingEventName::PURCHASE->value);

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
            'distinct_devices' => (clone $events)->distinct('device_id')->count('device_id'),
        ];
    }

    /** Timeline grouped into visits (30+ min gap = new visit), most recent first. */
    private function timeline(): array
    {
        $events = $this->baseQuery()->with(['device', 'attribution', 'items'])->orderBy('occurred_at')->limit(500)->get();

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

    private function attributionHistory(): Collection
    {
        return $this->baseQuery()
            ->with('attribution')
            ->whereHas('attribution')
            ->orderByDesc('occurred_at')
            ->limit(100)
            ->get()
            ->pluck('attribution')
            ->filter();
    }

    private function recentActivity(): Collection
    {
        return $this->baseQuery()->orderByDesc('occurred_at')->limit(5)->get();
    }

    private function currentAttribution(): ?\App\Models\Marketing\MarketingAttribution
    {
        return $this->baseQuery()
            ->with('attribution')
            ->whereHas('attribution')
            ->orderByDesc('occurred_at')
            ->first()
            ?->attribution;
    }

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

    /** Per-source breakdown (visits/revenue), scoped to this profile only. */
    private function sources(): Collection
    {
        return $this->baseQuery()
            ->selectRaw("COALESCE(utm_source, 'direct') as source, COUNT(*) as events, SUM(CASE WHEN event_name = ? THEN value ELSE 0 END) as revenue", [MarketingEventName::PURCHASE->value])
            ->groupBy(\Illuminate\Support\Facades\DB::raw("COALESCE(utm_source, 'direct')"))
            ->orderByDesc('events')
            ->get();
    }

    /** Per-campaign breakdown, scoped to this profile only. */
    private function campaigns(): Collection
    {
        return $this->baseQuery()
            ->whereNotNull('utm_campaign')
            ->selectRaw("utm_campaign, utm_source, COUNT(*) as events, SUM(CASE WHEN event_name = ? THEN value ELSE 0 END) as revenue", [MarketingEventName::PURCHASE->value])
            ->groupBy('utm_campaign', 'utm_source')
            ->orderByDesc('events')
            ->get();
    }

    /** Per-device event activity, scoped to this profile's devices. */
    private function deviceActivity(): Collection
    {
        if (empty($this->deviceIds)) {
            return collect();
        }

        $counts = $this->baseQuery()
            ->selectRaw('device_id, COUNT(*) as events, MAX(occurred_at) as last_event_at')
            ->whereNotNull('device_id')
            ->groupBy('device_id')
            ->get()
            ->keyBy('device_id');

        return Device::whereIn('id', $this->deviceIds)
            ->get()
            ->map(function (Device $device) use ($counts) {
                $row = $counts->get($device->id);
                $device->setAttribute('tracked_events', (int) ($row->events ?? 0));
                $device->setAttribute('last_tracked_at', $row?->last_event_at ? \Illuminate\Support\Carbon::parse($row->last_event_at) : null);

                return $device;
            })
            ->sortByDesc('tracked_events')
            ->values();
    }

    public function render()
    {
        return view('livewire.admin.users.partials.user-marketing', [
            'customerId' => $this->customerId,
            'stats' => $this->stats(),
            'visits' => $this->subTab === 'timeline' ? $this->timeline() : [],
            'attributionHistory' => $this->subTab === 'attribution' ? $this->attributionHistory() : collect(),
            'recentActivity' => $this->subTab === 'overview' ? $this->recentActivity() : collect(),
            'currentAttribution' => $this->subTab === 'overview' ? $this->currentAttribution() : null,
            'topProducts' => $this->subTab === 'overview' ? $this->topProducts() : collect(),
            'sources' => $this->subTab === 'sources' ? $this->sources() : collect(),
            'campaigns' => $this->subTab === 'campaigns' ? $this->campaigns() : collect(),
            'deviceActivity' => $this->subTab === 'devices' ? $this->deviceActivity() : collect(),
        ]);
    }
}
