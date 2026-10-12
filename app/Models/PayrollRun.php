<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    protected $fillable = [
        'month', 'gross', 'bonus', 'bonus_note', 'advance_deducted', 'net', 'paid', 'status',
        'due_on', 'journal_entry_id', 'created_by',
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
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function unpaid(): float
    {
        return max(0, round((float) $this->net - (float) $this->paid, 2));
    }
}
