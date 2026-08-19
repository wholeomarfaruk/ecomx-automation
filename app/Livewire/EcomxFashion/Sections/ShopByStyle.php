<?php

namespace App\Livewire\EcomxFashion\Sections;

use App\Models\Category;
use App\Support\EcomxFashion\Catalog;
use App\Support\EcomxFashion\PageSectionConfigRegistry;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class ShopByStyle extends Component
{
    // Masonry grid spans, cycled in saved-order across the selected categories.
    protected const SPAN_PATTERN = [2, 1, 1, 2, 1, 1];

    public array $styles = [];

    public function mount(): void
    {
        $config = PageSectionConfigRegistry::find('home', 'shop-by-style');
        $categoryIds = $config['categoryIds'] ?? [];

        $this->styles = ! empty($categoryIds)
            ? $this->stylesFromCategories($categoryIds)
            : Catalog::styles();
    }

    protected function stylesFromCategories(array $categoryIds): array
    {
        $categories = Category::whereIn('id', $categoryIds)
            ->where('is_active', true)
            ->withCount('products')
            ->with('banner')
            ->get()
            ->keyBy('id');

        $ordered = collect($categoryIds)
            ->map(fn ($id) => $categories->get((int) $id))
            ->filter();

        if ($ordered->isEmpty()) {
            return Catalog::styles();
        }

        return $ordered->values()->map(fn (Category $c, int $i) => [
            'name' => $c->name,
            'sub' => $c->products_count . ' ' . ($c->products_count === 1 ? 'piece' : 'pieces'),
            'img' => $c->banner?->getUrl() ?? ($c->image ? asset('images/category/' . $c->image) : 'photo-1434389677669-e08b4cac3105'),
            'slug' => $c->slug,
            'span' => static::SPAN_PATTERN[$i % count(static::SPAN_PATTERN)],
        ])->all();
    }

    public function placeholder()
    {
        return view('ecomx-fashion.livewire.sections.skeletons.shop-by-style');
    }

    public function render()
    {
        return view('ecomx-fashion.livewire.sections.shop-by-style', [
            'styles' => $this->styles,
        ]);
    }
}
