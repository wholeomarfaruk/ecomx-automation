<?php

namespace App\Models;

use App\Enums\Product\ProductType;
use App\Http\Middleware\DeviceTracker;
use App\Services\OfferService;
use App\Services\StockService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'name', 'slug', 'short_description', 'description',
        'brand_id', 'status', 'featured', 'stock_status',
        'product_type', 'combo_allowed', 'gift_allowed',
        'price', 'sale_price', 'purchase_price', 'combo_price', 'stock_quantity',
        'featured_image_id', 'image_ids', 'video_ids',
        'weight', 'length', 'width', 'height',
        'meta_image_id', 'meta_title', 'meta_description', 'meta_keywords',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'featured'       => 'boolean',
            'combo_allowed'  => 'boolean',
            'gift_allowed'   => 'boolean',
            'product_type'   => ProductType::class,
            'image_ids'      => 'array',
            'video_ids'      => 'array',
            'price'          => 'decimal:2',
            'sale_price'     => 'decimal:2',
            'purchase_price' => 'decimal:2',
            'combo_price'    => 'decimal:2',
            'stock_quantity' => 'decimal:3',
            'weight'        => 'decimal:3',
            'length'         => 'decimal:3',
            'width'          => 'decimal:3',
            'height'         => 'decimal:3',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_category_pivot')
            ->using(ProductCategoryPivot::class)
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(File::class, 'featured_image_id');
    }

    public function getFeaturedImageAttribute(): ?string
    {
        return $this->featured_image_id ? file_path($this->featured_image_id) : null;
    }

    public function metaImage(): BelongsTo
    {
        return $this->belongsTo(File::class, 'meta_image_id');
    }

    public function productAttributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function attributeOrder(): HasMany
    {
        return $this->hasMany(ProductAttributeOrder::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function comboItems(): HasMany
    {
        return $this->hasMany(ProductComboItem::class, 'combo_product_id')->orderBy('sort_order');
    }

    public function gifts(): HasMany
    {
        return $this->hasMany(ProductGift::class);
    }

    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function reviewStatistic(): HasOne
    {
        return $this->hasOne(ProductReviewStatistic::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Single source of truth for what one unit (of $variant, or of the
     * product itself) costs on the storefront:
     *  - regular:    the variant's own price, falling back to the product's
     *                (a variant's price is optional in the admin);
     *  - selling:    the minimum of regular and sale price — a sale price only
     *                counts when it's set, above 0 and below regular. Variant
     *                sale prices pair with the variant's own price; a variant
     *                without its own price inherits the product's sale too.
     *                This is what the cart stores (CartManager).
     *  - discounted: selling further reduced by active offers that lower a
     *                single unit with no cart-level conditions
     *                (OfferService::unitPrice()). Display-only — at checkout
     *                OfferService::evaluate() applies offers as their own
     *                lines on top of the stored selling price, which lands on
     *                the same number, so it's never deducted twice.
     *
     * @return array{regular: float, selling: float, discounted: float}
     */
    public function unitPricing(?ProductVariant $variant = null): array
    {
        $regular = $this->regularPrice($variant);
        $selling = $this->sellingPrice($variant);

        return [
            'regular'    => $regular,
            'selling'    => $selling,
            'discounted' => app(OfferService::class)->unitPrice($this, $selling, $variant?->id),
        ];
    }

    public function regularPrice(?ProductVariant $variant = null): float
    {
        return (float) ($variant?->price ?? $this->price ?? 0);
    }

    public function sellingPrice(?ProductVariant $variant = null): float
    {
        $regular = $this->regularPrice($variant);

        $sale = $variant && $variant->price !== null
            ? $variant->sale_price
            : ($variant?->sale_price ?? $this->sale_price);

        $sale = $sale !== null ? (float) $sale : null;

        return $sale !== null && $sale > 0 && $sale < $regular ? $sale : $regular;
    }

    /** discounted_price: one unit of the product itself after sale price and per-unit offers — see unitPricing(). */
    protected function discountedPrice(): Attribute
    {
        return Attribute::get(fn () => $this->unitPricing()['discounted']);
    }

    /** min_price: the lowest price this product can be bought for — see cardPricing(). */
    protected function minPrice(): Attribute
    {
        return Attribute::get(fn () => $this->cardPricing()['sale'] ?? $this->cardPricing()['price']);
    }

    /**
     * SQL twin of cardPricing()'s pre-offer price — the lowest selling price
     * (sellingPrice() rules: a sale price only counts when > 0 and below
     * regular; a variant without its own price inherits the product's price
     * and sale) across active variants, in-stock ones first, falling back to
     * the product's own selling price. For storefront price filters/sorts on
     * a query over `products` (unaliased). Offers aren't included — they're
     * evaluated in PHP (OfferService) and can't be expressed in SQL.
     */
    public static function minSellingPriceSql(): string
    {
        $productSelling = '(CASE WHEN products.sale_price > 0 AND products.sale_price < COALESCE(products.price, 0)'
            . ' THEN products.sale_price ELSE COALESCE(products.price, 0) END)';

        $variantRegular = 'COALESCE(pv.price, products.price, 0)';
        $variantSale = '(CASE WHEN pv.price IS NOT NULL THEN pv.sale_price ELSE COALESCE(pv.sale_price, products.sale_price) END)';
        $variantSelling = "(CASE WHEN {$variantSale} > 0 AND {$variantSale} < {$variantRegular} THEN {$variantSale} ELSE {$variantRegular} END)";

        $variantMin = fn (string $extra) => "(SELECT MIN({$variantSelling}) FROM product_variants pv"
            . " WHERE pv.product_id = products.id AND pv.status = 'active' AND pv.deleted_at IS NULL{$extra})";

        return 'COALESCE(' . $variantMin(' AND pv.stock_quantity > 0') . ', ' . $variantMin('') . ', ' . $productSelling . ')';
    }

    /** @var array{price: float, sale: ?float}|null memoized cardPricing() */
    protected ?array $cardPricingCache = null;

    /**
     * Price shape for product cards/search results: the cheapest option
     * (lowest discounted price) across the product's active variants — in
     * stock ones first, so a sold-out cheap variant doesn't set the headline
     * price — or the product itself when it has no active variants.
     * 'price' is that option's regular price, 'sale' its discounted price
     * when that's actually lower (null otherwise, i.e. nothing to strike).
     *
     * Eager-load 'variants' before calling this in a list to avoid N+1s.
     *
     * @return array{price: float, sale: ?float}
     */
    public function cardPricing(): array
    {
        if ($this->cardPricingCache !== null) {
            return $this->cardPricingCache;
        }

        $variants = $this->variants->where('status', 'active');
        $inStock = $variants->filter(fn (ProductVariant $v) => (float) $v->stock_quantity > 0);
        $options = ($inStock->isNotEmpty() ? $inStock : $variants)
            ->map(fn (ProductVariant $v) => $this->unitPricing($v));

        if ($options->isEmpty()) {
            $options = collect([$this->unitPricing()]);
        }

        $cheapest = $options->sortBy('discounted')->first();

        return $this->cardPricingCache = [
            'price' => $cheapest['regular'],
            'sale'  => $cheapest['discounted'] < $cheapest['regular'] ? $cheapest['discounted'] : null,
        ];
    }

    /**
     * Most a cart may hold of this product when no variant is involved: its
     * own stock_quantity for a simple product while the Inventory module is
     * off (that column is the balance then — see StockService::usesOwnStock()),
     * otherwise null (not capped at the cart).
     */
    public function ownStockLimit(): ?float
    {
        return $this->product_type === ProductType::SIMPLE && app(StockService::class)->usesOwnStock()
            ? (float) $this->stock_quantity
            : null;
    }

    /**
     * Displayable stock figure for the admin product list, computed per
     * product_type since only variants and simple products carry real stock:
     * - simple: the default warehouse's inventory_stocks balance (variant_id
     *   null), via StockService — mirrors app/Livewire/Admin/Inventory/StockList.php.
     *   With the Inventory module off, StockService falls back to the
     *   product's own stock_quantity column instead.
     * - variable: sum of stock_quantity across variants (that column is a
     *   synced read cache — see StockService::syncVariantCache()), plus how
     *   many variants make up the total.
     * - combo: not tracked directly (per StockService::commitOrder()'s
     *   "combo stock is derived from components" comment) — computed here as
     *   how many bundles could be assembled right now, i.e. the minimum
     *   across components of floor(component stock / quantity needed).
     *   A component with 'allow_variant' unset/false pins one variant; when
     *   true the customer picks at checkout, so the component's own total
     *   (variable: summed, simple: its own stock) is used instead.
     *
     * Eager-load 'variants' (variable) or 'comboItems.product.variants' +
     * 'comboItems.variant' (combo) before calling this in a list to avoid
     * N+1s; simple products hit inventory_stocks directly while the Inventory
     * module is on.
     */
    protected function stockInfo(): Attribute
    {
        return Attribute::get(function () {
            return match ($this->product_type) {
                ProductType::SIMPLE => [
                    'quantity'      => app(StockService::class)->available($this, null),
                    'variant_count' => null,
                ],
                ProductType::VARIABLE => [
                    'quantity'      => (float) $this->variants->sum('stock_quantity'),
                    'variant_count' => $this->variants->count(),
                ],
                ProductType::COMBO => [
                    'quantity'      => $this->comboAvailableQuantity(),
                    'variant_count' => null,
                ],
            };
        });
    }

    /**
     * How many bundles of this combo product could be assembled right now —
     * the minimum across its components of floor(component's available
     * stock / quantity needed per bundle). A combo with no components yet
     * has no defined availability.
     */
    public function comboAvailableQuantity(): ?float
    {
        $items = $this->relationLoaded('comboItems')
            ? $this->comboItems
            : $this->comboItems()->with('product.variants', 'variant')->get();

        if ($items->isEmpty()) {
            return null;
        }

        $stockService = app(StockService::class);
        $possible = null;

        foreach ($items as $item) {
            $needed = (float) $item->quantity;

            if ($needed <= 0) {
                continue;
            }

            $componentStock = match (true) {
                ! $item->allow_variant && $item->variant
                    => (float) $item->variant->stock_quantity,
                $item->product->product_type === ProductType::VARIABLE
                    => (float) $item->product->variants->sum('stock_quantity'),
                default
                    => $stockService->available($item->product, null),
            };

            $bundlesFromThis = floor($componentStock / $needed);
            $possible = $possible === null ? $bundlesFromThis : min($possible, $bundlesFromThis);
        }

        return $possible;
    }

    /**
     * Whether this product is in the given device's wishlist. Pass the
     * Device the DeviceTracker middleware already resolved for this request
     * (request()->attributes->get('device')) — not a cookie value.
     *
     * Pass $variantId to check one specific variant line (e.g. the buy box's
     * currently selected colour/size) instead of "any variant of this
     * product", which is what product-card grids without a variant picker want.
     */
    public function isWishedBy(Device|int|null $device, ?int $variantId = null): bool
    {
        if (! $device) {
            return false;
        }

        $deviceId = $device instanceof Device ? $device->id : $device;

        return $this->wishlistItems()
            ->when($variantId !== null, fn ($q) => $q->where('variant_id', $variantId))
            ->whereHas('wishlist', fn ($q) => $q->where('device_id', $deviceId))
            ->exists();
    }
}
