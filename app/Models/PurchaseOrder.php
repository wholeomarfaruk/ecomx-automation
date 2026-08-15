<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_number', 'supplier_id', 'product_variant_id', 'quantity',
        'unit_price', 'total_amount', 'supplier_invoice_id', 'status',
        'order_date', 'deadline', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity'     => 'decimal:3',
            'unit_price'   => 'decimal:2',
            'total_amount' => 'decimal:2',
            'order_date'   => 'date',
            'deadline'     => 'date',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function supplierInvoice(): BelongsTo
    {
        return $this->belongsTo(SupplierInvoice::class);
    }
}
