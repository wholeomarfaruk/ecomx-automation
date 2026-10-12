<?php

namespace App\Services\Accounts;

use Carbon\CarbonImmutable;

/**
 * Kisti schedule for the Loans & EMI plan builder (spec §5.8 / §7.6).
 * The first kisti falls on the first due date; custom = day offsets from it.
 *
 *   flat      total = principal + principal × rate% × span (years)
 *   reducing  EMI at the periodic rate (custom → treated as flat)
 *   none      total = principal
 *
 * Every kisti is the total ÷ count rounded UP to ৳10; the last one takes
 * whatever remains. Each row splits principal / interest (reducing: interest
 * on the balance; otherwise pro-rata).
 */
class KistiBuilder
{
    public const FREQUENCIES = ['monthly' => 'Monthly', 'weekly' => 'Weekly', 'daily' => 'Daily', 'yearly' => 'Yearly', 'custom' => 'Custom days'];

    protected const PER_YEAR = ['monthly' => 12, 'weekly' => 52, 'daily' => 365, 'yearly' => 1];

    /**
     * @return array{rows: list<array{no: int, due: CarbonImmutable, amount: float, principal: float, interest: float, left: float}>,
     *               total: float, interest: float, kisti: float, last: float, count: int, end: ?CarbonImmutable}
     */
    public function build(string $frequency, int $count, string $firstDue, float $principal, string $rateType = 'none', float $rate = 0, string $offsets = ''): array
    {
        $start = CarbonImmutable::parse($firstDue)->startOfDay();
        $dates = $this->dates($frequency, $count, $start, $offsets);
        $n = count($dates);
        $principal = max(0, round($principal, 2));

        if (! $n || $principal <= 0) {
            return ['rows' => [], 'total' => 0.0, 'interest' => 0.0, 'kisti' => 0.0, 'last' => 0.0, 'count' => 0, 'end' => null];
        }

        $r = max(0, $rate) / 100;
        $reducing = $rateType === 'reducing' && $r > 0 && $frequency !== 'custom';
        $span = $frequency === 'custom' ? $start->diffInDays(end($dates)) / 365 : $n / self::PER_YEAR[$frequency];

        $total = match (true) {
            $reducing => ($i = $r / self::PER_YEAR[$frequency]) > 0 ? $principal * $i / (1 - (1 + $i) ** -$n) * $n : $principal,
            in_array($rateType, ['flat', 'reducing'], true) => $principal + $principal * $r * $span,
            default => $principal,
        };
        $total = round($total);
        $total = max($total, $principal);

        $kisti = ceil($total / $n / 10) * 10;
        $amounts = [];
        $rest = $total;
        foreach (range(0, $n - 1) as $k) {
            $amounts[] = $k < $n - 1 ? min($kisti, $rest) : $rest;
            $rest = max(0, $rest - end($amounts));
        }
        // Rounding up can leave the tail empty (e.g. ৳100 over 12): drop the empty kisti.
        $amounts = array_values(array_filter($amounts, fn ($a) => $a > 0));
        $dates = array_slice($dates, 0, count($amounts));
        $n = count($amounts);

        $interestTotal = round($total - $principal, 2);
        $balance = $principal;
        $left = $total;
        $interestLeft = $interestTotal;
        $rows = [];

        foreach ($amounts as $k => $amount) {
            $last = $k === $n - 1;
            $interest = match (true) {
                $last => $interestLeft,
                $reducing => min($interestLeft, round($balance * $r / self::PER_YEAR[$frequency], 2)),
                default => min($interestLeft, round($interestTotal * $amount / $total, 2)),
            };
            $interest = max(0, min($interest, $amount));
            $p = round($amount - $interest, 2);
            $balance = round($balance - $p, 2);
            $interestLeft = round($interestLeft - $interest, 2);
            $left = round($left - $amount, 2);

            $rows[] = ['no' => $k + 1, 'due' => $dates[$k], 'amount' => (float) $amount, 'principal' => $p, 'interest' => $interest, 'left' => max(0, $left)];
        }

        return [
            'rows' => $rows, 'total' => (float) $total, 'interest' => $interestTotal,
            'kisti' => (float) $amounts[0], 'last' => (float) end($amounts), 'count' => $n, 'end' => end($dates),
        ];
    }

    /** "15, 45, 90" → [15, 45, 90] */
    public static function offsets(string $offsets): array
    {
        $o = array_values(array_unique(array_filter(array_map('intval', preg_split('/[\s,\/]+/', $offsets) ?: []), fn ($d) => $d >= 0)));
        sort($o);

        return $o;
    }

    /** @return list<CarbonImmutable> */
    protected function dates(string $frequency, int $count, CarbonImmutable $start, string $offsets): array
    {
        if ($frequency === 'custom') {
            return array_map(fn ($d) => $start->addDays($d), self::offsets($offsets) ?: [0]);
        }

        $count = min(max($count, 1), 600);

        return array_map(fn ($k) => match ($frequency) {
            'weekly' => $start->addWeeks($k),
            'daily'  => $start->addDays($k),
            'yearly' => $start->addYearsNoOverflow($k),
            default  => $start->addMonthsNoOverflow($k),
        }, range(0, $count - 1));
    }
}
