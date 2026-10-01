<?php

namespace App\Livewire\EcomxFashion;

use App\Livewire\Concerns\TracksSearch;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class SearchModal extends Component
{
    use TracksSearch;

    public string $q = '';

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

    public function trackSearch(): void
    {
        $this->recordSearch($this->q, $this->search());
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
