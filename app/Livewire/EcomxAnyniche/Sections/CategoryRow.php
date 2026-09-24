<?php

namespace App\Livewire\EcomxAnyniche\Sections;

use App\Livewire\Concerns\TogglesWishlist;
use App\Models\Category;
use App\Support\EcomxAnyniche\Catalog;
use App\Support\EcomxAnyniche\PageSectionConfigRegistry;
use Livewire\Attributes\Lazy;
use Livewire\Component;

/**
 * A single category's product row — ported from juwel-trade-corporation's
 * App\Livewire\Website\Storefront\CategorySection. Registered multiple times
 * in config('ecomx-anyniche.sections') (category-row-1..5) so the admin can
 * place/reorder/toggle each one independently, matching the JTC homepage's
 * interleaved layout (hero, carousel, category row, promo strip, category
 * row, promos grid, browse-all, more category rows, discover chips).
 */
#[Lazy]
class CategoryRow extends Component
{
    use TogglesWishlist;

    public ?string $sectionKey = null;

    public ?Category $category = null;

    public array $products = [];

    public bool $rail = true;

    public function mount(string $sectionKey): void
    {
        $this->sectionKey = $sectionKey;
        $config = PageSectionConfigRegistry::find('home', $sectionKey);

        $categoryId = $config['categoryId'] ?? '';
        $this->rail = array_key_exists('rail', $config ?? []) ? (bool) $config['rail'] : true;

        $this->category = $categoryId !== '' ? Category::where('status', 'active')->find((int) $categoryId) : null;

        $this->products = $this->category
            ? $this->category->products()->where('status', 'active')->with('variants')->take(12)->get()
                ->map(fn ($p) => Catalog::decorateProduct($p))->all()
            : Catalog::jtcProducts();
    }

    /** @param array $params Same props passed to @livewire(...) — mount() hasn't run yet at placeholder time. */
    public function placeholder(array $params = [])
    {
        return view('ecomx-anyniche.livewire.sections.skeletons.category-row', [
            'rail' => $params['rail'] ?? true,
        ]);
    }

    public function render()
    {
        return view('ecomx-anyniche.livewire.sections.category-row');
    }
}
