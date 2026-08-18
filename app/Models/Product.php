<?php

namespace App\Models;

use App\Enums\Product\ProductType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')->orderBy('sort_order')->orderBy('name');
    }
}
