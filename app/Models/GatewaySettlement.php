<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GatewaySettlement extends Model
{
    protected $fillable = [
        'gateway_account_id', 'settled_at', 'gross_amount', 'fee_amount', 'net_amount', 'journal_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'settled_at'    => 'date',
            'gross_amount'  => 'decimal:2',
            'fee_amount'    => 'decimal:2',
            'net_amount'    => 'decimal:2',
        ];
    }

    public function gatewayAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'gateway_account_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
