<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankReconciliation extends Model
{
    protected $fillable = [
        'account_id', 'statement_date', 'statement_balance', 'book_balance', 'status',
    ];

    protected function casts(): array
    {
        return [
            'statement_date'     => 'date',
            'statement_balance'  => 'decimal:2',
            'book_balance'       => 'decimal:2',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BankReconciliationLine::class);
    }

    public function difference(): float
    {
        return round((float) $this->statement_balance - (float) $this->book_balance, 2);
    }
}
