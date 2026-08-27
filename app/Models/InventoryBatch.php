<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryBatch extends Model
{
    protected $fillable = [
        'warehouse_id', 'product_id', 'variant_id',
        'supplier_id', 'purchase_order_id',
        'batch_no', 'manufacture_date', 'expiry_date',
        'quantity', 'purchase_price', 'status',
    ];

    protected function casts(): array
    {
        return [
            'manufacture_date' => 'date',
            'expiry_date' => 'date',
            'quantity' => 'decimal:3',
            'purchase_price' => 'decimal:4',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryStockMovement::class, 'batch_id');
    }

    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')->where('quantity', '>', 0);
    }

    /** Earliest-expiry-first, for FEFO issuing. Batches with no expiry sort last. */
    public function scopeFefoOrder(Builder $query): Builder
    {
        return $query->orderByRaw('expiry_date IS NULL')->orderBy('expiry_date');
    }
}
