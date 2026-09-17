<?php

namespace App\Livewire\EcomxAnyniche\Sections;

use App\Models\Category;
use App\Support\EcomxAnyniche\PageSectionConfigRegistry;
use Livewire\Attributes\Lazy;
use Livewire\Component;

/**
 * "Discover more" category chips — ported from juwel-trade-corporation's
 * DiscoverChipsSection. Admin picks which categories show
 * (category_multi_select), same pattern as CategoryCarousel.
 */
#[Lazy]
class DiscoverChips extends Component
{
    protected const MAX_CHIPS = 18;

    public array $chips = [];

    public function mount(): void
    {
        $config = PageSectionConfigRegistry::find('home', 'discover-chips');
        $categoryIds = $config['categoryIds'] ?? [];

        if (empty($categoryIds)) {
            $this->chips = [];

            return;
        }

        $categories = Category::whereIn('id', $categoryIds)
            ->where('status', 'active')
            ->get()
            ->keyBy('id');

        $this->chips = collect($categoryIds)
            ->take(static::MAX_CHIPS)
            ->map(fn ($id) => $categories->get((int) $id))
            ->filter()
            ->map(fn (Category $c) => [
                'name' => $c->name,
                'url' => route('ecomx-anyniche.category', $c->slug),
            ])
            ->values()
            ->all();
    }

    public function placeholder()
    {
        return view('ecomx-anyniche.livewire.sections.skeletons.discover-chips');
    }

    public function render()
    {
        return view('ecomx-anyniche.livewire.sections.discover-chips');
    }
}
