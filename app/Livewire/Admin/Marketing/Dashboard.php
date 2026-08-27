<?php

namespace App\Livewire\Admin\Marketing;

use App\Livewire\Admin\Marketing\Concerns\HasDateRange;
use App\Marketing\Enums\MarketingEventName;
use App\Models\Marketing\MarketingCampaign;
use App\Models\Marketing\MarketingEvent;
use App\Models\Marketing\MarketingEventDestination;
use App\Models\Marketing\MarketingSession;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin.admin')]
class Dashboard extends Component
{
    use HasDateRange;

    public function render()
    {
        $since = $this->since();
        $until = $this->until();

        $events = MarketingEvent::query()
            ->when($since, fn ($q) => $q->where('occurred_at', '>=', $since))
            ->when($until, fn ($q) => $q->where('occurred_at', '<=', $until));

        $counts = (clone $events)
            ->selectRaw('event_name, COUNT(*) as total')
            ->groupBy('event_name')
            ->pluck('total', 'event_name');

        $funnel = collect([
            MarketingEventName::PAGE_VIEW->value,
            MarketingEventName::VIEW_CONTENT->value,
            MarketingEventName::ADD_TO_CART->value,
            MarketingEventName::INITIATE_CHECKOUT->value,
            MarketingEventName::PURCHASE->value,
        ])->map(fn ($name) => [
            'name' => $name,
            'count' => (int) ($counts[$name] ?? 0),
        ]);

        $revenue = (clone $events)
            ->where('event_name', MarketingEventName::PURCHASE->value)
            ->sum('value');

        $purchaseCount = (int) ($counts[MarketingEventName::PURCHASE->value] ?? 0);

        $kpis = [
            'visitors' => (clone $events)->distinct('device_id')->count('device_id'),
            'sessions' => MarketingSession::query()
                ->when($since, fn ($q) => $q->where('started_at', '>=', $since))
                ->when($until, fn ($q) => $q->where('started_at', '<=', $until))
                ->count(),
            'customers' => (clone $events)->whereNotNull('customer_id')->distinct('customer_id')->count('customer_id'),
            'purchases' => $purchaseCount,
            'revenue' => (float) $revenue,
            'aov' => $purchaseCount > 0 ? (float) $revenue / $purchaseCount : 0.0,
        ];

        // Daily event volume for the trend chart — zero-filled so the x-axis
        // has no gaps even on days with no traffic.
        $dailyRaw = (clone $events)
            ->selectRaw('DATE(occurred_at) as day, event_name, COUNT(*) as total')
            ->groupBy('day', 'event_name')
            ->get()
            ->groupBy('day');

        $trendStart = $since ?? Carbon::parse((clone $events)->min('occurred_at') ?? now())->startOfDay();
        $trendEnd = $until ?? now();

        $period = collect();
        $cursor = $trendStart;
        while ($cursor->lte($trendEnd)) {
            $period->push($cursor->format('Y-m-d'));
            $cursor = $cursor->addDay();
        }

        $trend = [
            'labels' => $period->map(fn ($d) => Carbon::parse($d)->format('M j'))->all(),
            'pageViews' => $period->map(fn ($d) => (int) ($dailyRaw->get($d, collect())->firstWhere('event_name', MarketingEventName::PAGE_VIEW->value)->total ?? 0))->all(),
            'purchases' => $period->map(fn ($d) => (int) ($dailyRaw->get($d, collect())->firstWhere('event_name', MarketingEventName::PURCHASE->value)->total ?? 0))->all(),
        ];

        // Campaign performance table — joins the raw utm_campaign string on
        // marketing_events (see MarketingCampaign::events()) rather than a
        // campaign_id FK, since events can predate campaign registration.
        $campaigns = MarketingCampaign::with('source')
            ->get()
            ->map(function (MarketingCampaign $campaign) use ($since, $until) {
                $campaignEvents = MarketingEvent::query()
                    ->when($since, fn ($q) => $q->where('occurred_at', '>=', $since))
                    ->when($until, fn ($q) => $q->where('occurred_at', '<=', $until))
                    ->where('utm_campaign', $campaign->campaign_key);

                $campaignCounts = (clone $campaignEvents)
                    ->selectRaw('event_name, COUNT(*) as total')
                    ->groupBy('event_name')
                    ->pluck('total', 'event_name');

                return [
                    'id' => $campaign->id,
                    'platform' => $campaign->source?->platform,
                    'name' => $campaign->external_campaign_name ?? $campaign->campaign_key,
                    'visitors' => (clone $campaignEvents)->distinct('device_id')->count('device_id'),
                    'product_views' => (int) ($campaignCounts[MarketingEventName::VIEW_CONTENT->value] ?? 0),
                    'add_to_cart' => (int) ($campaignCounts[MarketingEventName::ADD_TO_CART->value] ?? 0),
                    'purchases' => (int) ($campaignCounts[MarketingEventName::PURCHASE->value] ?? 0),
                    'revenue' => (float) (clone $campaignEvents)->where('event_name', MarketingEventName::PURCHASE->value)->sum('value'),
                ];
            })
            ->sortByDesc('revenue')
            ->values();

        // Source breakdown (by raw utm_source — always available even for
        // sources with no registered marketing_campaigns row yet).
        $sources = (clone $events)
            ->whereNotNull('utm_source')
            ->selectRaw('utm_source, COUNT(DISTINCT device_id) as visitors')
            ->groupBy('utm_source')
            ->orderByDesc('visitors')
            ->limit(6)
            ->get();

        $destinationHealth = MarketingEventDestination::query()
            ->whereHas('event', fn ($q) => $q
                ->when($since, fn ($qq) => $qq->where('occurred_at', '>=', $since))
                ->when($until, fn ($qq) => $qq->where('occurred_at', '<=', $until)))
            ->selectRaw('destination, status, COUNT(*) as total')
            ->groupBy('destination', 'status')
            ->get()
            ->groupBy('destination');

        return view('livewire.admin.marketing.dashboard', [
            'kpis' => $kpis,
            'funnel' => $funnel,
            'trend' => $trend,
            'campaigns' => $campaigns,
            'sources' => $sources,
            'destinationHealth' => $destinationHealth,
        ]);
    }
}
