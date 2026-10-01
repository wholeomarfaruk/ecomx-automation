<?php

namespace App\Livewire\Concerns;

use App\Enums\Product\ProductType;
use App\Marketing\Catalog\CatalogItemId;
use App\Marketing\Events\Search;
use App\Marketing\Services\MarketingEventService;
use App\Models\Device;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Search marketing event for the storefront live searches. Called from the
 * input once typing pauses (x-on:input.debounce in the view), not on every
 * live keystroke — so "s", "sh", "shi"… aren't each a Search event, only
 * the term the shopper settled on.
 */
trait TracksSearch
{
    /** Last term sent as a Search event, so a pause on the same term isn't sent twice. */
    public ?string $trackedTerm = null;

    /** @param  Collection<int, Product>  $results  the products shown for $term */
    protected function recordSearch(string $term, Collection $results): void
    {
        $term = trim($term);

        if (mb_strlen($term) < 2 || mb_strtolower($term) === mb_strtolower((string) $this->trackedTerm)) {
            return;
        }

        /** @var Device|null $device */
        $device = request()->attributes->get('device');

        if (! $device) {
            return;
        }

        $this->trackedTerm = $term;

        // Same catalog ids as ViewContent: no variant is picked from a
        // search result, so a variable product is its item group.
        $items = $results
            ->map(fn (Product $p) => [
                'item_id' => $p->product_type === ProductType::VARIABLE ? CatalogItemId::group($p->id) : CatalogItemId::item($p->id),
                'item_name' => $p->name,
                'price' => (float) $p->min_price,
            ])
            ->values()
            ->all();

        $result = app(MarketingEventService::class)->recordForCurrentRequest(
            event: Search::create(
                searchString: $term,
                currency: 'BDT',
                items: $items,
            ),
            device: $device,
            customer: auth()->check() ? auth()->user()->customer : null,
        );

        $this->dispatch('marketing-event', payload: $result['browserPayload']);
    }
}
