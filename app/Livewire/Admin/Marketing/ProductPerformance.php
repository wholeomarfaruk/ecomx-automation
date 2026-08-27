<?php

namespace App\Livewire\Admin\Marketing;

use App\Marketing\Enums\MarketingEventName;
use App\Models\Marketing\MarketingEvent;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin.admin')]
class ProductPerformance extends Component
{
    public string $search = '';

    public function render()
    {
        // Grouped by content_name (product name at event time) rather than
        // content_id — ViewContent/AddToCart events aren't always linked to
        // a real products.id (e.g. events recorded before a product existed
        // in the catalog, or from a page that only had a display name).
        $rows = MarketingEvent::query()
            ->whereNotNull('content_name')
            ->when($this->search, fn ($q) => $q->where('content_name', 'like', "%{$this->search}%"))
            ->selectRaw('content_name, event_name, COUNT(DISTINCT device_id) as visitors, COUNT(*) as total')
            ->groupBy('content_name', 'event_name')
            ->get()
            ->groupBy('content_name')
            ->map(function ($events, $name) {
                $byEvent = $events->keyBy('event_name');

                $views = (int) ($byEvent[MarketingEventName::VIEW_CONTENT->value]->total ?? 0);
                $atc = (int) ($byEvent[MarketingEventName::ADD_TO_CART->value]->total ?? 0);
                $purchases = (int) ($byEvent[MarketingEventName::PURCHASE->value]->total ?? 0);

                $revenue = MarketingEvent::query()
                    ->where('content_name', $name)
                    ->where('event_name', MarketingEventName::PURCHASE->value)
                    ->sum('value');

                return [
                    'name' => $name,
                    'visitors' => (int) ($byEvent[MarketingEventName::VIEW_CONTENT->value]->visitors ?? 0),
                    'views' => $views,
                    'add_to_cart' => $atc,
                    'purchases' => $purchases,
                    'revenue' => (float) $revenue,
                    'view_to_cart' => $views > 0 ? round($atc / $views * 100, 1) : 0.0,
                    'cart_to_purchase' => $atc > 0 ? round($purchases / $atc * 100, 1) : 0.0,
                ];
            })
            ->sortByDesc('revenue')
            ->values();

        return view('livewire.admin.marketing.product-performance', [
            'products' => $rows,
        ]);
    }
}
