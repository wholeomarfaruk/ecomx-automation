<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    protected $fillable = [
        'payroll_run_id', 'master_profile_id', 'include', 'salary', 'house_rent', 'medical', 'conveyance', 'other_allowance',
        'ot_hours', 'ot_rate', 'ot_amount', 'bonus', 'incentive', 'arrears', 'gross', 'working_days', 'absent_days', 'absent_deduction',
        'advance_deduction', 'tax', 'other_deduction', 'total_deduction', 'net', 'paid_amount', 'status', 'advance_cuts', 'note',
    ];

    /** Sheet columns that are typed in (the rest are calculated). */
    public const INPUTS = ['salary', 'house_rent', 'medical', 'conveyance', 'other_allowance', 'ot_hours', 'ot_rate', 'bonus', 'incentive', 'arrears',
        'working_days', 'absent_days', 'advance_deduction', 'tax', 'other_deduction'];

    /**
     * The salary sheet maths for one employee:
     *   gross   = salary + allowances + overtime (hours × rate) + bonus + incentive + arrears
     *   absent  = (salary + allowances) ÷ working days × absent days
     *   net     = gross − absent − advance − tax − other deduction
     *
     * @return array{ot_amount: float, gross: float, absent_deduction: float, total_deduction: float, net: float}
     */
    public static function compute(array $r): array
    {
        $n = fn ($k) => max(0, round((float) ($r[$k] ?? 0), 2));
        $fixed = $n('salary') + $n('house_rent') + $n('medical') + $n('conveyance') + $n('other_allowance');
        $ot = round($n('ot_hours') * $n('ot_rate'), 2);
        $gross = round($fixed + $ot + $n('bonus') + $n('incentive') + $n('arrears'), 2);
        $days = max(1, (float) ($r['working_days'] ?? 30));
        $absent = round(min($fixed, $fixed / $days * min($days, $n('absent_days'))), 2);
        $deduct = round($absent + $n('advance_deduction') + $n('tax') + $n('other_deduction'), 2);

        return ['ot_amount' => $ot, 'gross' => $gross, 'absent_deduction' => $absent, 'total_deduction' => $deduct, 'net' => round($gross - $deduct, 2)];
    }

    protected function casts(): array
    {
        return [
            'include'           => 'boolean',
            'salary'            => 'decimal:2',
            'bonus'             => 'decimal:2',
            'gross'             => 'decimal:2',
            'advance_cuts'      => 'array',
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
