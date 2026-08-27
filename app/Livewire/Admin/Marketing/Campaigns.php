<?php

namespace App\Livewire\Admin\Marketing;

use App\Livewire\Admin\Marketing\Concerns\HasDateRange;
use App\Marketing\Enums\MarketingEventName;
use App\Models\Marketing\MarketingCampaign;
use App\Models\Marketing\MarketingEvent;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin.admin')]
class Campaigns extends Component
{
    use HasDateRange;

    /** Pinned by the route (Meta/Google/TikTok/Other), or empty for "All Campaigns". */
    public string $platform = '';

    public function mount(string $platform = ''): void
    {
        $this->platform = $platform;
    }

    public function render()
    {
        $since = $this->since();
        $until = $this->until();

        $campaigns = MarketingCampaign::with('source')
            ->when($this->platform !== '', function ($q) {
                if ($this->platform === 'other') {
                    $q->whereDoesntHave('source', fn ($s) => $s->whereIn('platform', ['meta', 'google', 'tiktok']));
                } else {
                    $q->whereHas('source', fn ($s) => $s->where('platform', $this->platform));
                }
            })
            ->get()
            ->map(function (MarketingCampaign $campaign) use ($since, $until) {
                $events = MarketingEvent::query()->where('utm_campaign', $campaign->campaign_key);

                if ($since) {
                    $events->where('occurred_at', '>=', $since);
                }

                if ($until) {
                    $events->where('occurred_at', '<=', $until);
                }

                $counts = (clone $events)
                    ->selectRaw('event_name, COUNT(*) as total')
                    ->groupBy('event_name')
                    ->pluck('total', 'event_name');

                $visitors = (clone $events)->distinct('device_id')->count('device_id');
                $purchases = (int) ($counts[MarketingEventName::PURCHASE->value] ?? 0);
                $revenue = (float) (clone $events)->where('event_name', MarketingEventName::PURCHASE->value)->sum('value');

                return [
                    'id' => $campaign->id,
                    'platform' => $campaign->source?->platform ?? 'unknown',
                    'name' => $campaign->external_campaign_name ?? $campaign->campaign_key ?? '—',
                    'campaign_key' => $campaign->campaign_key,
                    'external_campaign_id' => $campaign->external_campaign_id,
                    'status' => $campaign->status,
                    'visitors' => $visitors,
                    'page_views' => (int) ($counts[MarketingEventName::PAGE_VIEW->value] ?? 0),
                    'product_views' => (int) ($counts[MarketingEventName::VIEW_CONTENT->value] ?? 0),
                    'add_to_cart' => (int) ($counts[MarketingEventName::ADD_TO_CART->value] ?? 0),
                    'checkout' => (int) ($counts[MarketingEventName::INITIATE_CHECKOUT->value] ?? 0),
                    'purchases' => $purchases,
                    'revenue' => $revenue,
                    'conversion_rate' => $visitors > 0 ? round($purchases / $visitors * 100, 2) : 0.0,
                ];
            })
            ->sortByDesc('revenue')
            ->values();

        return view('livewire.admin.marketing.campaigns', [
            'campaigns' => $campaigns,
            'platformLabel' => match ($this->platform) {
                'meta' => 'Meta',
                'google' => 'Google',
                'tiktok' => 'TikTok',
                'other' => 'Other / UTM',
                default => 'All',
            },
        ]);
    }
}
