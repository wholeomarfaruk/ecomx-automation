<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Installment extends Model
{
    protected $fillable = [
        'installment_plan_id', 'no', 'due_date', 'amount', 'adjusted_amount', 'paid_amount',
        'support_amount', 'status', 'paid_at', 'journal_entry_id', 'late_fee_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date'        => 'date',
            'amount'          => 'decimal:2',
            'adjusted_amount' => 'decimal:2',
            'paid_amount'     => 'decimal:2',
            'support_amount'  => 'decimal:2',
            'paid_at'         => 'datetime',
            'late_fee_at'     => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(InstallmentPlan::class, 'installment_plan_id');
    }
}
