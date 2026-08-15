<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierInvoice extends Model
{
    protected $fillable = [
        'supplier_id', 'serial_number', 'invoice_number', 'type',
        'amount', 'is_adjusted', 'invoice_date', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount'       => 'decimal:2',
            'is_adjusted'  => 'boolean',
            'invoice_date' => 'date',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierInvoiceItem::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    protected static function booted(): void
    {
        static::created(function (SupplierInvoice $invoice) {
            $invoice->supplier()->increment('balance', Supplier::balanceDelta($invoice->type, (float) $invoice->amount));
        });

        static::updating(function (SupplierInvoice $invoice) {
            if ($invoice->isDirty(['type', 'amount', 'supplier_id'])) {
                $originalDelta = Supplier::balanceDelta($invoice->getOriginal('type'), (float) $invoice->getOriginal('amount'));
                $newDelta      = Supplier::balanceDelta($invoice->type, (float) $invoice->amount);

                Supplier::whereKey($invoice->getOriginal('supplier_id'))->decrement('balance', $originalDelta);
                Supplier::whereKey($invoice->supplier_id)->increment('balance', $newDelta);
            }
        });

        static::deleted(function (SupplierInvoice $invoice) {
            $invoice->supplier()->decrement('balance', Supplier::balanceDelta($invoice->type, (float) $invoice->amount));
        });
    }
}
