<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionAccrual extends Model
{
    protected $fillable = ['commission_scheme_id', 'master_profile_id', 'month', 'eligible', 'rate', 'amount', 'journal_entry_id'];

    protected function casts(): array
    {
        return ['month' => 'date', 'eligible' => 'decimal:2', 'rate' => 'decimal:3', 'amount' => 'decimal:2'];
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
