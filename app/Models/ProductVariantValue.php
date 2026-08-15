<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariantValue extends Model
{
    protected $fillable = ['product_variant_id', 'product_attribute_value_id'];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function productAttributeValue(): BelongsTo
    {
        return $this->belongsTo(ProductAttributeValue::class);
    }
}
