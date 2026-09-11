<?php

namespace App\Actions\Accounts;

use App\Models\FiscalPeriod;

/**
 * Locks or reopens a fiscal period. PostJournalEntry already refuses to
 * post into a locked period (Golden Rule #7); this is the only place that
 * flips is_locked, and it's activity-logged so an unlock is auditable —
 * see plan decision (e): no DB triggers, just this narrowly-scoped Action
 * plus a permission gate on who can call it.
 */
class LockFiscalPeriod
{
    public function lock(FiscalPeriod $period): FiscalPeriod
    {
        $period->update([
            'is_locked' => true,
            'locked_at' => now(),
            'locked_by' => auth()->id(),
        ]);

        activity('accounts')
            ->causedBy(auth()->user())
            ->performedOn($period)
            ->event('locked')
            ->log("Fiscal period {$period->start_date->format('M Y')} was locked");

        return $period;
    }

    public function reopen(FiscalPeriod $period): FiscalPeriod
    {
        $period->update([
            'is_locked' => false,
            'locked_at' => null,
            'locked_by' => null,
        ]);

        activity('accounts')
            ->causedBy(auth()->user())
            ->performedOn($period)
            ->event('reopened')
            ->log("Fiscal period {$period->start_date->format('M Y')} was reopened");

        return $period;
    }
}
