<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LoanRepaymentSchedule extends Model
{
    protected $fillable = [
        'loan_id', 'due_date', 'principal_due', 'interest_due', 'status',
        'installment_no', 'adjusted_amount', 'paid_amount', 'support_amount',
    ];

    protected function casts(): array
    {
        return [
            'due_date'        => 'date',
            'principal_due'   => 'decimal:2',
            'interest_due'    => 'decimal:2',
            'adjusted_amount' => 'decimal:2',
            'paid_amount'     => 'decimal:2',
            'support_amount'  => 'decimal:2',
        ];
    }

    /** EMI before any support adjustment. */
    public function emi(): float
    {
        return round((float) $this->principal_due + (float) $this->interest_due, 2);
    }

    /** What is due on this kisti after a support (over)payment on the previous one. */
    public function payable(): float
    {
        return round($this->adjusted_amount !== null ? (float) $this->adjusted_amount : $this->emi(), 2);
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
