<?php

namespace App\Livewire\EcomxFashion;

use App\Models\Product as ProductModel;
use App\Support\EcomxFashion\Catalog;
use Livewire\Attributes\Lazy;
use Livewire\Component;

/**
 * "You may also like" carousel for the ecomx-fashion product page. Loads
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
            ? $products->map(fn (ProductModel $p) => $this->mapProduct($p))->all()
            : array_slice(Catalog::products(), 1);
    }

    protected function mapProduct(ProductModel $p): array
    {
        $colorValues = $p->variants
            ->where('status', 'active')
            ->flatMap(fn ($v) => $v->values)
            ->map(fn ($v) => $v->productAttributeValue?->attributeValue)
            ->filter(fn ($av) => $av?->attribute?->name === 'Color')
            ->unique('id');

        return [
            'id' => $p->id,
            'slug' => $p->slug,
            'name' => $p->name,
            'url' => route('ecomx-fashion.product', $p->slug),
            'price' => (float) $p->price,
            'sale' => $p->sale_price !== null ? (float) $p->sale_price : null,
            'tag' => $p->sale_price !== null ? 'Sale' : '',
            'cat' => $p->categories->first()->name ?? '',
            'img' => $p->featured_image,
            'colors' => $colorValues->pluck('swatch_value')->filter()->values()->all(),
            'is_wished' => $p->isWishedBy(request()->attributes->get('device')),
        ];
    }

    public function placeholder()
    {
        return view('ecomx-fashion.livewire.product-related-carousel-placeholder');
    }

    public function render()
    {
        return view('ecomx-fashion.livewire.product-related-carousel');
    }
}
