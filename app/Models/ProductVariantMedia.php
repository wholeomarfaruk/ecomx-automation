<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariantMedia extends Model
{
    protected $fillable = ['product_variant_id', 'media_id', 'is_primary', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(File::class, 'media_id');
    }
}
