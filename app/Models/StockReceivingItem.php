<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One received product / variant line of a GRN, with its own batch. */
class StockReceivingItem extends Model
{
    protected $fillable = [
        'stock_receiving_id', 'purchase_order_item_id', 'product_id', 'variant_id', 'batch_id', 'batch_no',
        'quantity', 'unit_cost', 'line_total', 'manufacture_date', 'expiry_date',
    ];

    protected function casts(): array
    {
        return [
            'quantity'         => 'decimal:3',
            'unit_cost'        => 'decimal:4',
            'line_total'       => 'decimal:2',
            'manufacture_date' => 'date',
            'expiry_date'      => 'date',
        ];
    }

    public function receiving(): BelongsTo
    {
        return $this->belongsTo(StockReceiving::class, 'stock_receiving_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'batch_id');
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }
}
