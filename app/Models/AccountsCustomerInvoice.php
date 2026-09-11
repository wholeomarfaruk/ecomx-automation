<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class AccountsCustomerInvoice extends Model
{
    protected $fillable = [
        'customer_id', 'order_id', 'invoice_number', 'amount',
        'amount_allocated', 'status', 'journal_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'amount'            => 'decimal:2',
            'amount_allocated'  => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function allocations(): MorphMany
    {
        return $this->morphMany(AccountsPaymentAllocation::class, 'allocatable');
    }

    public function amountDue(): float
    {
        return round((float) $this->amount - (float) $this->amount_allocated, 2);
    }
}
