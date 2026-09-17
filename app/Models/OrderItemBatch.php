<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemBatch extends Model
{
    protected $fillable = [
        'order_item_id', 'inventory_batch_id', 'quantity', 'unit_cost', 'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity'  => 'decimal:3',
            'unit_cost' => 'decimal:4',
            'posted_at' => 'datetime',
        ];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'inventory_batch_id');
    }
}
