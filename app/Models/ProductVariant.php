<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id', 'sku', 'combination_key',
        'price', 'sale_price', 'purchase_price', 'combo_price', 'stock_quantity',
        'reorder_level', 'reorder_quantity',
        'status', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price'             => 'decimal:2',
            'sale_price'        => 'decimal:2',
            'purchase_price'    => 'decimal:2',
            'combo_price'       => 'decimal:2',
            'stock_quantity'    => 'decimal:3',
            'reorder_level'     => 'decimal:3',
            'reorder_quantity'  => 'decimal:3',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(ProductVariantValue::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(ProductVariantMedia::class)->orderBy('sort_order');
    }

    public function links(): HasMany
    {
        return $this->hasMany(ProductVariantLink::class)->orderBy('sort_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * This variant's attribute selections as ['Color' => 'White', 'Size' =>
     * 'Unstitched'], for cart/wishlist/checkout line-item display and the
     * edit-variant modal's pre-selected colour/size. Loads `values` fresh if
     * not already eager-loaded.
     */
    protected function optionsMap(): Attribute
    {
        return Attribute::get(function () {
            $values = $this->relationLoaded('values')
                ? $this->values
                : $this->values()->with('productAttributeValue.attributeValue.attribute')->get();

            $map = [];

            foreach ($values as $variantValue) {
                $av = $variantValue->productAttributeValue?->attributeValue;
                $attrName = $av?->attribute?->name;

                if ($attrName && $av?->value) {
                    $map[$attrName] = $av->value;
                }
            }

            return $map;
        });
    }

    /**
     * This variant's own image (e.g. the White colour's photo), for cart/
     * wishlist/checkout line-item media — falls back to the product's
     * featured image at the call site when this is null. Loads `media`
     * fresh if not already eager-loaded.
     */
    protected function displayImage(): Attribute
    {
        return Attribute::get(function () {
            $items = $this->relationLoaded('media') ? $this->media : $this->media()->get();

            $primary = $items->firstWhere('is_primary', true) ?? $items->first();

            return $primary ? file_path($primary->media_id) : null;
        });
    }
}
