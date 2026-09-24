<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderOffer extends Model
{
    protected $fillable = [
        'order_id', 'promotion_id', 'offer_id', 'name', 'discount_amount', 'shipping_discount',
    ];

    protected function casts(): array
    {
        return [
            'discount_amount'   => 'decimal:2',
            'shipping_discount' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }
}
