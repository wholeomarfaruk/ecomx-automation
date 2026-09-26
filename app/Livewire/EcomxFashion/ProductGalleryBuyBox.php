<?php

namespace App\Livewire\EcomxFashion;

use App\Livewire\Concerns\TogglesWishlist;
use App\Models\Product as ProductModel;
use App\Models\ProductVariant;
use App\Services\OfferService;
use Livewire\Attributes\Lazy;
use Livewire\Component;

/**
 * Above-the-fold gallery + colour/size/add-to-cart box for the ecomx-fashion
 * product page. #[Lazy] paints its skeleton in the initial response, then
 * loads for real on the immediate follow-up request — keeps the
 * variant/media queries off the first-byte critical path.
 */
#[Lazy]
class ProductGalleryBuyBox extends Component
{
    use TogglesWishlist;

    public int $productId;
    public bool $flashSale = false;

    public array $product = [
        'name' => '',
        'cat' => '',
        'price' => 0,
        'sale' => 0,
        'desc' => '',
        'shortDesc' => '',
    ];

    public array $media = [];
    public int $activeIndex = 0;

    /** The product's own gallery (image_ids/video_ids) — the fallback shown
     *  whenever the selected colour has no dedicated variant images of its own. */
    public array $defaultMedia = [];

    public array $colors = [];
    public bool $hasColors = false;
    public int $selectedColorIndex = 0;

    public array $sizes = [];
    public bool $hasSizes = false;
    public ?string $selectedSize = null;

    public bool $hasRealVariants = false;
    public array $variantMatrix = [];

    /** Active offers covering this product (OfferService::offersForProduct()), shown as badges. */
    public array $offers = [];

    public bool $showSizePrompt = false;
    public bool $showSizeGuide = false;
    public bool $addedToCart = false;

    public function mount(int $productId): void
    {
        $this->productId = $productId;

        $p = ProductModel::findOrFail($productId);

        // Prices from Product::unitPricing(): 'sale' is the discounted price
        // (sale price + per-unit offers) when it's below regular, else 0.
        // flashSale stays tied to a real sale price (drives the flash UI).
        $pricing = $p->unitPricing();
        $this->flashSale = $p->sellingPrice() < $pricing['regular'];
        $this->offers = app(OfferService::class)->offersForProduct($p);
        $this->product = [
            'name' => $p->name,
            'cat' => $p->categories->first()->name ?? '',
            'price' => $pricing['regular'],
            'sale' => $pricing['discounted'] < $pricing['regular'] ? $pricing['discounted'] : 0,
            'desc' => $p->description ?: '',
            'shortDesc' => $p->short_description ?: '',
        ];

        $this->media = $this->buildMedia($p);
        $this->defaultMedia = $this->media;

        $this->loadRealVariants($p);

        if (count($this->sizes) === 1) {
            $this->selectedSize = $this->sizes[0];
        }

        // Selected colour defaults to index 0 (see loadRealVariants) — if
        // that colour has its own gallery images, show them from first paint
        // instead of the general product gallery, matching what changing
        // colour would do.
        if ($this->hasColors) {
            $this->applyColorMedia($this->selectedColorIndex);
        }
    }

    public function selectImage(int $index): void
    {
        if (isset($this->media[$index])) {
            $this->activeIndex = $index;
        }
    }

    public function selectColor(int $index): void
    {
        if (! isset($this->colors[$index])) {
            return;
        }

        $this->selectedColorIndex = $index;
        $this->applyColorMedia($index);

        if ($this->selectedSize && $this->isSizeOutOfStock($this->selectedSize)) {
            $this->selectedSize = null;
        }
    }

    /**
     * Swaps the whole gallery to the selected colour's own images when it
     * has any (set per-variant in the admin), falling back to the product's
     * general gallery otherwise — the standard "gallery follows the colour"
     * pattern (Shopify, most storefronts): pick a colour, see that colour,
     * not just the same photos with the swatch highlighted differently.
     * The gallery markup is keyed by a hash of $media (see
     * product-gallery-buy-box.blade.php), so changing it here makes
     * Livewire remount the whole gallery block — Swiper reinitializes
     * against the new slides automatically, no manual JS slide surgery.
     */
    private function applyColorMedia(int $colorIndex): void
    {
        $colorMedia = $this->colors[$colorIndex]['media'] ?? [];

        $this->media = ! empty($colorMedia) ? $colorMedia : $this->defaultMedia;
        $this->activeIndex = 0;
    }

    public function selectSize(string $size): void
    {
        $this->selectedSize = $size;
        $this->showSizePrompt = false;
    }

    public function toggleSizeGuide(): void
    {
        $this->showSizeGuide = ! $this->showSizeGuide;
    }

    public function isSizeOutOfStock(string $size): bool
    {
        if (! $this->hasRealVariants) {
            return false;
        }

        $colorKey = $this->hasColors ? ($this->colors[$this->selectedColorIndex]['name'] ?? '*') : '*';
        $variant = $this->variantMatrix[$colorKey . '|' . $size] ?? null;

        return $variant !== null && $variant['stock'] <= 0;
    }

    public function getSelectedVariantIdProperty(): ?int
    {
        return $this->selectedVariant['variantId'] ?? null;
    }

    /**
     * The full variantMatrix row for the current colour+size pick — carries
     * variantId, price, salePrice, stock. Null if this product has no real
     * variants, or the current pick doesn't match a generated combination.
     */
    public function getSelectedVariantProperty(): ?array
    {
        if (! $this->hasRealVariants) {
            return null;
        }

        $colorKey = $this->hasColors ? ($this->colors[$this->selectedColorIndex]['name'] ?? '*') : '*';
        $sizeKey = $this->hasSizes ? ($this->selectedSize ?? '*') : '*';

        return $this->variantMatrix[$colorKey . '|' . $sizeKey] ?? null;
    }

    /**
     * Price to display for the current colour+size pick — the selected
     * variant's discounted price (Product::unitPricing(): sale price +
     * per-unit offers; a variant without its own price inherits the
     * product's), else the product's. The cart charges the pre-offer selling
     * price and applies offers at checkout, which lands on the same total.
     */
    public function getCurrentPriceProperty(): float
    {
        $variant = $this->selectedVariant;

        if ($variant) {
            return $variant['salePrice'] ?? $variant['price'];
        }

        return $this->product['sale'] ?: $this->product['price'];
    }

    public function getCurrentComparePriceProperty(): ?float
    {
        $variant = $this->selectedVariant;

        if ($variant) {
            return $variant['salePrice'] !== null ? $variant['price'] : null;
        }

        return $this->product['sale'] ? $this->product['price'] : null;
    }

    public function addToCart(bool $checkout = false): void
    {
        if ($this->hasSizes && ! $this->selectedSize) {
            $this->showSizePrompt = true;
            return;
        }

        $this->dispatch('add-to-cart', productId: $this->productId, variantId: $this->selectedVariantId, checkout: $checkout);
        $this->addedToCart = ! $checkout;
    }

    /** Add to cart, then CartManager redirects straight to checkout. */
    public function buyNow(): void
    {
        $this->addToCart(true);
    }

    /**
     * Full gallery: featured image first, then the rest of the product's
     * image gallery, then any videos — deduped so the featured image isn't
     * repeated if it's also listed in image_ids.
     */
    private function buildMedia(ProductModel $p): array
    {
        $imageIds = collect($p->image_ids ?? [])
            ->reject(fn ($id) => (int) $id === (int) $p->featured_image_id)
            ->when($p->featured_image_id, fn ($ids) => $ids->prepend($p->featured_image_id))
            ->unique();

        $media = $imageIds
            ->map(fn ($id) => file_path($id))
            ->filter()
            ->map(fn ($url) => ['img' => $url, 'video' => false])
            ->values();

        $videos = collect($p->video_ids ?? [])
            ->map(fn ($id) => file_path($id))
            ->filter()
            ->map(fn ($url) => ['img' => $url, 'video' => true]);

        return $media->concat($videos)->all();
    }

    /**
     * A variant's own gallery images (set per-variant from the admin variant
     * editor — see ProductVariants::saveVariant()/variantImageIds), resolved
     * to the same ['img'=>url,'video'=>bool] shape as the product's general
     * gallery. Empty when this variant has none — applyColorMedia() falls
     * back to the product's general gallery in that case.
     */
    private function variantGalleryMedia(ProductVariant $variant): array
    {
        return $variant->media
            ->sortBy('sort_order')
            ->map(fn ($vm) => $vm->media ? ['img' => file_path($vm->media_id), 'video' => $vm->media->type === 'video'] : null)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Builds colour/size pickers + the variant price/stock matrix from real
     * ProductAttribute/ProductVariant data, so addToCart() can resolve a
     * real variant id. Products with no variants render with no colour/size
     * pickers at all — add-to-cart still uses the real product id, just
     * with no variantId.
     */
    private function loadRealVariants(ProductModel $p): void
    {
        $variants = $p->variants()
            ->with(
                'values.productAttributeValue.attributeValue.attribute',
                'values.productAttributeValue.swatchImage',
                'media.media',
            )
            ->where('status', 'active')
            ->get();

        if ($variants->isEmpty()) {
            return;
        }

        $colorValues = [];
        $colorVariant = []; // AttributeValue id => first ProductVariant carrying that colour (for its gallery images)
        $sizeValues = [];

        foreach ($variants as $variant) {
            foreach ($variant->values as $variantValue) {
                $pav = $variantValue->productAttributeValue;
                $av = $pav?->attributeValue;
                if (! $av) {
                    continue;
                }

                $attrName = $av->attribute?->name;
                if ($attrName === 'Color' && ! isset($colorValues[$av->id])) {
                    $colorValues[$av->id] = $pav;
                    $colorVariant[$av->id] = $variant;
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
            $this->hasColors = true;
            $this->colors = collect($colorValues)
                ->sortBy('sort_order')
                ->values()
                ->map(fn ($pav) => [
                    'name' => $pav->attributeValue->value,
                    'hex' => $pav->attributeValue->swatch_value ?: '#CCCCCC',
                    'image' => $pav->swatch_image_url,
                    'media' => $this->variantGalleryMedia($colorVariant[$pav->attribute_value_id]),
                ])->all();
        }

        if (! empty($sizeValues)) {
            $this->hasSizes = true;
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
            // Regular price (inherits the product's when the variant has
            // none) and discounted price — see Product::unitPricing().
            $variantPricing = $p->unitPricing($variant);
            $this->variantMatrix[$key] = [
                'variantId' => $variant->id,
                'price' => $variantPricing['regular'],
                'salePrice' => $variantPricing['discounted'] < $variantPricing['regular'] ? $variantPricing['discounted'] : null,
                'stock' => $variant->stock_quantity,
            ];
        }
    }

    public function getIsWishedProperty(): bool
    {
        return ProductModel::find($this->productId)?->isWishedBy(request()->attributes->get('device'), $this->selectedVariantId) ?? false;
    }

    public function placeholder()
    {
        return view('ecomx-fashion.livewire.product-gallery-buy-box-placeholder');
    }

    public function render()
    {
        return view('ecomx-fashion.livewire.product-gallery-buy-box');
    }
}
