<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttributeValue extends Model
{
    protected $fillable = ['product_attribute_id', 'attribute_value_id', 'swatch_image_id', 'sort_order'];

    public function productAttribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class);
    }

    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(AttributeValue::class);
    }

    public function swatchImage(): BelongsTo
    {
        return $this->belongsTo(File::class, 'swatch_image_id');
    }

    /**
     * Per-product swatch image URL, if set on this product↔value row —
     * overrides the attribute value's own global swatch_type/swatch_value
     * for this product only. Null when this product uses the global swatch.
     */
    public function getSwatchImageUrlAttribute(): ?string
    {
        return $this->swatch_image_id ? file_path($this->swatch_image_id) : null;
    }
}
