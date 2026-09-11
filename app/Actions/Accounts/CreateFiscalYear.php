<?php

namespace App\Actions\Accounts;

use App\Models\FiscalPeriod;
use App\Models\FiscalYear;
use Illuminate\Support\Carbon;

/**
 * Creates a fiscal year and its twelve monthly periods in one go — a
 * business only ever needs to think in terms of "this month is locked",
 * so periods are always generated monthly regardless of the year's actual
 * length.
 */
class CreateFiscalYear
{
    public function handle(string $name, string $startDate): FiscalYear
    {
        $start = Carbon::parse($startDate)->startOfMonth();
        $end = $start->copy()->addYear()->subDay();

        $fiscalYear = FiscalYear::create([
            'name'       => $name,
            'start_date' => $start->toDateString(),
            'end_date'   => $end->toDateString(),
        ]);

        $cursor = $start->copy();
        for ($i = 0; $i < 12; $i++) {
            FiscalPeriod::create([
                'fiscal_year_id' => $fiscalYear->id,
                'start_date'     => $cursor->toDateString(),
                'end_date'       => $cursor->copy()->endOfMonth()->toDateString(),
            ]);
            $cursor->addMonth();
        }

        return $fiscalYear;
    }
}
