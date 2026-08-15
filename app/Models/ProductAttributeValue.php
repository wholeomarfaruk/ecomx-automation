<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttributeValue extends Model
{
    protected $fillable = ['product_attribute_id', 'attribute_value_id', 'sort_order'];

    public function productAttribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class);
    }

    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(AttributeValue::class);
    }
}
