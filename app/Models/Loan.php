<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    protected $fillable = [
        'name', 'lender', 'principal', 'interest_rate', 'start_date',
        'term_months', 'loan_payable_account_id', 'status',
        'master_profile_id', 'lender_type', 'total_payable', 'rate_type', 'frequency',
        'installments', 'custom_offsets', 'support_rule', 'note',
    ];

    protected function casts(): array
    {
        return [
            'principal'      => 'decimal:2',
            'interest_rate'  => 'decimal:4',
            'start_date'     => 'date',
            'total_payable'  => 'decimal:2',
            'custom_offsets' => 'array',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(MasterProfile::class, 'master_profile_id');
    }

    public function payableAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'loan_payable_account_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(LoanRepaymentSchedule::class);
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(LoanRepayment::class);
    }

    public function outstandingPrincipal(): float
    {
        return round((float) $this->principal - (float) $this->repayments()->sum('principal_amount'), 2);
    }
}
