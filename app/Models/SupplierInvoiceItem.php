<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierInvoiceItem extends Model
{
    protected $fillable = [
        'supplier_invoice_id', 'product_variant_id', 'name', 'quantity', 'unit_price', 'amount',
    ];

    protected function casts(): array
    {
        return [
            'quantity'   => 'decimal:3',
            'unit_price' => 'decimal:2',
            'amount'     => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SupplierInvoice::class, 'supplier_invoice_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
