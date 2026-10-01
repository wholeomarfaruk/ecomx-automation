<?php

namespace App\Livewire\EcomxAnyniche;

use App\Livewire\Concerns\TracksSearch;
use App\Models\Product;
use App\Support\EcomxAnyniche\MenuRegistry;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class SearchModal extends Component
{
    use TracksSearch;

    public string $q = '';
    public ?string $category = null;

    public function selectCategory(?string $category): void
    {
        $this->category = $category;
    }

    public function getResultsProperty(): array
    {
        return $this->search()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'cat' => $p->categories->first()->name ?? '',
                ...$p->cardPricing(),
                // low_stock still sells — only out_of_stock doesn't.
                'inStock' => $p->stock_status !== 'out_of_stock',
                'img' => $p->featured_image,
                'url' => route('ecomx-anyniche.product', $p->slug),
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
            ->when($this->category !== null, fn ($query) => $query->whereHas(
                'categories',
                fn ($q) => $q->where('name', $this->category)
            ))
            ->with('categories', 'variants')
            ->limit(8)
            ->get();
    }

    public function render()
    {
        return view('livewire.ecomx-anyniche.search-modal', [
            'categories' => MenuRegistry::searchCategoryItems(),
        ]);
    }
}
