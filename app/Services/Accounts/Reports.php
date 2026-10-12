<?php

namespace App\Services\Accounts;

use App\Enums\Accounts\JournalEntryStatus;
use App\Models\Account;
use App\Models\EmployeeAdvance;
use App\Models\Installment;
use App\Models\JournalEntry;
use App\Models\LoanRepaymentSchedule;
use App\Models\MasterProfile;
use App\Models\Order;
use App\Models\PayrollRun;
use App\Services\Profiles\LedgerAccounts;
use App\Services\Profiles\ProfileLedger;
use App\Support\AccountsPeriod;
use App\Support\AccountsSettings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Accounts Panel reports (spec §5.11). Posted entries only. Every report
 * returns one table shape the page, the print / PDF copy and the Excel
 * (CSV) export all render:
 *
 *   title, period, tiles[[label, value, sub, tone]], columns[[label, type]],
 *   rows[[kind, cells[], tone[]]], note, check (balance sheet), split (two tables)
 *
 * column type: text | money | num | pct | ref.  row kind: head | line |
 * total | net | plain | open.
 */
class Reports
{
    public const GROUPS = [
        'প্রতিদিন' => ['daily' => ['Daily report', 'দিন শেষের হিসাব'], 'saved' => ['Saved reports', '']],
        'Financial statements' => ['pl' => ['Profit & Loss', 'লাভ-ক্ষতি'], 'bs' => ['Balance Sheet', 'সম্পদ ও দায়'], 'tb' => ['Trial Balance', ''], 'cf' => ['Cash flow', '']],
        'Books · খাতা' => ['cash' => ['Cash book', 'নগদ খাতা'], 'bank' => ['Bank book', ''], 'day' => ['Day book', ''], 'party' => ['Party ledger', '']],
        'Business' => ['sales' => ['Sales summary', ''], 'stock' => ['Stock valuation', ''], 'aging' => ['Receivable aging', ''], 'payaging' => ['Payable aging', ''],
            'exp' => ['Expense by category', ''], 'loan' => ['Loan & EMI schedule', ''], 'payroll' => ['Payroll summary', '']],
    ];

    public const PERIODS = ['month' => 'This month', 'last_month' => 'Last month', 'year' => 'This year (Jul–Jun)', 'custom' => 'Custom'];

    /** Reports that are "as of" a date rather than a period. */
    public const AS_OF = ['bs', 'tb', 'stock', 'aging', 'payaging', 'loan'];

    protected ?Collection $accounts = null;

    public function __construct(protected LedgerAccounts $ledgers) {}

    public static function title(string $key): string
    {
        foreach (self::GROUPS as $items) {
            if (isset($items[$key])) {
                return $items[$key][0];
            }
        }

        return 'Report';
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    public static function range(array $f): array
    {
        $today = CarbonImmutable::today();

        return match ($f['period'] ?? 'month') {
            'last_month' => [$today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()->startOfDay()],
            'year' => [AccountsPeriod::fyStart($today), $today],
            'custom' => [CarbonImmutable::parse($f['from'] ?? $today->startOfMonth())->startOfDay(), CarbonImmutable::parse($f['to'] ?? $today)->startOfDay()],
            default => [$today->startOfMonth(), $today],
        };
    }

    /** The comparison period: last month for "this month", else the same length right before. */
    public static function previous(CarbonImmutable $from, CarbonImmutable $to): array
    {
        if ($from->day === 1 && $from->isSameMonth($to)) {
            $p = $from->subMonthNoOverflow();

            return [$p->startOfMonth(), $p->endOfMonth()->startOfDay()];
        }
        $len = $from->diffInDays($to) + 1;

        return [$from->subDays($len), $from->subDay()];
    }

    public static function label(CarbonImmutable $from, CarbonImmutable $to): string
    {
        if ($from->isSameDay($to)) {
            return $from->format('l, j F Y');
        }
        if ($from->isSameMonth($to)) {
            return $from->day === 1 && $to->isSameDay($from->endOfMonth()) ? $from->format('F Y') : $from->format('j') . '–' . $to->format('j F Y');
        }

        return $from->format('j M Y') . ' – ' . $to->format('j M Y');
    }

    /* ================================================================ data */

    protected function accounts(): Collection
    {
        return $this->accounts ??= Account::query()->get(['id', 'code', 'name', 'type', 'subtype', 'normal_balance', 'parent_id'])->keyBy('id');
    }

    /** Top-level ancestor code of an account. */
    protected function root(Account $a): string
    {
        $all = $this->accounts();
        $guard = 0;
        while ($a->parent_id && isset($all[$a->parent_id]) && $guard++ < 10) {
            $a = $all[$a->parent_id];
        }

        return (string) $a->code;
    }

    /** account id → [debit, credit] of posted lines (from = null → since the start). */
    public function sums(?CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return DB::table('journal_entry_lines as l')->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->where('e.status', JournalEntryStatus::POSTED->value)
            ->when($from, fn ($q) => $q->where('e.entry_date', '>=', $from->toDateString()))
            ->where('e.entry_date', '<=', $to->toDateString())
            ->groupBy('l.account_id')->selectRaw('l.account_id, SUM(l.debit) as d, SUM(l.credit) as c')
            ->get()->keyBy('account_id');
    }

    /** Balance in the account's own normal direction. */
    protected function bal(Collection $sums, Account $a): float
    {
        $s = $sums[$a->id] ?? null;

        return $s ? round($a->normal_balance->signedDelta((float) $s->d, (float) $s->c), 2) : 0.0;
    }

    public function drafts(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $q = JournalEntry::whereIn('status', [JournalEntryStatus::DRAFT->value, JournalEntryStatus::SCHEDULED->value])
            ->whereBetween('entry_date', [$from->toDateString(), $to->toDateString()]);

        return ['count' => (clone $q)->count(), 'amount' => (float) (clone $q)->sum('amount')];
    }

    /* ================================================================ build */

    public function build(string $key, array $f): array
    {
        [$from, $to] = self::range($f);
        if (in_array($key, self::AS_OF, true)) {
            $to = ! empty($f['as_of']) ? CarbonImmutable::parse($f['as_of'])->startOfDay() : $to->min(CarbonImmutable::today());
        }

        $r = match ($key) {
            'pl' => $this->pl($from, $to),
            'bs' => $this->bs($to),
            'tb' => $this->tb($to),
            'cf' => $this->cf($from, $to),
            'cash', 'bank' => $this->book($key, $from, $to, isset($f['account_id']) ? (int) $f['account_id'] : null),
            'day' => $this->dayBook($from, $to),
            'party' => $this->party($from, $to, isset($f['profile_id']) ? (int) $f['profile_id'] : null),
            'sales' => $this->sales($from, $to),
            'stock' => $this->stock(),
            'aging' => $this->aging('receivables'),
            'payaging' => $this->aging('payables'),
            'exp' => $this->expenses($from, $to),
            'loan' => $this->loanSchedule(),
            'payroll' => $this->payroll($from),
            default => ['columns' => [], 'rows' => []],
        };

        return $r + [
            'key' => $key, 'title' => self::title($key), 'from' => $from, 'to' => $to,
            'period' => $r['period'] ?? (in_array($key, self::AS_OF, true) ? 'As of ' . $to->format('j F Y') : self::label($from, $to)),
            'tiles' => [], 'note' => null, 'business' => AccountsSettings::businessName(),
        ];
    }

    protected static function row(string $kind, array $cells, array $tone = []): array
    {
        return ['kind' => $kind, 'cells' => $cells, 'tone' => $tone];
    }

    /* ---------------------------------------------------------------- P&L */

    protected function plLines(Collection $sums): array
    {
        $out = ['sales' => 0, 'returns' => 0, 'discounts' => 0, 'rebates' => 0, 'cogs' => 0, 'finance' => 0, 'other' => [], 'exp' => []];

        foreach ($this->accounts() as $a) {
            $v = $this->bal($sums, $a);
            if (abs($v) < 0.005) {
                continue;
            }
            $root = $this->root($a);
            $type = $a->type->value;

            if ($type === 'income') {
                match (true) {
                    in_array($a->code, ['4900'], true) => $out['returns'] += $v,
                    $a->code === '4910' => $out['discounts'] += $v,
                    $a->code === '4950' => $out['rebates'] += $v,
                    in_array($root, ['4000', '4050'], true) => $out['sales'] += $v,
                    str_starts_with((string) $a->subtype, 'contra_') => $out['returns'] += $v,
                    default => $out['other'][$a->name] = ($out['other'][$a->name] ?? 0) + $v,
                };
            } elseif ($type === 'expense') {
                match (true) {
                    $a->subtype === 'cogs' || $root === '5000' => $out['cogs'] += $v,
                    $a->code === '5400' => $out['finance'] += $v,
                    default => $out['exp'][$a->name] = ($out['exp'][$a->name] ?? 0) + $v,
                };
            }
        }

        $out['net_sales'] = $out['sales'] - $out['returns'] - $out['discounts'] - $out['rebates'];
        $out['gross'] = $out['net_sales'] - $out['cogs'];
        $out['other_total'] = array_sum($out['other']);
        $out['exp_total'] = array_sum($out['exp']) + $out['finance'];
        $out['net'] = $out['gross'] + $out['other_total'] - $out['exp_total'];

        return $out;
    }

    public function plTotals(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return $this->plLines($this->sums($from, $to));
    }

    protected function pl(CarbonImmutable $from, CarbonImmutable $to): array
    {
        [$pf, $pt] = self::previous($from, $to);
        $c = $this->plLines($this->sums($from, $to));
        $p = $this->plLines($this->sums($pf, $pt));
        $ns = $c['net_sales'] ?: null;
        $pct = fn ($v) => $ns ? round($v / $ns * 100, 1) : null;
        $L = fn ($label, $cv, $pv, $kind = 'line', $neg = false) => self::row($kind, [$label, $neg ? -$cv : $cv, $neg ? -$pv : $pv, $kind === 'head' ? null : $pct($cv)]);

        $rows = [self::row('head', ['আয় · Income', null, null, null]), $L('Sales', $c['sales'], $p['sales'])];
        foreach (['returns' => 'Less: returns', 'discounts' => 'Less: discounts', 'rebates' => 'Less: rebates'] as $k => $label) {
            if ($c[$k] || $p[$k]) {
                $rows[] = $L($label, $c[$k], $p[$k], 'line', true);
            }
        }
        $rows[] = $L('Net sales', $c['net_sales'], $p['net_sales'], 'total');
        $rows[] = $L('Cost of goods sold (avg cost)', $c['cogs'], $p['cogs'], 'line', true);
        $rows[] = $L('Gross profit', $c['gross'], $p['gross'], 'total');
        if ($c['other'] || $p['other']) {
            $rows[] = self::row('head', ['অন্যান্য আয় · Other income', null, null, null]);
            foreach (array_unique([...array_keys($c['other']), ...array_keys($p['other'])]) as $n) {
                $rows[] = $L($n, $c['other'][$n] ?? 0, $p['other'][$n] ?? 0);
            }
        }
        $rows[] = self::row('head', ['খরচ · Operating expenses', null, null, null]);
        $names = array_unique([...array_keys($c['exp']), ...array_keys($p['exp'])]);
        usort($names, fn ($x, $y) => ($c['exp'][$y] ?? 0) <=> ($c['exp'][$x] ?? 0));
        foreach ($names as $n) {
            $rows[] = $L($n, $c['exp'][$n] ?? 0, $p['exp'][$n] ?? 0);
        }
        $rows[] = $L('Finance cost (interest)', $c['finance'], $p['finance']);
        $rows[] = $L('Total expenses', $c['exp_total'], $p['exp_total'], 'total');
        $rows[] = $L('নিট লাভ · Net profit', $c['net'], $p['net'], 'net');

        $margin = $ns ? round($c['gross'] / $ns * 100, 1) : 0;

        return [
            'period' => self::label($from, $to) . ' · compared with ' . self::label($pf, $pt),
            'columns' => [['Particulars', 'text'], [self::label($from, $to), 'money'], [self::label($pf, $pt), 'money'], ['% of sales', 'pct']],
            'rows' => $rows,
            'tiles' => [
                ['Net sales', $c['net_sales'], null, null],
                ['Gross profit', $c['gross'], "{$margin}% margin", null],
                ['Net profit', $c['net'], ($ns ? round($c['net'] / $ns * 100, 1) : 0) . '% of sales', $c['net'] >= 0 ? 'green' : 'red'],
            ],
            'note' => $to->isSameDay(CarbonImmutable::today()) && $to->day < $to->daysInMonth ? $to->format('F') . '-এর বেতন আর অবচয় মাস শেষে payroll ও depreciation run-এর পরে যোগ হবে।' : null,
        ];
    }

    /* ---------------------------------------------------------------- Balance sheet */

    protected function bs(CarbonImmutable $asOf): array
    {
        $sums = $this->sums(null, $asOf);
        $fy = AccountsPeriod::fyStart($asOf);
        $A = ['Cash & bank' => 0, 'Customer receivable' => 0, 'Courier COD receivable' => 0, 'Gateway receivable' => 0, 'Advances given' => 0, 'Inventory (avg cost)' => 0];
        $fixedCost = 0;
        $accum = 0;
        $L = ['Supplier payable' => 0, 'Loans (lenders)' => 0, 'Salary payable' => 0, 'Customer advances & credit' => 0, 'VAT payable' => 0, 'Rebate payable' => 0];
        $E = ['capital' => 0, 'retained' => 0, 'drawings' => 0, 'other' => []];

        foreach ($this->accounts() as $a) {
            $v = $this->bal($sums, $a);
            if (abs($v) < 0.005) {
                continue;
            }
            $root = $this->root($a);
            $type = $a->type->value;

            if ($type === 'asset') {
                match (true) {
                    in_array($a->subtype, LedgerAccounts::CASH_SUBTYPES, true) || in_array($root, ['1010', '1020', '1030', '1040'], true) => $A['Cash & bank'] += $v,
                    $root === '1100' => $A['Customer receivable'] += $v,
                    $a->subtype === 'courier' || in_array($root, ['1045', '1050'], true) => $A['Courier COD receivable'] += $v,
                    $root === '1060' => $A['Gateway receivable'] += $v,
                    in_array($root, ['1150', '1160'], true) => $A['Advances given'] += $v,
                    $root === '1200' => $A['Inventory (avg cost)'] += $v,
                    $root === '1510' || $a->subtype === 'contra_asset' => $accum += $v,
                    $root === '1500' => $fixedCost += $v,
                    default => $A[$a->name] = ($A[$a->name] ?? 0) + $v,
                };
            } elseif ($type === 'liability') {
                match (true) {
                    $root === '2100' => $L['Supplier payable'] += $v,
                    $root === '2200' => $L['Loans (lenders)'] += $v,
                    $root === '2400' => $L['Salary payable'] += $v,
                    in_array($root, ['2150', '2160'], true) => $L['Customer advances & credit'] += $v,
                    $root === '2300' => $L['VAT payable'] += $v,
                    $root === '2450' => $L['Rebate payable'] += $v,
                    default => $L[$a->name] = ($L[$a->name] ?? 0) + $v,
                };
            } elseif ($type === 'equity') {
                match (true) {
                    in_array($root, ['3000', '3950'], true) => $E['capital'] += $v,
                    $root === '3900' => $E['retained'] += $v,
                    $root === '3100' => $E['drawings'] += $v,
                    default => $E['other'][$a->name] = ($E['other'][$a->name] ?? 0) + $v,
                };
            }
        }

        $before = $this->plLines($this->sums(null, $fy->subDay()))['net'];
        $thisYear = $this->plLines($this->sums($fy, $asOf))['net'];
        $fixedBook = $fixedCost - $accum;
        $totalA = array_sum($A) + $fixedBook;
        $totalL = array_sum($L);
        $totalE = $E['capital'] + $E['retained'] + $before + $thisYear - $E['drawings'] + array_sum($E['other']);

        $left = [self::row('head', ['Current assets', null])];
        foreach ($A as $n => $v) {
            if (abs($v) >= 0.005 || in_array($n, ['Cash & bank', 'Customer receivable', 'Inventory (avg cost)'], true)) {
                $left[] = self::row('line', [$n, $v]);
            }
        }
        $left[] = self::row('head', ['Fixed assets', null]);
        $left[] = self::row('line', ['Cost ' . \App\Support\Bdt::money($fixedCost) . ' − depreciation ' . \App\Support\Bdt::money($accum), $fixedBook]);
        $left[] = self::row('total', ['মোট সম্পদ · Total assets', $totalA]);

        $right = [self::row('head', ['Liabilities', null])];
        foreach ($L as $n => $v) {
            if (abs($v) >= 0.005 || in_array($n, ['Supplier payable', 'Loans (lenders)'], true)) {
                $right[] = self::row('line', [$n, $v]);
            }
        }
        $right[] = self::row('total', ['Total liabilities', $totalL]);
        $right[] = self::row('head', ['Owners equity', null]);
        $right[] = self::row('line', ['Capital', $E['capital']]);
        if (abs($E['retained'] + $before) >= 0.005) {
            $right[] = self::row('line', ['Retained earnings (previous years)', $E['retained'] + $before]);
        }
        $right[] = self::row('line', ['Profit this year (' . $fy->format('M') . '–' . $asOf->format('M') . ')', $thisYear]);
        $right[] = self::row('line', ['Drawings', -$E['drawings']], [1 => 'red']);
        foreach ($E['other'] as $n => $v) {
            $right[] = self::row('line', [$n, $v]);
        }
        $right[] = self::row('total', ['Total equity', $totalE]);
        $right[] = self::row('total', ['মোট দায় ও মালিকানা', $totalL + $totalE]);

        $diff = round($totalA - $totalL - $totalE, 2);

        return [
            'columns' => [['Particulars', 'text'], ['Amount', 'money']],
            'split' => ['সম্পদ · Assets' => $left, 'দায় ও মালিকানা · Liabilities & Equity' => $right],
            'rows' => [...$left, ...$right],
            'check' => ['ok' => abs($diff) < 1, 'assets' => $totalA, 'liabilities' => $totalL, 'equity' => $totalE, 'diff' => $diff],
        ];
    }

    /* ---------------------------------------------------------------- Trial balance */

    protected function tb(CarbonImmutable $asOf): array
    {
        $sums = $this->sums(null, $asOf);
        $rows = [];
        $dr = $cr = 0;
        foreach ($this->accounts()->sortBy('code') as $a) {
            $s = $sums[$a->id] ?? null;
            $net = $s ? round((float) $s->d - (float) $s->c, 2) : 0;
            if (abs($net) < 0.005) {
                continue;
            }
            $rows[] = self::row('plain', [$a->code, $a->name, $net > 0 ? $net : null, $net < 0 ? -$net : null]);
            $net > 0 ? $dr += $net : $cr += -$net;
        }
        $rows[] = self::row('total', ['', 'Total', $dr, $cr]);

        return [
            'columns' => [['Code', 'text'], ['Ledger', 'text'], ['Debit', 'money'], ['Credit', 'money']],
            'rows' => $rows,
            'check' => ['ok' => abs($dr - $cr) < 1, 'tb' => true, 'debit' => $dr, 'credit' => $cr, 'diff' => round($dr - $cr, 2)],
        ];
    }

    /* ---------------------------------------------------------------- Cash flow */

    protected function cf(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $cashIds = collect($this->ledgers->cashAccounts())->pluck('id')->all();
        $courier = $this->accounts()->filter(fn ($a) => $a->subtype === 'courier')->keys()->all();
        $cashIds = array_values(array_unique([...$cashIds, ...$courier]));
        $sumsOpen = $this->sums(null, $from->subDay());
        $opening = collect($cashIds)->sum(fn ($id) => isset($this->accounts()[$id]) ? $this->bal($sumsOpen, $this->accounts()[$id]) : 0);

        $lines = DB::table('journal_entry_lines as l')->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->where('e.status', JournalEntryStatus::POSTED->value)->whereBetween('e.entry_date', [$from->toDateString(), $to->toDateString()])
            ->whereIn('l.journal_entry_id', DB::table('journal_entry_lines')->whereIn('account_id', $cashIds ?: [0])->select('journal_entry_id'))
            ->get(['l.journal_entry_id', 'l.account_id', 'l.debit', 'l.credit'])->groupBy('journal_entry_id');

        $buckets = ['Operating' => [], 'Investing' => [], 'Financing' => []];
        foreach ($lines as $ls) {
            $cash = $ls->filter(fn ($l) => in_array($l->account_id, $cashIds));
            $delta = round($cash->sum(fn ($l) => (float) $l->debit - (float) $l->credit), 2);
            if (abs($delta) < 0.005) {
                continue;                                   // transfer between cash accounts
            }
            $other = $ls->reject(fn ($l) => in_array($l->account_id, $cashIds))->sortByDesc(fn ($l) => (float) $l->debit + (float) $l->credit)->first();
            $a = $other ? $this->accounts()[$other->account_id] ?? null : null;
            $root = $a ? $this->root($a) : '';
            $type = $a ? $a->type->value : '';
            [$bucket, $label] = match (true) {
                ! $a => ['Operating', 'Other'],
                in_array($root, ['1500', '1510', '4200', '5700'], true) => ['Investing', 'Fixed assets'],
                $root === '2200' || $root === '5400' => ['Financing', $delta > 0 ? 'Loans received' : 'Loan repayments & interest'],
                $type === 'equity' => ['Financing', $root === '3100' ? 'Owner drawings' : 'Owner capital'],
                in_array($root, ['1100', '4000', '4050', '2150', '2160'], true) || $a->subtype === 'courier' => ['Operating', 'Received from customers'],
                in_array($root, ['2100', '1150', '1200'], true) => ['Operating', 'Paid to suppliers'],
                in_array($root, ['2400', '1160'], true) || $a->code === '5130' => ['Operating', 'Salary & staff advances'],
                $type === 'expense' => ['Operating', 'Expenses'],
                $type === 'income' => ['Operating', 'Other income'],
                default => ['Operating', $a->name],
            };
            $buckets[$bucket][$label] = ($buckets[$bucket][$label] ?? 0) + $delta;
        }

        $rows = [self::row('open', ['Opening cash & bank', $opening])];
        $net = 0;
        foreach (['Operating' => 'Operating activities', 'Investing' => 'Investing activities', 'Financing' => 'Financing activities'] as $k => $title) {
            $rows[] = self::row('head', [$title, null]);
            foreach ($buckets[$k] as $label => $v) {
                $rows[] = self::row('line', [$label, $v], [1 => $v >= 0 ? 'green' : 'red']);
            }
            $t = array_sum($buckets[$k]);
            $net += $t;
            $rows[] = self::row('total', ["Net cash from {$k}", $t]);
        }
        $rows[] = self::row('net', ['Closing cash & bank', $opening + $net]);

        return [
            'columns' => [['Particulars', 'text'], ['Amount', 'money']], 'rows' => $rows,
            'tiles' => [['Opening', $opening, null, null], ['Net change', $net, null, $net >= 0 ? 'green' : 'red'], ['Closing', $opening + $net, null, null]],
            'note' => 'Cash & bank, MFS আর courier-এর কাছে থাকা COD মিলিয়ে। এক account থেকে আরেক account-এ transfer বাদ।',
        ];
    }

    /* ---------------------------------------------------------------- Books */

    public function bookAccounts(string $key): array
    {
        return collect($this->ledgers->cashAccounts())
            ->filter(fn ($a) => $key === 'cash' ? ($a['subtype'] ?? '') === 'cash' : ($a['subtype'] ?? '') !== 'cash')
            ->values()->all() ?: $this->ledgers->cashAccounts();
    }

    protected function book(string $key, CarbonImmutable $from, CarbonImmutable $to, ?int $accountId): array
    {
        $options = $this->bookAccounts($key);
        $accountId = $accountId && collect($options)->contains('id', $accountId) ? $accountId : ($options[0]['id'] ?? 0);
        $acc = $this->accounts()[$accountId] ?? null;
        if (! $acc) {
            return ['columns' => [], 'rows' => [], 'note' => 'কোনো account নেই।'];
        }

        $opening = $this->bal($this->sums(null, $from->subDay()), $acc);
        $lines = DB::table('journal_entry_lines as l')->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->where('e.status', JournalEntryStatus::POSTED->value)->where('l.account_id', $accountId)
            ->whereBetween('e.entry_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('e.entry_date')->orderBy('e.id')
            ->get(['e.entry_date', 'e.voucher_ref', 'e.entry_number', 'e.description', 'e.party_label', 'l.debit', 'l.credit', 'l.memo']);

        $run = $opening;
        $rows = [self::row('open', ['Opening balance · ' . $acc->name . ' · ' . $from->format('d M'), null, null, null, null, $opening])];
        foreach ($lines as $l) {
            $run += (float) $l->debit - (float) $l->credit;
            $rows[] = self::row('plain', [CarbonImmutable::parse($l->entry_date)->format('d M'), $l->voucher_ref ?: $l->entry_number,
                trim(($l->description ?: $l->memo ?: '—') . ($l->party_label ? " · {$l->party_label}" : '')), (float) $l->debit ?: null, (float) $l->credit ?: null, round($run, 2)],
                [3 => 'green', 4 => 'red']);
        }
        $rows[] = self::row('total', ['Closing balance · ' . $to->format('d M'), '', '', (float) $lines->sum('debit'), (float) $lines->sum('credit'), round($run, 2)], [3 => 'green', 4 => 'red']);

        return [
            'title' => ($key === 'cash' ? 'Cash book · ' : 'Bank book · ') . $acc->name,
            'columns' => [['Date', 'text'], ['Voucher', 'ref'], ['Particulars', 'text'], ['Receipt', 'money'], ['Payment', 'money'], ['Balance', 'money']],
            'rows' => $rows, 'accountOptions' => $options, 'accountId' => $accountId,
            'note' => 'Account বদলাতে Period-এর পাশে account বেছে নিন।',
        ];
    }

    protected function dayBook(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $entries = JournalEntry::with('profile:id,display_name')->where('status', JournalEntryStatus::POSTED->value)
            ->whereBetween('entry_date', [$from->toDateString(), $to->toDateString()])->orderBy('entry_date')->orderBy('id')->limit(2000)->get();
        $v = app(Vouchers::class);
        $rows = $entries->map(fn ($e) => self::row('plain', [$e->entry_date->format('d M'), $e->ref(), $v->typeLabel($e),
            trim(($e->description ?: '—') . (($e->party_label ?: $e->profile?->display_name) ? ' · ' . ($e->party_label ?: $e->profile?->display_name) : '')), (float) $e->amount]))->all();
        $rows[] = self::row('total', ['', '', '', $entries->count() . ' vouchers', (float) $entries->sum('amount')]);

        return ['columns' => [['Date', 'text'], ['Voucher', 'ref'], ['Type', 'text'], ['Particulars', 'text'], ['Amount', 'money']], 'rows' => $rows];
    }

    protected function party(CarbonImmutable $from, CarbonImmutable $to, ?int $profileId): array
    {
        $p = $profileId ? MasterProfile::find($profileId) : null;
        if (! $p) {
            return ['columns' => [], 'rows' => [], 'needsProfile' => true, 'note' => 'যেকোনো customer, supplier, lender-এর পুরো খাতা — উপরে profile বেছে নিন।'];
        }

        $s = app(ProfileLedger::class)->statement($p, $from->toDateString(), $to->toDateString());
        $rows = [self::row('open', ['Opening balance', '', '', null, null, $s['opening']])];
        foreach ($s['rows'] as $r) {
            $rows[] = self::row('plain', [$r['date']->format('d M'), $r['ref'], trim($r['text'] . ($r['sub'] ? " · {$r['sub']}" : '')), $r['debit'] ?: null, $r['credit'] ?: null, $r['balance']]);
        }
        $rows[] = self::row('total', ['Closing balance', '', '', $s['debit'], $s['credit'], $s['closing']]);

        return [
            'title' => 'Party ledger · ' . $p->display_name, 'profileId' => $p->id, 'profileName' => $p->display_name,
            'columns' => [['Date', 'text'], ['Voucher', 'ref'], ['Particulars', 'text'], ['Debit', 'money'], ['Credit', 'money'], ['Balance', 'money']], 'rows' => $rows,
            'note' => 'Balance + মানে সে আমাদের কাছে পাবে না, আমরা পাবো (Dr); − মানে আমরা দেবো (Cr)।',
        ];
    }

    /* ---------------------------------------------------------------- Business */

    protected function sales(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $codes = fn ($list) => $this->accounts()->filter(fn ($a) => in_array($this->root($a), $list, true))->keys()->all();
        $salesIds = $codes(['4000', '4050']);
        $retIds = $codes(['4900', '4910']);

        $byDay = DB::table('journal_entry_lines as l')->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->where('e.status', JournalEntryStatus::POSTED->value)->whereBetween('e.entry_date', [$from->toDateString(), $to->toDateString()])
            ->whereIn('l.account_id', [...$salesIds, ...$retIds] ?: [0])
            ->groupBy('e.entry_date')->orderBy('e.entry_date')
            ->selectRaw('e.entry_date as d, SUM(CASE WHEN l.account_id IN (' . implode(',', $salesIds ?: [0]) . ') THEN l.credit - l.debit ELSE 0 END) as sales,
                SUM(CASE WHEN l.account_id IN (' . implode(',', $retIds ?: [0]) . ') THEN l.debit - l.credit ELSE 0 END) as ret,
                COUNT(DISTINCT CASE WHEN e.source_type = ? AND e.purpose LIKE ? THEN e.source_id END) as orders', [Order::class, 'sale%'])
            ->get();

        $orderIds = JournalEntry::where('source_type', Order::class)->where('status', JournalEntryStatus::POSTED->value)->where('purpose', 'like', 'sale%')
            ->whereBetween('entry_date', [$from->toDateString(), $to->toDateString()])->pluck('source_id');

        $rows = [self::row('head', ['দিন অনুযায়ী · By day', null, null, null, null])];
        foreach ($byDay as $d) {
            $rows[] = self::row('line', [CarbonImmutable::parse($d->d)->format('D, d M'), (int) $d->orders, (float) $d->sales, -(float) $d->ret, (float) $d->sales - (float) $d->ret]);
        }
        $totS = (float) $byDay->sum('sales');
        $totR = (float) $byDay->sum('ret');
        $totO = (int) $byDay->sum('orders');
        $rows[] = self::row('total', ['Total', $totO, $totS, -$totR, $totS - $totR]);

        $channels = Order::whereIn('id', $orderIds)->groupBy('source')->selectRaw('source, COUNT(*) as n, SUM(total_amount) as amt')->get();
        if ($channels->isNotEmpty()) {
            $rows[] = self::row('head', ['Channel অনুযায়ী', null, null, null, null]);
            foreach ($channels as $c) {
                $rows[] = self::row('line', [ucfirst((is_object($c->source) ? $c->source->value : (string) $c->source) ?: 'Other'), (int) $c->n, (float) $c->amt, null, null]);
            }
        }

        $top = DB::table('order_items')->whereIn('order_id', $orderIds)->groupBy('product_name')
            ->selectRaw('product_name, SUM(quantity) as q, SUM(total_amount) as amt')->orderByDesc('amt')->limit(10)->get();
        if ($top->isNotEmpty()) {
            $rows[] = self::row('head', ['Top products', null, null, null, null]);
            foreach ($top as $t) {
                $rows[] = self::row('line', [$t->product_name, (float) $t->q, (float) $t->amt, null, null]);
            }
        }

        return [
            'columns' => [['Particulars', 'text'], ['Orders / qty', 'num'], ['Sales', 'money'], ['Returns', 'money'], ['Net', 'money']], 'rows' => $rows,
            'tiles' => [['Gross sales', $totS, $totO . ' orders', null], ['Returns', $totR, null, 'red'], ['Net sales', $totS - $totR, $totO ? 'Avg ' . \App\Support\Bdt::money(($totS - $totR) / $totO) . ' / order' : null, 'green']],
        ];
    }

    protected function stock(): array
    {
        $own = app(\App\Services\StockService::class)->usesOwnStock();

        if ($own) {
            $items = DB::table('product_variants as v')->join('products as p', 'p.id', '=', 'v.product_id')->whereNull('p.deleted_at')->where('v.stock_quantity', '!=', 0)
                ->get(['p.name', 'v.sku', 'v.stock_quantity as q', DB::raw('COALESCE(v.purchase_price, p.purchase_price, 0) as cost')])
                ->concat(DB::table('products as p')->whereNull('p.deleted_at')->where('p.stock_quantity', '!=', 0)
                    ->whereNotExists(fn ($q) => $q->from('product_variants')->whereColumn('product_variants.product_id', 'p.id'))
                    ->get(['p.name', DB::raw('NULL as sku'), 'p.stock_quantity as q', DB::raw('COALESCE(p.purchase_price, 0) as cost')]));
        } else {
            $avg = DB::table('inventory_batches')->groupBy('product_id', 'variant_id')
                ->selectRaw('product_id, variant_id, SUM(quantity * purchase_price) / NULLIF(SUM(quantity), 0) as avg_cost');
            $items = DB::table('inventory_stocks as s')->join('products as p', 'p.id', '=', 's.product_id')->leftJoin('product_variants as v', 'v.id', '=', 's.variant_id')
                ->leftJoinSub($avg, 'b', fn ($j) => $j->on('b.product_id', '=', 's.product_id')->whereRaw('COALESCE(b.variant_id, 0) = COALESCE(s.variant_id, 0)'))
                ->groupBy('s.product_id', 's.variant_id', 'p.name', 'v.sku', 'b.avg_cost', 'v.purchase_price', 'p.purchase_price')
                ->havingRaw('SUM(s.quantity) <> 0')
                ->get(['p.name', 'v.sku', DB::raw('SUM(s.quantity) as q'), DB::raw('COALESCE(b.avg_cost, v.purchase_price, p.purchase_price, 0) as cost')]);
        }

        $rows = $items->sortByDesc(fn ($i) => (float) $i->q * (float) $i->cost)->map(fn ($i) => self::row('plain', [$i->name, $i->sku, (float) $i->q, (float) $i->cost, round((float) $i->q * (float) $i->cost, 2)]))->values()->all();
        $value = array_sum(array_map(fn ($r) => $r['cells'][4], $rows));
        $rows[] = self::row('total', ['Total · ' . count($rows) . ' items', '', array_sum(array_map(fn ($r) => $r['cells'][2], $rows)), null, $value]);

        return [
            'columns' => [['Product', 'text'], ['SKU', 'text'], ['Qty', 'num'], ['Avg cost', 'money'], ['Value', 'money']], 'rows' => $rows,
            'tiles' => [['Stock value', $value, $own ? 'Purchase price' : 'Weighted average cost', null]],
        ];
    }

    protected function aging(string $kind): array
    {
        $data = app(Aging::class)->{$kind}();
        $names = MasterProfile::whereIn('id', $data->pluck('profile_id'))->pluck('display_name', 'id');
        $rows = $data->sortByDesc('total')->map(fn ($r) => self::row('plain', [$names[$r['profile_id']] ?? '—', $r['total'], $r['buckets'][0] ?: null, $r['buckets'][1] ?: null, $r['buckets'][2] ?: null, $r['buckets'][3] ?: null],
            [3 => 'amber', 4 => 'amber', 5 => 'red']))->values()->all();
        $tot = fn ($i) => (float) $data->sum(fn ($r) => $r['buckets'][$i]);
        $rows[] = self::row('total', ['All ' . $data->count(), (float) $data->sum('total'), $tot(0), $tot(1), $tot(2), $tot(3)]);

        return ['columns' => [[$kind === 'receivables' ? 'Customer' : 'Supplier', 'text'], ['Total', 'money'], ['0–30', 'money'], ['31–60', 'money'], ['61–90', 'money'], ['90+', 'money']], 'rows' => $rows];
    }

    protected function expenses(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $cats = ExpenseCategories::all();
        $sums = $this->sums($from, $to);
        $months = max(1, $from->startOfMonth()->diffInMonths($to->startOfMonth()) + 1);
        $rows = [];
        foreach ($cats as $c) {
            $spent = $this->bal($sums, $c);
            $budget = (float) $c->monthly_budget * $months;
            if (abs($spent) < 0.005 && $budget <= 0) {
                continue;
            }
            $rows[] = self::row('plain', [$c->name, $budget ?: null, $spent, $budget ? $budget - $spent : null, $budget ? round($spent / $budget * 100, 1) : null],
                [2 => $budget && $spent > $budget ? 'red' : '', 3 => $budget && $spent > $budget ? 'red' : 'green']);
        }
        usort($rows, fn ($x, $y) => $y['cells'][2] <=> $x['cells'][2]);
        $b = array_sum(array_map(fn ($r) => (float) $r['cells'][1], $rows));
        $s = array_sum(array_map(fn ($r) => (float) $r['cells'][2], $rows));
        $rows[] = self::row('total', ['Total', $b, $s, $b - $s, $b ? round($s / $b * 100, 1) : null]);

        return ['columns' => [['Category', 'text'], ['Budget', 'money'], ['Spent', 'money'], ['Left', 'money'], ['% used', 'pct']], 'rows' => $rows];
    }

    protected function loanSchedule(): array
    {
        $until = CarbonImmutable::today()->addMonths(12)->toDateString();
        $items = LoanRepaymentSchedule::with('loan:id,name,master_profile_id,installments,status')->where('status', '!=', 'paid')->where('due_date', '<=', $until)
            ->whereHas('loan', fn ($q) => $q->where('status', 'active'))->get()
            ->map(fn ($s) => ['due' => $s->due_date, 'party' => $s->loan->name, 'plan' => 'Loan', 'no' => ($s->installment_no ?? '') . ($s->loan->installments ? '/' . $s->loan->installments : ''), 'dir' => 'দিবো', 'amount' => round($s->payable() - (float) $s->paid_amount, 2)])
            ->concat(Installment::with('plan.profile:id,display_name')->where('status', '!=', 'paid')->where('due_date', '<=', $until)
                ->whereHas('plan', fn ($q) => $q->where('status', 'active'))->get()
                ->map(fn ($k) => ['due' => $k->due_date, 'party' => $k->plan->profile?->display_name ?? '—', 'plan' => $k->plan->role === 'customer' ? 'Kisti sale' : 'Supplier kisti',
                    'no' => "{$k->no}/{$k->plan->count}", 'dir' => $k->plan->role === 'customer' ? 'পাবো' : 'দিবো', 'amount' => round((float) $k->adjusted_amount - (float) $k->paid_amount, 2)]))
            ->sortBy(fn ($r) => $r['due']->timestamp)->values();

        $rows = [];
        foreach ($items->groupBy(fn ($r) => $r['due']->format('Y-m')) as $m => $g) {
            $rows[] = self::row('head', [CarbonImmutable::parse("{$m}-01")->format('F Y'), null, null, null, null, null]);
            foreach ($g as $r) {
                $rows[] = self::row('line', [$r['due']->format('d M'), $r['party'], $r['plan'], $r['no'], $r['dir'], $r['amount']], [5 => $r['dir'] === 'দিবো' ? 'red' : 'green']);
            }
        }
        $pay = (float) $items->where('dir', 'দিবো')->sum('amount');
        $get = (float) $items->where('dir', 'পাবো')->sum('amount');

        return [
            'period' => 'Next 12 months',
            'columns' => [['Due', 'text'], ['Party', 'text'], ['Plan', 'text'], ['Kisti', 'text'], ['', 'text'], ['Amount', 'money']], 'rows' => $rows,
            'tiles' => [['দিতে হবে', $pay, null, 'red'], ['পাবো', $get, null, 'green']],
        ];
    }

    protected function payroll(CarbonImmutable $from): array
    {
        $fy = AccountsPeriod::fyStart($from);
        $runs = PayrollRun::withCount('items')->whereBetween('month', [$fy->toDateString(), $fy->addYear()->subDay()->toDateString()])->orderBy('month')->get();
        $rows = $runs->map(fn ($r) => self::row('plain', [$r->month->format('F Y'), (int) $r->items_count, (float) $r->gross, (float) $r->bonus, -(float) $r->advance_deducted, (float) $r->net, (float) $r->paid, round((float) $r->net - (float) $r->paid, 2)],
            [7 => (float) $r->net - (float) $r->paid > 0.5 ? 'amber' : '']))->all();
        $rows[] = self::row('total', ['Total', null, (float) $runs->sum('gross'), (float) $runs->sum('bonus'), -(float) $runs->sum('advance_deducted'), (float) $runs->sum('net'), (float) $runs->sum('paid'), round((float) $runs->sum('net') - (float) $runs->sum('paid'), 2)]);
        $adv = (float) EmployeeAdvance::where('balance', '>', 0)->sum('balance');

        return [
            'period' => AccountsPeriod::fyLabel($fy),
            'columns' => [['Month', 'text'], ['Staff', 'num'], ['Gross', 'money'], ['Bonus', 'money'], ['Advance cut', 'money'], ['Net', 'money'], ['Paid', 'money'], ['Unpaid', 'money']], 'rows' => $rows,
            'tiles' => [['Net salary (FY)', (float) $runs->sum('net'), $runs->count() . ' months', null], ['Advances outstanding', $adv, null, 'amber']],
        ];
    }
}
