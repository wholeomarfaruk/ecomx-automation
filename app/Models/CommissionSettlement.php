<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionSettlement extends Model
{
    protected $fillable = ['commission_scheme_id', 'master_profile_id', 'period_start', 'period_end', 'rate', 'amount', 'method', 'advance', 'journal_entry_id'];

    protected function casts(): array
    {
        return ['period_start' => 'date', 'period_end' => 'date', 'rate' => 'decimal:3', 'amount' => 'decimal:2', 'advance' => 'boolean'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(MasterProfile::class, 'master_profile_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
