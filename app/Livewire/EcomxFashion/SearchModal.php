<?php

namespace App\Livewire\EcomxFashion;

use App\Models\Product;
use Livewire\Component;

class SearchModal extends Component
{
    public string $q = '';

    public function getResultsProperty(): array
    {
        $term = trim($this->q);

        if ($term === '') {
            return [];
        }

        return Product::where('status', 'active')
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhereHas('categories', fn ($q) => $q->where('name', 'like', "%{$term}%"));
            })
            ->with('categories', 'variants')
            ->limit(8)
            ->get()
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

    public function render()
    {
        return view('livewire.ecomx-fashion.search-modal');
    }
}
