<?php

namespace App\Livewire\EcomxAnyniche;

use App\Models\Product as ProductModel;
use App\Support\EcomxAnyniche\Catalog;
use Livewire\Attributes\Lazy;
use Livewire\Component;

/**
 * "You may also like" carousel for the ecomx-anyniche product page. Loads
 * below the fold — #[Lazy] keeps its query off the first-paint request.
 */
#[Lazy]
class ProductRelatedCarousel extends Component
{
    public int $productId;
    public array $related = [];

    public function mount(int $productId): void
    {
        $this->productId = $productId;

        $products = ProductModel::active()
            ->where('id', '!=', $productId)
            ->with('categories', 'variants.values.productAttributeValue.attributeValue.attribute')
            ->limit(8)
            ->get();

        $this->related = $products->isNotEmpty()
            ? $products->map(fn (ProductModel $p) => Catalog::decorateProduct($p))->all()
            : Catalog::jtcProducts();
    }

    public function placeholder()
    {
        return view('ecomx-anyniche.livewire.product-related-carousel-placeholder');
    }

    public function render()
    {
        return view('ecomx-anyniche.livewire.product-related-carousel');
    }
}
