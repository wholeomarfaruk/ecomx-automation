<?php

namespace App\Models;

use App\Enums\Purchase\SupplierInvoiceType;
use App\Exceptions\Purchase\SupplierInvoiceDeletionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierInvoice extends Model
{
    protected $fillable = [
        'supplier_id', 'serial_number', 'invoice_number', 'type',
        'amount', 'is_adjusted', 'invoice_date', 'notes', 'document_ids',
    ];

    protected function casts(): array
    {
        return [
            'type'         => SupplierInvoiceType::class,
            'amount'       => 'decimal:2',
            'is_adjusted'  => 'boolean',
            'invoice_date' => 'date',
            'document_ids' => 'array',
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
            $invoice->supplier()->increment('balance', $invoice->type->signedAmount((float) $invoice->amount));
        });

        static::updating(function (SupplierInvoice $invoice) {
            if ($invoice->isDirty(['type', 'amount', 'supplier_id'])) {
                $originalType   = SupplierInvoiceType::from($invoice->getOriginal('type'));
                $originalDelta  = $originalType->signedAmount((float) $invoice->getOriginal('amount'));
                $newDelta       = $invoice->type->signedAmount((float) $invoice->amount);

                Supplier::whereKey($invoice->getOriginal('supplier_id'))->decrement('balance', $originalDelta);
                Supplier::whereKey($invoice->supplier_id)->increment('balance', $newDelta);
            }
        });

        static::deleting(function (SupplierInvoice $invoice) {
            if ($invoice->purchaseOrders()->exists()) {
                throw SupplierInvoiceDeletionException::linkedToPurchaseOrder();
            }

            if (! $invoice->isLatestSerial()) {
                throw SupplierInvoiceDeletionException::notLatestSerial();
            }
        });

        static::deleted(function (SupplierInvoice $invoice) {
            $invoice->supplier()->decrement('balance', $invoice->type->signedAmount((float) $invoice->amount));
        });
    }

    /**
     * Whether this is the most recently recorded invoice for its supplier
     * (the only one currently eligible for deletion).
     */
    public function isLatestSerial(): bool
    {
        return $this->serial_number == static::where('supplier_id', $this->supplier_id)->max('serial_number');
    }
}
