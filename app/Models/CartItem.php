<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id', 'product_id', 'variant_id', 'combo_id', 'is_gift', 'quantity', 'price',
    ];

    protected function casts(): array
    {
        return [
            'is_gift'  => 'boolean',
            'quantity' => 'decimal:3',
            'price'    => 'decimal:2',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function combo(): BelongsTo
    {
        return $this->belongsTo(Combo::class);
    }
}
