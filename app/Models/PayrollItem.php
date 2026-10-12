<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    protected $fillable = [
        'payroll_run_id', 'master_profile_id', 'salary', 'bonus', 'advance_deduction', 'net', 'paid_amount', 'status',
    ];

    protected function casts(): array
    {
        return [
            'salary'            => 'decimal:2',
            'bonus'             => 'decimal:2',
            'advance_deduction' => 'decimal:2',
            'net'               => 'decimal:2',
            'paid_amount'       => 'decimal:2',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(MasterProfile::class, 'master_profile_id');
    }

    public function unpaid(): float
    {
        return max(0, round((float) $this->net - (float) $this->paid_amount, 2));
    }
}
