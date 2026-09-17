<?php

namespace App\Livewire\EcomxAnyniche\Sections;

use App\Livewire\Concerns\TogglesWishlist;
use App\Models\Product;
use App\Support\EcomxAnyniche\Catalog;
use Livewire\Attributes\Lazy;
use Livewire\Component;

/**
 * "Browse our products" grid — ported from juwel-trade-corporation's
 * BrowseAllSection. Not admin-configurable (like the original): always the
 * latest active products, capped at $limit.
 */
#[Lazy]
class BrowseAll extends Component
{
    use TogglesWishlist;

    public int $limit = 12;

    public array $products = [];

    public function mount(): void
    {
        $products = Product::where('status', 'active')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->take($this->limit)
            ->get();

        $this->products = $products->isNotEmpty()
            ? $products->map(fn (Product $p) => Catalog::decorateProduct($p))->all()
            : Catalog::jtcProducts();
    }

    public function placeholder()
    {
        return view('ecomx-anyniche.livewire.sections.skeletons.browse-all');
    }

    public function render()
    {
        return view('ecomx-anyniche.livewire.sections.browse-all');
    }
}
