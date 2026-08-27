<?php

namespace App\Livewire\EcomxFashion\Sections;

use App\Livewire\Concerns\TogglesWishlist;
use App\Models\Category;
use App\Models\Product;
use App\Support\EcomxFashion\Catalog;
use App\Support\EcomxFashion\PageSectionConfigRegistry;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class Trending extends Component
{
    use TogglesWishlist;

    protected const DEFAULT_KICKER = 'Trending now';
    protected const DEFAULT_HEADING = 'The Trending Collection';

    public string $kicker = self::DEFAULT_KICKER;
    public string $heading = self::DEFAULT_HEADING;
    public array $trending = [];

    public function mount(): void
    {
        $config = PageSectionConfigRegistry::find('home', 'trending');
        $this->kicker = $config['kicker'] ?? '' ?: static::DEFAULT_KICKER;
        $this->heading = $config['heading'] ?? '' ?: static::DEFAULT_HEADING;

        $categoryId = $config['categoryId'] ?? '';
        $category = $categoryId !== '' ? Category::find((int) $categoryId) : null;
        $products = $category ? $this->productsFor($category) : collect();

        $this->trending = $products->isNotEmpty()
            ? $products->map(fn (Product $p) => $this->mapProduct($p, $category->name))->all()
            : Catalog::products();
    }

    protected function productsFor(Category $category)
    {
        return $category->products()
            ->where('status', 'active')
            ->inRandomOrder()
            ->limit(12)
            ->get();
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
        return view('ecomx-fashion.livewire.sections.skeletons.trending');
    }

    public function render()
    {
        return view('ecomx-fashion.livewire.sections.trending', [
            'trending' => $this->trending,
        ]);
    }
}
