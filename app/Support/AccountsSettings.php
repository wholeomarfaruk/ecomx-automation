<?php

namespace App\Support;

use App\Models\Setting;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Accounts Panel › Settings, stored in the shared `settings` table under the
 * "accounts" group. Every reader goes through here so defaults live in one
 * place (design: Settings.dc.html).
 */
class AccountsSettings
{
    public const GROUP = 'accounts';

    /** Posting rules: key => [default on, default amount]. */
    public const RULES = [
        'approval' => [true, 50000],
        'attach'   => [true, 20000],
        'maker'    => [true, 0],
        'lock'     => [true, 0],
        'auto'     => [false, 0],
        'edit'     => [true, 0],
    ];

    /** Voucher types: key => [label, default prefix]. */
    public const VOUCHER_TYPES = [
        'receipt'  => ['Receipt (টাকা আসলো)', 'RV-'],
        'payment'  => ['Payment (টাকা গেলো)', 'PV-'],
        'journal'  => ['Journal', 'JV-'],
        'expense'  => ['Expense', 'EXP-'],
        'contra'   => ['Contra / transfer', 'CT-'],
        'emi'      => ['Loan / EMI', 'EMI-'],
        'salary'   => ['Salary', 'SAL-'],
        'opening'  => ['Opening balance', 'OB-'],
        'setoff'   => ['Set-off', 'SO-'],
    ];

    public const DEFAULT_TEMPLATES = [
        'kisti' => 'প্রিয় {name}, আগামীকাল {date} আপনার কিস্তি {amount} টাকা। ধন্যবাদ — {business}',
        'due'   => 'প্রিয় {name}, আপনার কাছে {due} টাকা বাকি আছে। — {business}',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default, self::GROUP);
    }

    public static function set(string $key, mixed $value): void
    {
        Setting::set($key, $value, self::GROUP);
    }

    public static function businessName(): string
    {
        return (string) (self::get('business_name') ?: (Setting::get('site_name', config('app.name'), 'general') ?: config('app.name')));
    }

    /** 7 = July–June (Bangladesh), 1 = January–December. */
    public static function fyStartMonth(): int
    {
        return (int) self::get('fy_start_month', 7) === 1 ? 1 : 7;
    }

    /** Carbon day-of-week the week starts on (6 = Saturday). */
    public static function weekStart(): int
    {
        return (int) self::get('week_start', CarbonInterface::SATURDAY);
    }

    public static function lockDate(): ?CarbonImmutable
    {
        $d = self::get('lock_date');

        return $d ? CarbonImmutable::parse($d)->startOfDay() : null;
    }

    public static function booksStart(): ?CarbonImmutable
    {
        $d = self::get('books_start');

        return $d ? CarbonImmutable::parse($d) : null;
    }

    /** @return array{on: bool, amount: float} */
    public static function rule(string $key): array
    {
        [$on, $amount] = self::RULES[$key] ?? [false, 0];
        $saved = (array) (self::get('rules') ?: []);

        return [
            'on'     => (bool) ($saved[$key]['on'] ?? $on),
            'amount' => (float) ($saved[$key]['amount'] ?? $amount),
        ];
    }

    public static function ruleOn(string $key): bool
    {
        return self::rule($key)['on'];
    }

    /** Approval threshold, or null when the rule is off. */
    public static function approvalThreshold(): ?float
    {
        $r = self::rule('approval');

        return $r['on'] ? $r['amount'] : null;
    }

    public static function attachmentThreshold(): ?float
    {
        $r = self::rule('attach');

        return $r['on'] ? $r['amount'] : null;
    }

    /** @return array<string, array{prefix: string, next: int}> */
    public static function numbering(): array
    {
        $saved = (array) (self::get('numbering') ?: []);

        return collect(self::VOUCHER_TYPES)->mapWithKeys(fn ($def, $type) => [$type => [
            'prefix' => (string) ($saved[$type]['prefix'] ?? $def[1]),
            'next'   => max(1, (int) ($saved[$type]['next'] ?? 1)),
        ]])->all();
    }

    public static function collectionTarget(): float
    {
        return (float) self::get('collection_target', 0);
    }

    public static function template(string $key): string
    {
        $tpl = (array) (self::get('sms_templates') ?: []);

        return (string) ($tpl[$key] ?? self::DEFAULT_TEMPLATES[$key] ?? '');
    }

    /** Replace {name} {amount} {date} {due} {kisti_no} {business}. */
    public static function render(string $template, array $vars): string
    {
        $vars += ['business' => self::businessName()];

        return strtr($template, collect($vars)->mapWithKeys(fn ($v, $k) => ['{' . $k . '}' => (string) $v])->all());
    }

    public static function alert(string $key): bool
    {
        $defaults = ['stale_voucher' => true, 'emi_3_days' => true, 'daily_summary' => false];
        $saved = (array) (self::get('alerts') ?: []);

        return (bool) ($saved[$key] ?? $defaults[$key] ?? false);
    }
}
