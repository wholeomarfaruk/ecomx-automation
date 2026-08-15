<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariantLink extends Model
{
    protected $fillable = ['product_variant_id', 'linked_product_id', 'linked_variant_id', 'link_type', 'sort_order'];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function linkedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'linked_product_id');
    }

    public function linkedVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'linked_variant_id');
    }
}
