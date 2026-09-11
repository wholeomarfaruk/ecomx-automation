<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class RecurringExpense extends Model
{
    protected $fillable = [
        'name', 'account_id', 'from_account_id', 'amount', 'cadence',
        'next_run_date', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount'        => 'decimal:2',
            'next_run_date' => 'date',
            'is_active'     => 'boolean',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }

    public function scopeDue(Builder $query, ?string $asOf = null): Builder
    {
        return $query->where('is_active', true)->where('next_run_date', '<=', $asOf ?? now()->toDateString());
    }

    public function advanceNextRunDate(): void
    {
        $next = match ($this->cadence) {
            'weekly'  => Carbon::parse($this->next_run_date)->addWeek(),
            'yearly'  => Carbon::parse($this->next_run_date)->addYear(),
            default   => Carbon::parse($this->next_run_date)->addMonth(),
        };

        $this->update(['next_run_date' => $next->toDateString()]);
    }
}
