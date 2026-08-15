<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttributeOrder extends Model
{
    protected $table = 'product_attribute_order';

    protected $fillable = ['product_id', 'product_attribute_id', 'sort_order'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productAttribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class);
    }
}
