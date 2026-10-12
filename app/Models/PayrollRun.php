<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    protected $fillable = [
        'month', 'gross', 'bonus', 'bonus_note', 'note', 'advance_deducted', 'other_deducted', 'net', 'paid', 'status',
        'due_on', 'working_days', 'journal_entry_id', 'created_by', 'posted_by', 'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'month'            => 'date',
            'gross'            => 'decimal:2',
            'bonus'            => 'decimal:2',
            'advance_deducted' => 'decimal:2',
            'net'              => 'decimal:2',
            'paid'             => 'decimal:2',
            'due_on'           => 'date',
            'posted_at'        => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    /** Draft = the salary sheet is still being filled in; nothing is in the books yet. */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function postedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function unpaid(): float
    {
        return max(0, round((float) $this->net - (float) $this->paid, 2));
    }
}
