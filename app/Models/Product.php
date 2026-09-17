<?php

namespace App\Models;

use App\Enums\Product\ProductType;
use App\Http\Middleware\DeviceTracker;
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
        'price', 'sale_price', 'purchase_price', 'combo_price',
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
            'weight'         => 'decimal:3',
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
     * Displayable stock figure for the admin product list, computed per
     * product_type since only variants and simple products carry real stock:
     * - simple: the default warehouse's inventory_stocks balance (variant_id
     *   null), via StockService — mirrors app/Livewire/Admin/Inventory/StockList.php.
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
     * N+1s; simple products always hit inventory_stocks directly since they
     * have no stock_quantity column of their own.
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
