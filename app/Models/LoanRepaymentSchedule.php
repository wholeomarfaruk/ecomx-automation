<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LoanRepaymentSchedule extends Model
{
    protected $fillable = [
        'loan_id', 'due_date', 'principal_due', 'interest_due', 'status',
    ];

    protected function casts(): array
    {
        return [
            'due_date'      => 'date',
            'principal_due' => 'decimal:2',
            'interest_due'  => 'decimal:2',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function repayment(): HasOne
    {
        return $this->hasOne(LoanRepayment::class, 'schedule_id');
    }

    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_date->isPast();
    }
}
