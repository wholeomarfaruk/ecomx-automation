<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id', 'product_id', 'product_variant_id',
        'quantity', 'unit_price', 'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(SupplierInvoiceItem::class);
    }

    /**
     * How much of this PO line has already been billed across every invoice
     * line linked to it — the invoicing equivalent of
     * StockService::receivedQuantityForPurchaseOrderItem(). Sums amount, not
     * quantity, since a manual/adjusted invoice line may carry an amount
     * without a quantity.
     */
    public function invoicedAmount(): float
    {
        return (float) $this->invoiceItems()->sum('amount');
    }
}
