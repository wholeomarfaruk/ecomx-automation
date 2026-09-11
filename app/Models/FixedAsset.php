<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FixedAsset extends Model
{
    protected $fillable = [
        'name', 'category_account_id', 'cost', 'purchase_date',
        'useful_life_months', 'salvage_value', 'method',
        'accumulated_depreciation', 'status', 'journal_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'cost'                      => 'decimal:2',
            'purchase_date'             => 'date',
            'salvage_value'             => 'decimal:2',
            'accumulated_depreciation'  => 'decimal:2',
        ];
    }

    public function categoryAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'category_account_id');
    }

    public function purchaseJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function depreciationEntries(): HasMany
    {
        return $this->hasMany(FixedAssetDepreciationEntry::class);
    }

    public function disposal(): HasOne
    {
        return $this->hasOne(FixedAssetDisposal::class);
    }

    public function bookValue(): float
    {
        return round((float) $this->cost - (float) $this->accumulated_depreciation, 2);
    }

    /**
     * Straight-line monthly depreciation: (cost - salvage) / useful life.
     * Never depreciates past (cost - salvage) even if called more times
     * than useful_life_months (e.g. a late catch-up run) — the last run
     * absorbs any rounding remainder so accumulated depreciation lands
     * exactly on cost - salvage rather than drifting a few cents short.
     */
    public function monthlyDepreciationAmount(): float
    {
        $depreciableBase = (float) $this->cost - (float) $this->salvage_value;
        $monthly = round($depreciableBase / $this->useful_life_months, 2);

        $remaining = $depreciableBase - (float) $this->accumulated_depreciation;

        return max(0.0, min($monthly, $remaining));
    }
}
