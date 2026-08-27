<?php

namespace App\Livewire\Admin\Marketing;

use App\Livewire\Admin\Marketing\Concerns\HasDateRange;
use App\Marketing\Enums\MarketingEventName;
use App\Models\Marketing\MarketingEvent;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin.admin')]
class SourceAnalytics extends Component
{
    use HasDateRange;

    public function render()
    {
        $base = MarketingEvent::query();

        if ($since = $this->since()) {
            $base->where('occurred_at', '>=', $since);
        }

        if ($until = $this->until()) {
            $base->where('occurred_at', '<=', $until);
        }

        $sourceKeys = (clone $base)
            ->selectRaw("COALESCE(utm_source, 'direct') as src")
            ->distinct()
            ->pluck('src');

        $sources = $sourceKeys->map(function ($src) use ($base) {
            $events = (clone $base)->where(fn ($q) => $src === 'direct'
                ? $q->whereNull('utm_source')
                : $q->where('utm_source', $src));

            $counts = (clone $events)->selectRaw('event_name, COUNT(*) as total')->groupBy('event_name')->pluck('total', 'event_name');

            $visitors = (clone $events)->distinct('device_id')->count('device_id');
            $returning = (clone $events)
                ->select('device_id')
                ->groupBy('device_id')
                ->havingRaw('COUNT(DISTINCT DATE(occurred_at)) > 1')
                ->get()
                ->count();
            $purchases = (int) ($counts[MarketingEventName::PURCHASE->value] ?? 0);
            $revenue = (float) (clone $events)->where('event_name', MarketingEventName::PURCHASE->value)->sum('value');

            return [
                'source' => $src,
                'visitors' => $visitors,
                'returning' => $returning,
                'page_views' => (int) ($counts[MarketingEventName::PAGE_VIEW->value] ?? 0),
                'product_views' => (int) ($counts[MarketingEventName::VIEW_CONTENT->value] ?? 0),
                'add_to_cart' => (int) ($counts[MarketingEventName::ADD_TO_CART->value] ?? 0),
                'checkout_abandon' => (int) ($counts[MarketingEventName::INITIATE_CHECKOUT->value] ?? 0) - $purchases,
                'purchases' => $purchases,
                'revenue' => $revenue,
                'conversion_rate' => $visitors > 0 ? round($purchases / $visitors * 100, 2) : 0.0,
            ];
        })->sortByDesc('revenue')->values();

        return view('livewire.admin.marketing.source-analytics', [
            'sources' => $sources,
        ]);
    }
}
