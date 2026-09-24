<?php

namespace App\Livewire\EcomxAnyniche;

use App\Livewire\Concerns\TogglesWishlist;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\EcomxAnyniche\Catalog;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Shop / catalogue page. Visual design ported from juwel-trade-corporation's
 * storefront.shop (jtc-shop, jtc-filters, jtc-toolbar, jtc-fdrawer classes),
 * but — unlike that page, whose filters were UI-only ("functionality lands
 * in a later pass") — this component's filtering (category/brand/size/offer/
 * price/search/sort/load-more) is fully live via Livewire #[Url]-bound
 * properties, same behaviour this page already had before the redesign.
 */
#[Layout('ecomx-anyniche.layouts.ecomx_anyniche')]
class Shop extends Component
{
    use TogglesWishlist;

    protected const DEFAULT_MIN_PRICE = 0;
    protected const DEFAULT_MAX_PRICE = 15000;
    protected const PER_PAGE = 24;

    #[Url(as: 'cat')] public array $cats = [];
    #[Url(as: 'brand')] public array $brands = [];
    #[Url(as: 'size')] public array $sizes = [];
    #[Url(as: 'offer')] public array $offers = [];
    #[Url(as: 'min')] public int $minPrice = self::DEFAULT_MIN_PRICE;
    #[Url(as: 'price')] public int $maxPrice = self::DEFAULT_MAX_PRICE;
    #[Url] public string $sort = 'Featured';
    #[Url] public string $q = '';

    public int $perPage = self::PER_PAGE;

    public array $allCats = [];
    public array $allBrands = [];
    public array $allSizes = [];
    public array $allOffers = ['flash_sale' => 'Flash Sale'];

    public function mount(): void
    {
        // ?offer[]= comes straight from the URL — keep only known filter keys
        // (e.g. drops the retired 'discount' option from old links).
        $this->offers = array_values(array_intersect($this->offers, array_keys($this->allOffers)));

        $this->allCats = Category::active()
            ->withCount('products')
            ->get()
            ->map(fn (Category $c) => ['id' => $c->id, 'name' => $c->name, 'count' => $c->products_count])
            ->all();

        $this->allBrands = Brand::active()
            ->withCount('products')
            ->get()
            ->map(fn (Brand $b) => ['id' => $b->id, 'name' => $b->name, 'count' => $b->products_count])
            ->all();

        $sizeAttribute = Attribute::where('name', 'Size')->first();
        $this->allSizes = $sizeAttribute
            ? $sizeAttribute->values()->orderBy('sort_order')->pluck('value')->unique()->values()->all()
            : [];
    }

    public function updating($property): void
    {
        if (in_array($property, ['cats', 'brands', 'sizes', 'offers', 'minPrice', 'maxPrice', 'sort', 'q'], true)) {
            $this->perPage = static::PER_PAGE;
        }
    }

    public function toggleCat(string $c): void
    {
        $this->cats = in_array($c, $this->cats) ? array_values(array_diff($this->cats, [$c])) : [...$this->cats, $c];
        $this->perPage = static::PER_PAGE;
    }

    public function toggleBrand(string $b): void
    {
        $this->brands = in_array($b, $this->brands) ? array_values(array_diff($this->brands, [$b])) : [...$this->brands, $b];
        $this->perPage = static::PER_PAGE;
    }

    public function toggleSize(string $s): void
    {
        $this->sizes = in_array($s, $this->sizes) ? array_values(array_diff($this->sizes, [$s])) : [...$this->sizes, $s];
        $this->perPage = static::PER_PAGE;
    }

    public function toggleOffer(string $o): void
    {
        if (! array_key_exists($o, $this->allOffers)) {
            return;
        }

        $this->offers = in_array($o, $this->offers) ? array_values(array_diff($this->offers, [$o])) : [...$this->offers, $o];
        $this->perPage = static::PER_PAGE;
    }

    public function clearAll(): void
    {
        $this->reset('cats', 'brands', 'sizes', 'offers', 'q');
        $this->minPrice = static::DEFAULT_MIN_PRICE;
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
            ->when(! empty($this->brands), fn ($q) => $q->whereHas(
                'brand',
                fn ($b) => $b->whereIn('name', $this->brands)
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
            ->where('price', '>=', $this->minPrice)
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

    public function render()
    {
        $items = $this->items;
        $total = $this->total;

        $siteName = \App\Models\Setting::get('site_name', 'AnyNiche') ?: 'AnyNiche';

        return view('ecomx-anyniche.livewire.shop', [
            'items' => $items->map(fn (Product $p) => Catalog::decorateProduct($p))->all(),
            'total' => $total,
            'hasMore' => $items->count() < $total,
        ])->layout('ecomx-anyniche.layouts.ecomx_anyniche', [
            'title' => "Shop — {$siteName}",
            'metaDescription' => 'Browse the full collection — new arrivals, best sellers, and flash sale picks.',
        ]);
    }
}
