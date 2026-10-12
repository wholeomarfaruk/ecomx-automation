<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Formatting shared by the Accounts Panel pages: ৳ with Bangladeshi digit
 * grouping (1775250 → ৳17,75,250) and the short relative dates used in
 * "Last activity" columns.
 */
class Bdt
{
    public static function money(int|float|string|null $amount, bool $signed = false): string
    {
        if ($amount === null || $amount === '') {
            return '—';
        }

        $amount   = (float) $amount;
        $negative = $amount < -0.004;
        $digits   = (string) (int) round(abs($amount));

        if (static::international()) {
            $digits = number_format((int) $digits);
        } elseif (strlen($digits) > 3) {
            $last3  = substr($digits, -3);
            $rest   = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', substr($digits, 0, -3));
            $digits = $rest . ',' . $last3;
        }

        $prefix = $negative ? '− ' : ($signed ? '+ ' : '');

        return $prefix . '৳' . $digits;
    }

    /** Settings › General › Currency & format: lakh (default) or international grouping. */
    protected static function international(): bool
    {
        static $intl = null;

        return $intl ??= AccountsSettings::get('currency_format', 'lakh') === 'international';
    }

    /** Today / Yesterday / 3 days ago / 1 week ago / 3 weeks ago / 05 Oct */
    public static function relative(?CarbonInterface $at): string
    {
        if (! $at) {
            return '—';
        }

        $days = (int) $at->copy()->startOfDay()->diffInDays(now()->startOfDay());

        return match (true) {
            $days <= 0  => 'Today',
            $days === 1 => 'Yesterday',
            $days < 7   => "{$days} days ago",
            $days < 14  => '1 week ago',
            $days < 30  => intdiv($days, 7) . ' weeks ago',
            default     => $at->format('d M'),
        };
    }
}
