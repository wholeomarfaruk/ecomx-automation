<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierCodSettlement extends Model
{
    protected $fillable = [
        'courier_id', 'settled_at', 'gross_amount', 'fee_amount', 'net_amount', 'journal_entry_id',
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

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
