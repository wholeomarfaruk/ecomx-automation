<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductComboItem extends Model
{
    protected $fillable = [
        'combo_product_id', 'product_id', 'variant_id', 'allow_variant', 'quantity', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'allow_variant' => 'boolean',
            'quantity'      => 'decimal:3',
        ];
    }

    public function comboProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'combo_product_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
