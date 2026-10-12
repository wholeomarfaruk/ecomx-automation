<?php

namespace App\Actions\Profiles;

use App\Models\Installment;
use App\Models\InstallmentPlan;
use App\Models\MasterProfile;
use App\Services\Profiles\InstallmentScheduler;
use App\Support\Bdt;
use Illuminate\Support\Facades\DB;

/**
 * Customer / supplier kisti plans. Creating a plan only schedules the
 * money already owed (the sale or purchase posted the receivable /
 * payable); payments against it post through the normal Collect / Pay
 * vouchers and are then spread over the kisti here.
 *
 * Support payment: paying more than a kisti's adjusted amount stores the
 * extra as support_amount. With support_rule = next_kisti it lowers the
 * next kisti's adjusted_amount; with principal it is taken off the last
 * kisti instead (shortening the plan).
 */
class InstallmentPlans
{
    public function __construct(protected InstallmentScheduler $scheduler) {}

    public function create(
        MasterProfile $profile,
        string $role,
        float $total,
        string $frequency,
        int $count,
        string $startDate,
        array $customOffsets = [],
        string $supportRule = 'next_kisti',
        ?string $reference = null,
    ): InstallmentPlan {
        $rows = $this->scheduler->build($frequency, $count, $startDate, $total, customOffsets: $customOffsets);

        if (! $rows) {
            throw new \InvalidArgumentException('Plan needs at least one kisti and an amount.');
        }

        return DB::transaction(function () use ($profile, $role, $total, $frequency, $startDate, $customOffsets, $supportRule, $reference, $rows) {
            $plan = InstallmentPlan::create([
                'master_profile_id' => $profile->id,
                'role'              => $role,
                'reference'         => $reference,
                'frequency'         => $frequency,
                'count'             => count($rows),
                'amount'            => $rows[0]['amount'],
                'total'             => $total,
                'start_date'        => $startDate,
                'custom_offsets'    => $frequency === 'custom' ? array_values(array_map('intval', $customOffsets)) : null,
                'support_rule'      => $supportRule,
                'status'            => 'active',
                'created_by'        => auth()->id(),
            ]);

            foreach ($rows as $r) {
                $plan->installments()->create([
                    'no'              => $r['no'],
                    'due_date'        => $r['due_date']->toDateString(),
                    'amount'          => $r['amount'],
                    'adjusted_amount' => $r['amount'],
                    'status'          => 'upcoming',
                ]);
            }

            $profile->logActivity('kisti', ucfirst($frequency) . " kisti plan created · {$plan->count} × " . Bdt::money($plan->amount));

            return $plan;
        });
    }

    /**
     * Spread a payment over the profile's active plan for this role,
     * oldest open kisti first. Returns what couldn't be applied.
     */
    public function applyPayment(MasterProfile $profile, string $role, float $amount, ?int $journalEntryId = null): float
    {
        $plan = InstallmentPlan::where('master_profile_id', $profile->id)->where('role', $role)
            ->where('status', 'active')->oldest()->first();

        if (! $plan || $amount <= 0) {
            return $amount;
        }

        return DB::transaction(function () use ($plan, $amount, $journalEntryId) {
            $kisti = $plan->installments()->where('status', '!=', 'paid')->orderBy('no')->first();

            if (! $kisti) {
                return $amount;
            }

            // The payment lands on the first open kisti; anything above what
            // it still needs is support, carried to later kisti by the rule.
            $due   = max(0, round((float) $kisti->adjusted_amount - (float) $kisti->paid_amount, 2));
            $extra = max(0, round($amount - $due, 2));
            $paid  = round((float) $kisti->paid_amount + $amount, 2);

            $kisti->update([
                'paid_amount'      => $paid,
                'support_amount'   => round((float) $kisti->support_amount + $extra, 2),
                'status'           => $paid + 0.005 >= (float) $kisti->adjusted_amount ? 'paid' : 'partial',
                'paid_at'          => now(),
                'journal_entry_id' => $journalEntryId ?? $kisti->journal_entry_id,
            ]);

            $unapplied = $extra > 0 ? $this->carrySupport($plan, $kisti, $extra) : 0.0;

            if (! $plan->installments()->where('status', '!=', 'paid')->exists()) {
                $plan->update(['status' => 'completed']);
            }

            return $unapplied;
        });
    }

    /** Move a support (over)payment onto later kisti per the plan's rule. */
    protected function carrySupport(InstallmentPlan $plan, Installment $paid, float $extra): float
    {
        if ($plan->support_rule === 'advance') {
            return 0.0; // kept on the kisti as support / advance, adjusted later by hand
        }

        $later = $plan->installments()->where('no', '>', $paid->no)->where('status', '!=', 'paid')->orderBy('no')->get();

        if ($plan->support_rule === 'principal') {
            $later = $later->reverse();
        }

        foreach ($later as $kisti) {
            if ($extra <= 0) {
                break;
            }

            $cut = min($extra, (float) $kisti->adjusted_amount);
            $kisti->update(['adjusted_amount' => round((float) $kisti->adjusted_amount - $cut, 2)]);
            $extra = round($extra - $cut, 2);

            if ((float) $kisti->adjusted_amount <= (float) $kisti->paid_amount + 0.005) {
                $kisti->update(['status' => 'paid', 'paid_at' => now()]);
            }
        }

        return max(0, $extra);
    }

    /** upcoming / due / overdue for display; paid & partial come from the row. */
    public static function statusOf(Installment $k): string
    {
        if (in_array($k->status, ['paid', 'partial'], true)) {
            return $k->status === 'partial' && $k->due_date->isPast() ? 'overdue' : $k->status;
        }

        return match (true) {
            $k->due_date->lt(today()) => 'overdue',
            $k->due_date->lte(today()->addDays(7)) => 'due',
            default => 'upcoming',
        };
    }
}
