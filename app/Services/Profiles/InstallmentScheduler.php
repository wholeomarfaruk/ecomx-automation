<?php

namespace App\Services\Profiles;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Builds a kisti schedule. Frequencies: monthly, weekly, daily, yearly, or
 * custom (explicit day offsets from the start date, e.g. [15, 45, 90]).
 * The first kisti falls one period after the start date.
 *
 * Interest (loans only):
 *   none      — total = principal, split evenly
 *   flat      — total = principal + profit; profit is either given
 *               (total payable) or principal × rate% × term-in-years
 *   reducing  — classic EMI on the declining balance (annual rate%)
 */
class InstallmentScheduler
{
    public const FREQUENCIES = ['monthly', 'weekly', 'daily', 'yearly', 'custom'];

    protected const PERIODS_PER_YEAR = ['monthly' => 12, 'weekly' => 52, 'daily' => 365, 'yearly' => 1];

    /**
     * @return list<array{no: int, due_date: Carbon, principal: float, interest: float, amount: float}>
     */
    public function build(
        string $frequency,
        int $count,
        string $startDate,
        float $principal,
        string $rateType = 'none',
        ?float $rate = null,
        ?float $totalPayable = null,
        array $customOffsets = [],
    ): array {
        if (! in_array($frequency, self::FREQUENCIES, true)) {
            throw new InvalidArgumentException("Unknown frequency {$frequency}.");
        }

        $offsets = array_values(array_filter(array_map('intval', $customOffsets), fn ($d) => $d > 0));
        if ($frequency === 'custom') {
            sort($offsets);
            $count = count($offsets);
        }

        if ($count < 1 || $principal <= 0) {
            return [];
        }

        $dates = $this->dates($frequency, $count, Carbon::parse($startDate), $offsets);

        if ($rateType === 'reducing' && $rate > 0 && $frequency !== 'custom') {
            return $this->reducing($dates, $principal, $rate / 100 / self::PERIODS_PER_YEAR[$frequency]);
        }

        $total = $principal;
        if ($rateType === 'flat') {
            $total = $totalPayable && $totalPayable > $principal
                ? $totalPayable
                : $principal + $principal * (($rate ?? 0) / 100) * $this->years($frequency, $count, $offsets);
        }

        return $this->even($dates, $principal, round($total - $principal, 2));
    }

    /** Equal kisti; rounding remainder goes on the last one. */
    protected function even(array $dates, float $principal, float $interest): array
    {
        $n = count($dates);
        $p = floor($principal / $n * 100) / 100;
        $i = floor($interest / $n * 100) / 100;

        return array_map(function ($date, $k) use ($n, $p, $i, $principal, $interest) {
            $last = $k === $n - 1;
            $pp = $last ? round($principal - $p * ($n - 1), 2) : $p;
            $ii = $last ? round($interest - $i * ($n - 1), 2) : $i;

            return ['no' => $k + 1, 'due_date' => $date, 'principal' => $pp, 'interest' => $ii, 'amount' => round($pp + $ii, 2)];
        }, $dates, array_keys($dates));
    }

    protected function reducing(array $dates, float $principal, float $i): array
    {
        $n = count($dates);
        $emi = round($principal * $i / (1 - (1 + $i) ** -$n), 2);
        $balance = $principal;
        $rows = [];

        foreach ($dates as $k => $date) {
            $interest = round($balance * $i, 2);
            $p = $k === $n - 1 ? round($balance, 2) : round($emi - $interest, 2);
            $balance -= $p;
            $rows[] = ['no' => $k + 1, 'due_date' => $date, 'principal' => $p, 'interest' => $interest, 'amount' => round($p + $interest, 2)];
        }

        return $rows;
    }

    /** @return list<CarbonInterface> */
    protected function dates(string $frequency, int $count, CarbonInterface $start, array $offsets): array
    {
        if ($frequency === 'custom') {
            return array_map(fn ($d) => $start->copy()->addDays($d), $offsets);
        }

        return array_map(fn ($k) => match ($frequency) {
            'monthly' => $start->copy()->addMonthsNoOverflow($k),
            'weekly'  => $start->copy()->addWeeks($k),
            'daily'   => $start->copy()->addDays($k),
            'yearly'  => $start->copy()->addYearsNoOverflow($k),
        }, range(1, $count));
    }

    protected function years(string $frequency, int $count, array $offsets): float
    {
        return $frequency === 'custom'
            ? (max($offsets ?: [0]) / 365)
            : $count / self::PERIODS_PER_YEAR[$frequency];
    }

    /** "Monthly · 12 kisti", "Custom · 15/45/90" */
    public static function ruleLabel(string $frequency, ?int $count, ?array $offsets = null): string
    {
        if ($frequency === 'custom') {
            return $offsets ? 'Custom · ' . implode('/', $offsets) : 'Custom · when possible';
        }

        return ucfirst($frequency) . ($count ? " · {$count} kisti" : '');
    }
}
