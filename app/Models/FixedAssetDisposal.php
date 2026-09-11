<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedAssetDisposal extends Model
{
    protected $fillable = [
        'fixed_asset_id', 'disposed_at', 'proceeds', 'book_value',
        'gain_loss_amount', 'journal_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'disposed_at'       => 'date',
            'proceeds'          => 'decimal:2',
            'book_value'        => 'decimal:2',
            'gain_loss_amount'  => 'decimal:2',
        ];
    }

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
