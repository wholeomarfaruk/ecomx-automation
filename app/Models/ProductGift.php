<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductGift extends Model
{
    protected $fillable = [
        'product_id', 'gift_product_id', 'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function giftProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'gift_product_id');
    }
}
