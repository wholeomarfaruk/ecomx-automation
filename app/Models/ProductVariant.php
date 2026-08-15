<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id', 'sku', 'combination_key',
        'price', 'sale_price', 'purchase_price', 'stock_quantity',
        'status', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price'          => 'decimal:2',
            'sale_price'     => 'decimal:2',
            'purchase_price' => 'decimal:2',
            'stock_quantity' => 'decimal:3',
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
}
