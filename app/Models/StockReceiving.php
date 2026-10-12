<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Goods receiving (GRN): one delivery, many product / variant lines. */
class StockReceiving extends Model
{
    protected $fillable = [
        'grn_number', 'warehouse_id', 'supplier_id', 'purchase_order_id', 'received_at', 'reference', 'note',
        'status', 'total_quantity', 'total_cost', 'line_count', 'created_by', 'cancelled_by', 'cancelled_at', 'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'received_at'    => 'datetime',
            'cancelled_at'   => 'datetime',
            'total_quantity' => 'decimal:3',
            'total_cost'     => 'decimal:2',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockReceivingItem::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
