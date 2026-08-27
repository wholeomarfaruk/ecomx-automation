<?php

namespace App\Livewire\Admin\Marketing;

use App\Marketing\Enums\MarketingEventName;
use App\Models\Marketing\MarketingAttribution as MarketingAttributionModel;
use App\Models\Marketing\MarketingEvent;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin.admin')]
class Attribution extends Component
{
    public function render()
    {
        $purchaseIds = MarketingEvent::query()
            ->where('event_name', MarketingEventName::PURCHASE->value)
            ->pluck('id');

        $firstTouch = MarketingAttributionModel::query()
            ->whereIn('marketing_event_id', $purchaseIds)
            ->whereNotNull('first_touch_source')
            ->join('marketing_events', 'marketing_events.id', '=', 'marketing_attributions.marketing_event_id')
            ->selectRaw('first_touch_source as source, COUNT(*) as purchases, SUM(marketing_events.value) as revenue')
            ->groupBy('first_touch_source')
            ->orderByDesc('revenue')
            ->get();

        $lastTouch = MarketingAttributionModel::query()
            ->whereIn('marketing_event_id', $purchaseIds)
            ->whereNotNull('last_touch_source')
            ->join('marketing_events', 'marketing_events.id', '=', 'marketing_attributions.marketing_event_id')
            ->selectRaw('last_touch_source as source, COUNT(*) as purchases, SUM(marketing_events.value) as revenue')
            ->groupBy('last_touch_source')
            ->orderByDesc('revenue')
            ->get();

        // Conversion paths: first_touch_source -> last_touch_source pairs,
        // i.e. what channel acquired the customer vs what channel closed
        // the sale, when they differ.
        $paths = MarketingAttributionModel::query()
            ->whereIn('marketing_event_id', $purchaseIds)
            ->whereNotNull('first_touch_source')
            ->whereNotNull('last_touch_source')
            ->join('marketing_events', 'marketing_events.id', '=', 'marketing_attributions.marketing_event_id')
            ->selectRaw('first_touch_source, last_touch_source, COUNT(*) as total, SUM(marketing_events.value) as revenue')
            ->groupBy('first_touch_source', 'last_touch_source')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return view('livewire.admin.marketing.attribution', [
            'firstTouch' => $firstTouch,
            'lastTouch' => $lastTouch,
            'paths' => $paths,
        ]);
    }
}
