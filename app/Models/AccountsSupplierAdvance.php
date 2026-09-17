<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class AccountsSupplierAdvance extends Model
{
    protected $fillable = [
        'supplier_id', 'supplier_invoice_id', 'amount',
        'amount_applied', 'status', 'journal_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'amount'         => 'decimal:2',
            'amount_applied' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function supplierInvoice(): BelongsTo
    {
        return $this->belongsTo(SupplierInvoice::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function allocations(): MorphMany
    {
        return $this->morphMany(AccountsPaymentAllocation::class, 'allocatable');
    }

    public function amountRemaining(): float
    {
        return round((float) $this->amount - (float) $this->amount_applied, 2);
    }
}
