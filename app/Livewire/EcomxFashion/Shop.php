<?php

namespace App\Livewire\EcomxFashion;

use App\Livewire\Concerns\TogglesWishlist;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('ecomx-fashion.layouts.ecomx_fashion')]
class Shop extends Component
{
    use TogglesWishlist;

    protected const DEFAULT_MAX_PRICE = 15000;
    protected const PER_PAGE = 24;

    #[Url(as: 'cat')] public array $cats = [];
    #[Url(as: 'size')] public array $sizes = [];
    #[Url(as: 'offer')] public array $offers = [];
    #[Url(as: 'price')] public int $maxPrice = self::DEFAULT_MAX_PRICE;
    #[Url] public string $sort = 'Featured';
    #[Url] public string $q = '';

    public int $perPage = self::PER_PAGE;

    public array $allCats = [];
    public array $allSizes = [];
    public array $allOffers = ['flash_sale' => 'Flash Sale', 'discount' => 'Discount'];

    public function mount(): void
    {
        $this->allCats = Category::active()
            ->pluck('name')
            ->all();

        $sizeAttribute = Attribute::where('name', 'Size')->first();
        $this->allSizes = $sizeAttribute
            ? $sizeAttribute->values()->orderBy('sort_order')->pluck('value')->unique()->values()->all()
            : [];
    }

    public function updating($property): void
    {
        if (in_array($property, ['cats', 'sizes', 'offers', 'maxPrice', 'sort', 'q'], true)) {
            $this->perPage = static::PER_PAGE;
        }
    }

    public function toggleCat(string $c): void
    {
        $this->cats = in_array($c, $this->cats) ? array_values(array_diff($this->cats, [$c])) : [...$this->cats, $c];
        $this->perPage = static::PER_PAGE;
    }

    public function toggleSize(string $s): void
    {
        $this->sizes = in_array($s, $this->sizes) ? array_values(array_diff($this->sizes, [$s])) : [...$this->sizes, $s];
        $this->perPage = static::PER_PAGE;
    }

    public function toggleOffer(string $o): void
    {
        $this->offers = in_array($o, $this->offers) ? array_values(array_diff($this->offers, [$o])) : [...$this->offers, $o];
        $this->perPage = static::PER_PAGE;
    }

    public function clearAll(): void
    {
        $this->reset('cats', 'sizes', 'offers', 'q');
        $this->maxPrice = static::DEFAULT_MAX_PRICE;
        $this->perPage = static::PER_PAGE;
    }

    public function loadMore(): void
    {
        $this->perPage += static::PER_PAGE;
    }

    protected function query()
    {
        $query = Product::where('status', 'active')
            ->when(! empty($this->cats), fn ($q) => $q->whereHas(
                'categories',
                fn ($c) => $c->whereIn('name', $this->cats)
            ))
            ->when(! empty($this->sizes), fn ($q) => $q->whereHas(
                'variants',
                fn ($v) => $v->where('status', 'active')->whereHas(
                    'values.productAttributeValue.attributeValue',
                    fn ($val) => $val->whereIn('value', $this->sizes)
                        ->whereHas('attribute', fn ($a) => $a->where('name', 'Size'))
                )
            ))
            ->when(! empty($this->offers), fn ($q) => $q->whereNotNull('sale_price'))
            ->when($this->q !== '', fn ($q) => $q->where('name', 'like', '%' . $this->q . '%'))
            ->where('price', '<=', $this->maxPrice);

        return match ($this->sort) {
            'Price: low to high' => $query->orderBy('price'),
            'Price: high to low' => $query->orderByDesc('price'),
            'Newest' => $query->orderByDesc('created_at'),
            default => $query->orderByDesc('id'),
        };
    }

    public function getTotalProperty(): int
    {
        return $this->query()->count();
    }

    public function getItemsProperty()
    {
        return $this->query()
            ->with(['categories', 'variants.values.productAttributeValue.attributeValue.attribute'])
            ->limit($this->perPage)
            ->get();
    }

    protected function mapProduct(Product $p): array
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
            'url' => $p->url,
            'price' => (float) $p->price,
            'sale' => $p->sale_price !== null ? (float) $p->sale_price : null,
            'tag' => $p->sale_price !== null ? 'Sale' : '',
            'cat' => $p->categories->first()->name ?? '',
            'img' => $p->featured_image,
            'colors' => $colorValues->pluck('swatch_value')->filter()->values()->all(),
            'is_wished' => $p->isWishedBy(request()->attributes->get('device')),
        ];
    }

    public function render()
    {
        $items = $this->items;
        $total = $this->total;

        $siteName = \App\Models\Setting::get('site_name', 'Seldom Fashion') ?: 'Seldom Fashion';

        return view('ecomx-fashion.livewire.shop', [
            'items' => $items->map(fn (Product $p) => $this->mapProduct($p))->all(),
            'total' => $total,
            'hasMore' => $items->count() < $total,
        ])->layout('ecomx-fashion.layouts.ecomx_fashion', [
            'title' => "Shop — {$siteName}",
            'metaDescription' => 'Browse the full collection — new arrivals, best sellers, and flash sale picks.',
        ]);
    }
}
