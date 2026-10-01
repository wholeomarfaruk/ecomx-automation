<?php

namespace App\Livewire\EcomxFashion;

use App\Enums\Product\ProductType;
use App\Marketing\Catalog\CatalogItemId;
use App\Marketing\Events\Search;
use App\Marketing\Services\MarketingEventService;
use App\Models\Device;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class SearchModal extends Component
{
    public string $q = '';

    /** Last term sent as a Search event, so a pause on the same term isn't sent twice. */
    public ?string $trackedTerm = null;

    public function getResultsProperty(): array
    {
        return $this->search()
            ->map(fn (Product $p) => [
                'name' => $p->name,
                'cat' => $p->categories->first()->name ?? '',
                ...$p->cardPricing(),
                // low_stock still sells — only out_of_stock doesn't.
                'inStock' => $p->stock_status !== 'out_of_stock',
                'img' => $p->featured_image,
                'url' => route('ecomx-fashion.product', $p->slug),
            ])
            ->all();
    }

    /**
     * Called from the input once typing pauses (see search-modal.blade.php),
     * not on every live keystroke — so "s", "sh", "shi"… aren't each a
     * Search event, only the term the shopper settled on.
     */
    public function trackSearch(): void
    {
        $term = trim($this->q);

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
        $items = $this->search()
            ->map(fn (Product $p) => [
                'item_id' => $p->product_type === ProductType::VARIABLE ? CatalogItemId::group($p->id) : CatalogItemId::item($p->id),
                'item_name' => $p->name,
                'price' => (float) $p->min_price,
            ])
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

    private function search(): Collection
    {
        $term = trim($this->q);

        if ($term === '') {
            return new Collection;
        }

        return Product::where('status', 'active')
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhereHas('categories', fn ($q) => $q->where('name', 'like', "%{$term}%"));
            })
            ->with('categories', 'variants')
            ->limit(8)
            ->get();
    }

    public function render()
    {
        return view('livewire.ecomx-fashion.search-modal');
    }
}
