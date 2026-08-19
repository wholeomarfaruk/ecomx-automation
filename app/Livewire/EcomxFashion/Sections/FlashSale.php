<?php

namespace App\Livewire\EcomxFashion\Sections;

use App\Livewire\Concerns\TogglesWishlist;
use App\Models\Product;
use App\Support\EcomxFashion\Catalog;
use App\Support\EcomxFashion\PageSectionConfigRegistry;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class FlashSale extends Component
{
    use TogglesWishlist;

    protected const DEFAULT_BADGE = '⚡ Flash Sale';
    protected const DEFAULT_HEADING = 'Up to 40% off — ends in';
    protected const DEFAULT_BUTTON_LABEL = 'View all deals →';

    public array $flashSale = [];
    public string $badge = self::DEFAULT_BADGE;
    public string $heading = self::DEFAULT_HEADING;
    public string $buttonLabel = self::DEFAULT_BUTTON_LABEL;

    public function mount(): void
    {
        $config = PageSectionConfigRegistry::find('home', 'flash-sale');
        $this->badge = $config['badge'] ?? '' ?: static::DEFAULT_BADGE;
        $this->heading = $config['heading'] ?? '' ?: static::DEFAULT_HEADING;
        $this->buttonLabel = $config['buttonLabel'] ?? '' ?: static::DEFAULT_BUTTON_LABEL;

        $discounted = Product::where('status', 'active')
            ->whereNotNull('sale_price')
            ->inRandomOrder()
            ->limit(8)
            ->get();

        $this->flashSale = $discounted->isNotEmpty()
            ? $discounted->map(fn (Product $p) => $this->mapProduct($p))->all()
            : Catalog::flashSale();
    }

    protected function mapProduct(Product $p): array
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
            'name' => $p->name,
            'url' => $p->url,
            'off' => $p->price > 0 ? (int) round((1 - $p->sale_price / $p->price) * 100) : 0,
            'price' => (float) $p->price,
            'sale' => (float) $p->sale_price,
            'img' => $p->featured_image,
            'colors' => $colorValues->pluck('swatch_value')->filter()->values()->all(),
            'is_wished' => $p->isWishedBy(request()->attributes->get('device')),
        ];
    }

    public function placeholder()
    {
        return view('ecomx-fashion.livewire.sections.skeletons.flash-sale');
    }

    public function render()
    {
        return view('ecomx-fashion.livewire.sections.flash-sale');
    }
}
