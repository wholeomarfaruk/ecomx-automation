<?php

namespace App\Models;

use App\Enums\Accounts\AccountType;
use App\Enums\Accounts\NormalBalance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'name', 'type', 'subtype', 'normal_balance', 'parent_id',
        'is_control_account', 'is_system', 'is_active', 'currency_id',
        'opening_balance', 'description',
    ];

    protected function casts(): array
    {
        return [
            'type'                => AccountType::class,
            'normal_balance'      => NormalBalance::class,
            'is_control_account'  => 'boolean',
            'is_system'           => 'boolean',
            'is_active'           => 'boolean',
            'opening_balance'     => 'decimal:2',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType(Builder $query, AccountType $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Net posted balance for this account: sum of debit-credit (or the
     * reverse, depending on normal_balance) across every journal_entry_line
     * against it, excluding void entries. This is always derived, never
     * stored, so it can never drift from the ledger.
     */
    public function balance(): float
    {
        $totals = $this->lines()
            ->whereHas('journalEntry', fn (Builder $q) => $q->where('status', '!=', 'void'))
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
            ->first();

        return $this->normal_balance->signedDelta(
            (float) $totals->total_debit,
            (float) $totals->total_credit,
        );
    }
}
