<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One delivered order line (and how many of it) an exchange takes back. */
class OrderExchangeItem extends Model
{
    protected $fillable = ['order_exchange_id', 'order_item_id', 'quantity'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
        ];
    }

    public function exchange(): BelongsTo
    {
        return $this->belongsTo(OrderExchange::class, 'order_exchange_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
