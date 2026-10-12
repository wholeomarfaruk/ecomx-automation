<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Period helpers for the Accounts Panel: Saturday-start weeks and the
 * July–June financial year (both configurable in Settings), plus the
 * period pills shared by the dashboard, Transactions and Reports.
 */
class AccountsPeriod
{
    public const PILLS = [
        'today'     => 'Today',
        'yesterday' => 'Yesterday',
        'week'      => 'This week',
        'month'     => 'This month',
        'year'      => 'This year',
        'custom'    => 'Custom',
    ];

    public const BN_WEEKDAYS = ['রবিবার', 'সোমবার', 'মঙ্গলবার', 'বুধবার', 'বৃহস্পতিবার', 'শুক্রবার', 'শনিবার'];

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} start of first day, end of last day */
    public static function range(string $period, ?string $from = null, ?string $to = null): array
    {
        $now = CarbonImmutable::now();

        return match ($period) {
            'yesterday' => [$now->subDay()->startOfDay(), $now->subDay()->endOfDay()],
            'week'      => [self::weekStart($now), $now->endOfDay()],
            'month'     => [$now->startOfMonth(), $now->endOfDay()],
            'year'      => [self::fyStart($now), $now->endOfDay()],
            'custom'    => [
                CarbonImmutable::parse($from ?: $now->toDateString())->startOfDay(),
                CarbonImmutable::parse($to ?: $from ?: $now->toDateString())->endOfDay(),
            ],
            default     => [$now->startOfDay(), $now->endOfDay()],
        };
    }

    /** "11 Oct 2026" or "04 Oct – 11 Oct 2026" */
    public static function label(CarbonInterface $start, CarbonInterface $end): string
    {
        return $start->isSameDay($end)
            ? $start->format('d M Y')
            : $start->format($start->year === $end->year ? 'd M' : 'd M Y') . ' – ' . $end->format('d M Y');
    }

    public static function weekStart(CarbonInterface $d): CarbonImmutable
    {
        $d = CarbonImmutable::instance($d)->startOfDay();
        $back = ($d->dayOfWeek - AccountsSettings::weekStart() + 7) % 7;

        return $d->subDays($back);
    }

    /** First day of the financial year containing $d. */
    public static function fyStart(?CarbonInterface $d = null): CarbonImmutable
    {
        $d = CarbonImmutable::instance($d ?? now())->startOfDay();
        $m = AccountsSettings::fyStartMonth();
        $year = $d->month >= $m ? $d->year : $d->year - 1;

        return CarbonImmutable::create($year, $m, 1)->startOfDay();
    }

    public static function fyEnd(?CarbonInterface $d = null): CarbonImmutable
    {
        return self::fyStart($d)->addYear()->subDay()->endOfDay();
    }

    /** "FY 2026-27" (or "FY 2026" for a calendar year). */
    public static function fyLabel(?CarbonInterface $d = null): string
    {
        $s = self::fyStart($d);

        return $s->month === 1 ? 'FY ' . $s->year : 'FY ' . $s->year . '-' . substr((string) ($s->year + 1), -2);
    }

    public static function bnWeekday(CarbonInterface $d): string
    {
        return self::BN_WEEKDAYS[$d->dayOfWeek];
    }
}
