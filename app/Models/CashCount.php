<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashCount extends Model
{
    public const DENOMINATIONS = ['n1000' => ['৳1000', 1000], 'n500' => ['৳500', 500], 'n200' => ['৳200', 200], 'n100' => ['৳100', 100], 'n50' => ['৳50', 50], 'n20' => ['৳20', 20], 'n10' => ['৳10', 10], 'coin' => ['Coins (৳)', 1]];

    protected $fillable = ['account_id', 'count_date', 'denominations', 'counted', 'book', 'difference', 'adjustment_journal_entry_id', 'user_id'];

    protected function casts(): array
    {
        return ['count_date' => 'date', 'denominations' => 'array', 'counted' => 'decimal:2', 'book' => 'decimal:2', 'difference' => 'decimal:2'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'adjustment_journal_entry_id');
    }
}
