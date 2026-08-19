<?php

namespace App\Livewire\EcomxFashion;

use App\Livewire\Concerns\TogglesWishlist;
use App\Models\Product as ProductModel;
use App\Support\EcomxFashion\Catalog;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('ecomx-fashion.layouts.ecomx_fashion')]
class Product extends Component
{
    use TogglesWishlist;

    public string $slug = 'sculpted-wool-coat';
    public bool $flashSale = true;
    public ?int $productId = null;

    public array $product = [
        'name' => 'Sculpted Wool Coat',
        'cat' => 'Outerwear',
        'price' => 12900,
        'sale' => 9900,
        'desc' => 'Cut from double-faced Italian wool with a sculpted shoulder and hidden closure. Fully lined in cupro, finished by hand in our Dhaka atelier. Falls just below the knee.',
    ];

    public array $media = [
        ['img'=>'photo-1539109136881-3be0616acf4b','video'=>false],
        ['img'=>'photo-1483985988355-763728e1935b','video'=>false],
        ['img'=>'photo-1445205170230-053b83016050','video'=>false],
        ['img'=>'photo-1524504388940-b1c1722653e1','video'=>false],
        ['img'=>'photo-1509631179647-0177331693ae','video'=>true],
    ];

    public array $colors = [
        ['name'=>'Camel','hex'=>'#C8B49A','main'=>'photo-1539109136881-3be0616acf4b'],
        ['name'=>'Noir','hex'=>'#111111','main'=>'photo-1483985988355-763728e1935b'],
        ['name'=>'Sage','hex'=>'#6B6F63','main'=>'photo-1445205170230-053b83016050'],
        ['name'=>'Camel / Noir','hex'=>'#C8B49A','hex2'=>'#111111','main'=>'photo-1524504388940-b1c1722653e1'],
    ];

    public array $sizes = ['XS','S','M','L','XL','2XL','Unstitched','Free Size'];

    public bool $hasRealVariants = false;
    public array $variantMatrix = [];

    public array $specs = [
        ['Material','100% Italian wool, 480 gsm'],
        ['Lining','Cupro'],
        ['Fit','Regular, falls below knee'],
        ['Closure','Hidden two-button'],
        ['Origin','Made in Dhaka, Bangladesh'],
        ['Care','Dry clean only'],
        ['SKU','SF-WC-1042'],
    ];

    public function mount(?string $slug = null): void
    {
        if ($slug) $this->slug = $slug;

        // Gallery/colours/sizes below stay demo data (not wired to real
        // ProductAttribute/ProductVariant records yet) — this only swaps in
        // the real product's identity/price/description where one exists,
        // so actions that need a genuine product_id (wishlist, add-to-cart)
        // have one to work with.
        $p = ProductModel::where('slug', $this->slug)->where('status', 'active')->first();

        if ($p) {
            $this->productId = $p->id;
            $this->flashSale = (bool) $p->sale_price;
            $this->product = [
                'name' => $p->name,
                'cat' => $p->categories->first()->name ?? $this->product['cat'],
                'price' => (float) $p->price,
                'sale' => $p->sale_price ? (float) $p->sale_price : $this->product['sale'],
                'desc' => $p->description ?: $this->product['desc'],
            ];

            $this->loadRealVariants($p);
        }
    }

    /**
     * Swaps the demo colours/sizes for real ProductAttribute/ProductVariant
     * data when the product actually has variants — same matrix shape
     * ProductGalleryBuyBox (legacy theme) builds, so addToCart() can resolve
     * a real variant id. Products with no variants keep the demo colour/size
     * pickers untouched (cosmetic only — add-to-cart still uses the real
     * product id, just with no variantId).
     */
    private function loadRealVariants(ProductModel $p): void
    {
        $variants = $p->variants()
            ->with('values.productAttributeValue.attributeValue.attribute')
            ->where('status', 'active')
            ->get();

        if ($variants->isEmpty()) {
            return;
        }

        $colorValues = [];
        $sizeValues = [];

        foreach ($variants as $variant) {
            foreach ($variant->values as $variantValue) {
                $av = $variantValue->productAttributeValue?->attributeValue;
                if (! $av) {
                    continue;
                }

                $attrName = $av->attribute?->name;
                if ($attrName === 'Color' && ! isset($colorValues[$av->id])) {
                    $colorValues[$av->id] = $av;
                } elseif ($attrName === 'Size' && ! isset($sizeValues[$av->id])) {
                    $sizeValues[$av->id] = $av;
                }
            }
        }

        if (empty($colorValues) && empty($sizeValues)) {
            return;
        }

        $this->hasRealVariants = true;

        if (! empty($colorValues)) {
            $this->colors = collect($colorValues)
                ->sortBy('sort_order')
                ->values()
                ->map(fn ($v) => [
                    'name' => $v->value,
                    'hex' => $v->swatch_value ?: '#CCCCCC',
                    'main' => null,
                ])->all();
        }

        if (! empty($sizeValues)) {
            $this->sizes = collect($sizeValues)
                ->sortBy('sort_order')
                ->values()
                ->pluck('value')
                ->all();
        }

        foreach ($variants as $variant) {
            $colorName = null;
            $sizeName = null;
            foreach ($variant->values as $variantValue) {
                $av = $variantValue->productAttributeValue?->attributeValue;
                if (! $av) {
                    continue;
                }

                if ($av->attribute?->name === 'Color') {
                    $colorName = $av->value;
                } elseif ($av->attribute?->name === 'Size') {
                    $sizeName = $av->value;
                }
            }

            $key = ($colorName ?? '*') . '|' . ($sizeName ?? '*');
            $this->variantMatrix[$key] = [
                'variantId' => $variant->id,
                'price' => (float) $variant->price,
                'salePrice' => $variant->sale_price !== null ? (float) $variant->sale_price : null,
                'stock' => $variant->stock_quantity,
            ];
        }
    }

    public function getIsWishedProperty(): bool
    {
        if (! $this->productId) {
            return false;
        }

        return ProductModel::find($this->productId)?->isWishedBy(request()->attributes->get('device')) ?? false;
    }

    public function render()
    {
        $all = Catalog::products();
        return view('ecomx-fashion.livewire.product', [
            'reviews' => Catalog::reviews(),
            'related' => array_slice($all, 1),
        ]);
    }
}
