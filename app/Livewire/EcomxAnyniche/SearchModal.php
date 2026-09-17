<?php

namespace App\Livewire\EcomxAnyniche;

use App\Models\Category;
use App\Models\Product;
use Livewire\Component;

class SearchModal extends Component
{
    public string $q = '';
    public ?string $category = null;

    public function selectCategory(?string $category): void
    {
        $this->category = $category;
    }

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
            ->when($this->category !== null, fn ($query) => $query->whereHas(
                'categories',
                fn ($q) => $q->where('name', $this->category)
            ))
            ->with('categories')
            ->limit(8)
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'cat' => $p->categories->first()->name ?? '',
                'price' => (float) $p->price,
                'sale' => $p->sale_price !== null ? (float) $p->sale_price : null,
                'inStock' => $p->stock_status === 'in_stock',
                'img' => $p->featured_image,
                'url' => $p->url,
            ])
            ->all();
    }

    public function render()
    {
        return view('livewire.ecomx-anyniche.search-modal', [
            'categories' => Category::active()->get(),
        ]);
    }
}
