<?php

namespace App\Livewire\EcomxAnyniche;

use App\Livewire\Concerns\TracksSearch;
use App\Models\Product;
use App\Support\EcomxAnyniche\MenuRegistry;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class HeaderSearch extends Component
{
    use TracksSearch;

    public string $query = '';
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
                ...$p->cardPricing(),
                'img' => $p->featured_image,
                'url' => route('ecomx-anyniche.product', $p->slug),
            ])
            ->all();
    }

    public function trackSearch(): void
    {
        $this->recordSearch($this->query, $this->search());
    }

    private function search(): Collection
    {
        $term = trim($this->query);

        if ($term === '') {
            return new Collection;
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
            ->with('categories', 'variants')
            ->limit(6)
            ->get();
    }

    public function render()
    {
        return view('livewire.ecomx-anyniche.header-search', [
            'categories' => MenuRegistry::searchCategoryItems(),
        ]);
    }
}
