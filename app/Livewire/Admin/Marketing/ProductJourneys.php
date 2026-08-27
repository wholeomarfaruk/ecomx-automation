<?php

namespace App\Livewire\Admin\Marketing;

use App\Marketing\Enums\MarketingEventName;
use App\Models\Marketing\MarketingEvent;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.admin.admin')]
class ProductJourneys extends Component
{
    #[Url]
    public string $selectedProduct = '';

    public function render()
    {
        $products = MarketingEvent::query()
            ->where('event_name', MarketingEventName::VIEW_CONTENT->value)
            ->whereNotNull('content_name')
            ->distinct()
            ->orderBy('content_name')
            ->pluck('content_name');

        $nextViewed = collect();
        $returnWindow = collect();

        if ($this->selectedProduct !== '') {
            // Devices that viewed the selected product
            $deviceIds = MarketingEvent::query()
                ->where('event_name', MarketingEventName::VIEW_CONTENT->value)
                ->where('content_name', $this->selectedProduct)
                ->whereNotNull('device_id')
                ->pluck('device_id', 'occurred_at');

            // What those same devices viewed next (any ViewContent after
            // their first view of the selected product, excluding itself).
            $deviceIdList = $deviceIds->values()->unique();

            $nextViewed = MarketingEvent::query()
                ->where('event_name', MarketingEventName::VIEW_CONTENT->value)
                ->whereIn('device_id', $deviceIdList)
                ->where('content_name', '!=', $this->selectedProduct)
                ->whereNotNull('content_name')
                ->selectRaw('content_name, COUNT(DISTINCT device_id) as total')
                ->groupBy('content_name')
                ->orderByDesc('total')
                ->limit(8)
                ->get();

            $totalViewers = $deviceIdList->count();
            if ($totalViewers > 0) {
                $nextViewed = $nextViewed->map(fn ($row) => [
                    'name' => $row->content_name,
                    'total' => $row->total,
                    'percentage' => round($row->total / $totalViewers * 100, 1),
                ]);
            }

            // Return window: for each viewing device, days until their next
            // event of any kind after viewing this product.
            $firstViews = MarketingEvent::query()
                ->where('event_name', MarketingEventName::VIEW_CONTENT->value)
                ->where('content_name', $this->selectedProduct)
                ->whereNotNull('device_id')
                ->select('device_id')
                ->selectRaw('MIN(occurred_at) as first_view')
                ->groupBy('device_id')
                ->get();

            $buckets = ['same_day' => 0, '1_3' => 0, '4_7' => 0, '8_30' => 0, 'never' => 0];

            foreach ($firstViews as $fv) {
                $nextEvent = MarketingEvent::query()
                    ->where('device_id', $fv->device_id)
                    ->where('occurred_at', '>', $fv->first_view)
                    ->orderBy('occurred_at')
                    ->value('occurred_at');

                if (! $nextEvent) {
                    $buckets['never']++;

                    continue;
                }

                $days = \Illuminate\Support\Carbon::parse($fv->first_view)->diffInDays($nextEvent);

                $buckets[match (true) {
                    $days < 1 => 'same_day',
                    $days <= 3 => '1_3',
                    $days <= 7 => '4_7',
                    $days <= 30 => '8_30',
                    default => 'never',
                }]++;
            }

            $returnWindow = collect($buckets);
        }

        return view('livewire.admin.marketing.product-journeys', [
            'products' => $products,
            'nextViewed' => $nextViewed,
            'returnWindow' => $returnWindow,
        ]);
    }
}
