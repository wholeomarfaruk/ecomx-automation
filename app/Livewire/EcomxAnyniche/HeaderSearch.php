<?php

namespace App\Livewire\EcomxAnyniche;

use App\Models\Category;
use App\Models\Product;
use Livewire\Component;

class HeaderSearch extends Component
{
    public string $query = '';
    public ?string $category = null;

    public function selectCategory(?string $category): void
    {
        $this->category = $category;
    }

    public function getResultsProperty(): array
    {
        $term = trim($this->query);

        if ($term === '') {
            return [];
        }

        return Product::where('status', 'active')
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhereHas('categories', fn ($c) => $c->where('name', 'like', "%{$term}%"));
            })
            ->when($this->category !== null, fn ($q) => $q->whereHas(
                'categories',
                fn ($c) => $c->where('name', $this->category)
            ))
            ->with('categories')
            ->limit(6)
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'price' => (float) $p->price,
                'sale' => $p->sale_price !== null ? (float) $p->sale_price : null,
                'img' => $p->featured_image,
                'url' => $p->url,
            ])
            ->all();
    }

    public function render()
    {
        return view('livewire.ecomx-anyniche.header-search', [
            'categories' => Category::active()->get(),
        ]);
    }
}
