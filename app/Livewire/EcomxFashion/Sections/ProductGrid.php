<?php

namespace App\Livewire\EcomxFashion\Sections;

use App\Livewire\Concerns\TogglesWishlist;
use App\Models\Category;
use App\Models\Product;
use App\Support\EcomxFashion\Catalog;
use App\Support\EcomxFashion\PageSectionConfigRegistry;
use Livewire\Attributes\Lazy;
use Livewire\Component;

/**
 * Static (non-carousel) grid variant of Trending: shows up to 10 products
 * from an optional source category (all active products when none is
 * picked), with a "Browse all products" button linking to the shop page.
 */
#[Lazy]
class ProductGrid extends Component
{
    use TogglesWishlist;

    protected const LIMIT = 10;
    protected const DEFAULT_KICKER = 'Trending now';
    protected const DEFAULT_HEADING = 'The Trending Collection';
    protected const DEFAULT_BUTTON_LABEL = 'Browse All Products';

    public string $kicker = self::DEFAULT_KICKER;
    public string $heading = self::DEFAULT_HEADING;
    public string $buttonLabel = self::DEFAULT_BUTTON_LABEL;
    public array $products = [];

    public function mount(): void
    {
        $config = PageSectionConfigRegistry::find('home', 'product-grid');
        $this->kicker = $config['kicker'] ?? '' ?: static::DEFAULT_KICKER;
        $this->heading = $config['heading'] ?? '' ?: static::DEFAULT_HEADING;
        $this->buttonLabel = $config['buttonLabel'] ?? '' ?: static::DEFAULT_BUTTON_LABEL;

        $categoryId = $config['categoryId'] ?? '';
        $category = $categoryId !== '' ? Category::find((int) $categoryId) : null;

        $query = $category ? $category->products() : Product::query();
        $products = $query->where('products.status', 'active')
            ->with('categories')
            ->latest('products.id')
            ->limit(static::LIMIT)
            ->get();

        $this->products = $products->isNotEmpty()
            ? $products->map(fn (Product $p) => $this->mapProduct($p, $category?->name ?? $p->categories->first()?->name ?? ''))->all()
            : array_slice(Catalog::products(), 0, static::LIMIT);
    }

    protected function mapProduct(Product $p, string $categoryName): array
    {
        $colorValues = $p->variants()
            ->where('status', 'active')
            ->with('values.productAttributeValue.attributeValue.attribute')
            ->get()
            ->flatMap(fn ($v) => $v->values)
            ->map(fn ($v) => $v->productAttributeValue?->attributeValue)
            ->filter(fn ($av) => $av?->attribute?->name === 'Color')
            ->unique('id');

        return [
            'id' => $p->id,
            'slug' => $p->slug,
            'name' => $p->name,
            'url' => $p->url,
            'price' => (float) $p->price,
            'sale' => $p->sale_price !== null ? (float) $p->sale_price : null,
            'tag' => $p->sale_price !== null ? 'Sale' : '',
            'cat' => $categoryName,
            'img' => $p->featured_image,
            'colors' => $colorValues->pluck('swatch_value')->filter()->values()->all(),
            'is_wished' => $p->isWishedBy(request()->attributes->get('device')),
        ];
    }

    public function placeholder()
    {
        return view('ecomx-fashion.livewire.sections.skeletons.product-grid');
    }

    public function render()
    {
        return view('ecomx-fashion.livewire.sections.product-grid');
    }
}
