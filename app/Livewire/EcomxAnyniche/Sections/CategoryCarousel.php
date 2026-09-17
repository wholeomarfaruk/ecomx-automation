<?php

namespace App\Livewire\EcomxAnyniche\Sections;

use App\Models\Category;
use App\Support\EcomxAnyniche\Catalog;
use App\Support\EcomxAnyniche\PageSectionConfigRegistry;
use Livewire\Attributes\Lazy;
use Livewire\Component;

/**
 * "Shop by category" carousel — ported from juwel-trade-corporation's
 * storefront.partials.category-carousel. Admin picks which categories show
 * (category_multi_select), same as ShopByStyle/CategoryShowcase — this
 * project's Category model has no homepage_category/display_order flags of
 * its own (see SectionSchema docblock).
 */
#[Lazy]
class CategoryCarousel extends Component
{
    protected const MAX_ITEMS = 20;

    public array $items = [];

    public function mount(): void
    {
        $config = PageSectionConfigRegistry::find('home', 'category-carousel');
        $categoryIds = $config['categoryIds'] ?? [];

        $this->items = ! empty($categoryIds)
            ? $this->itemsFromCategories($categoryIds)
            : Catalog::categories();
    }

    protected function itemsFromCategories(array $categoryIds): array
    {
        $categories = Category::whereIn('id', $categoryIds)
            ->where('status', 'active')
            ->get()
            ->keyBy('id');

        $ordered = collect($categoryIds)
            ->take(static::MAX_ITEMS)
            ->map(fn ($id) => $categories->get((int) $id))
            ->filter();

        if ($ordered->isEmpty()) {
            return Catalog::categories();
        }

        return $ordered->values()->map(fn (Category $c) => [
            'id' => $c->id,
            'name' => $c->name,
            'slug' => $c->slug,
            'url' => route('ecomx-anyniche.category', $c->slug),
            'image' => $c->featured_image_id ? file_path($c->featured_image_id) : (config('ecomx-anyniche.unsplash') . 'photo-1445205170230-053b83016050?q=80&w=400&auto=format&fit=crop'),
        ])->all();
    }

    public function placeholder()
    {
        return view('ecomx-anyniche.livewire.sections.skeletons.category-carousel');
    }

    public function render()
    {
        return view('ecomx-anyniche.livewire.sections.category-carousel', [
            'items' => $this->items,
        ]);
    }
}
