<?php

namespace App\Services\Profiles;

use App\Enums\Accounts\JournalEntryStatus;
use App\Models\Customer;
use App\Models\MasterProfile;
use App\Models\Supplier;
use App\Support\Bdt;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Every profile's money position, straight from the posted ledger, in one
 * grouped SQL query. A journal line belongs to a profile when its
 * subledger is one of the profile's Customer / Supplier rows, the profile
 * itself (lender / owner / employee postings), or when it hits one of the
 * profile's loan accounts.
 *
 * Columns are positive in their natural direction: receivable = they owe
 * us, payable = we owe them, and so on.
 */
class ProfileBalances
{
    public const COLUMNS = [
        'receivable', 'customer_advance', 'payable', 'supplier_advance', 'loan',
        'capital', 'drawings', 'salary_payable', 'salary_advance',
    ];

    public function __construct(protected LedgerAccounts $accounts) {}

    /** The grouped per-profile aggregate, ready for joinSub(..., 'pb', ...). */
    public function query(?string $from = null, ?string $to = null): Builder
    {
        $a = fn (string $code) => $this->accounts->id($code);

        $sum = fn (string $cond, string $dir) => "COALESCE(SUM(CASE WHEN {$cond} THEN " .
            ($dir === 'dr' ? 'jl.debit - jl.credit' : 'jl.credit - jl.debit') . ' ELSE 0 END), 0)';

        $profileId = $this->profileIdExpression();

        $query = DB::table('journal_entry_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->leftJoin('customers as c', fn ($j) => $j->on('c.id', '=', 'jl.subledger_id')->where('jl.subledger_type', Customer::class))
            ->leftJoin('suppliers as s', fn ($j) => $j->on('s.id', '=', 'jl.subledger_id')->where('jl.subledger_type', Supplier::class))
            ->leftJoin('loans as l', 'l.loan_payable_account_id', '=', 'jl.account_id')
            ->where('je.status', JournalEntryStatus::POSTED->value)
            ->whereRaw("{$profileId} IS NOT NULL")
            ->groupByRaw($profileId)
            ->selectRaw("{$profileId} as profile_id")
            ->selectRaw($sum('jl.account_id = ' . $a(LedgerAccounts::RECEIVABLE), 'dr') . ' as receivable')
            ->selectRaw($sum('jl.account_id IN (' . $a(LedgerAccounts::CUSTOMER_CREDIT) . ', ' . $a(LedgerAccounts::CUSTOMER_ADVANCE) . ')', 'cr') . ' as customer_advance')
            ->selectRaw($sum('jl.account_id = ' . $a(LedgerAccounts::PAYABLE), 'cr') . ' as payable')
            ->selectRaw($sum('jl.account_id = ' . $a(LedgerAccounts::SUPPLIER_ADVANCE), 'dr') . ' as supplier_advance')
            ->selectRaw($sum('l.id IS NOT NULL', 'cr') . ' as loan')
            ->selectRaw($sum('jl.account_id = ' . $a(LedgerAccounts::CAPITAL), 'cr') . ' as capital')
            ->selectRaw($sum('jl.account_id = ' . $a(LedgerAccounts::DRAWINGS), 'dr') . ' as drawings')
            ->selectRaw($sum('jl.account_id = ' . $a(LedgerAccounts::SALARY_PAYABLE), 'cr') . ' as salary_payable')
            ->selectRaw($sum('jl.account_id = ' . $a(LedgerAccounts::SALARY_ADVANCE), 'dr') . ' as salary_advance')
            ->selectRaw('MAX(je.created_at) as last_at');

        if ($from) {
            $query->where('je.entry_date', '>=', $from);
        }
        if ($to) {
            $query->where('je.entry_date', '<=', $to);
        }

        return $query;
    }

    /** SQL resolving a journal line to its owning profile id. */
    public function profileIdExpression(): string
    {
        $mp = DB::escape(MasterProfile::class);

        return "COALESCE(c.master_profile_id, s.master_profile_id, CASE WHEN jl.subledger_type = {$mp} THEN jl.subledger_id END, l.master_profile_id)";
    }

    /**
     * SQL CASE giving the balance kind used by the "Balance" chips:
     * cap / pay / due / adv / clear. Mirrors describe() below.
     */
    public static function kindSql(string $t = 'pb'): string
    {
        $c = fn ($col) => "COALESCE({$t}.{$col}, 0)";
        $owe = "({$c('payable')} + {$c('salary_payable')})";

        return "CASE
            WHEN {$c('capital')} - {$c('drawings')} > 0.5 THEN 'cap'
            WHEN {$c('loan')} > 0.5 THEN 'pay'
            WHEN {$c('receivable')} > 0.5 AND {$owe} > 0.5 THEN (CASE WHEN {$owe} >= {$c('receivable')} THEN 'pay' ELSE 'due' END)
            WHEN {$c('receivable')} > 0.5 THEN 'due'
            WHEN {$owe} > 0.5 THEN 'pay'
            WHEN {$c('customer_advance')} + {$c('supplier_advance')} + {$c('salary_advance')} > 0.5 THEN 'adv'
            ELSE 'clear' END";
    }

    /** Aggregates for the given profile ids, keyed by id (missing ids → zeros). */
    public function forProfiles(array $ids): Collection
    {
        if (! $ids) {
            return collect();
        }

        $rows = $this->query()->whereIn(DB::raw($this->profileIdExpression()), $ids)->get()->keyBy('profile_id');

        return collect($ids)->mapWithKeys(fn ($id) => [$id => $rows->get($id) ?? $this->empty($id)]);
    }

    public function forProfile(int $id): object
    {
        return $this->forProfiles([$id])->get($id);
    }

    public function empty(int $id): object
    {
        return (object) (['profile_id' => $id, 'last_at' => null] + array_fill_keys(self::COLUMNS, 0));
    }

    /**
     * The single figure + label shown in a profile's Balance column.
     *
     * @return array{amount: float, label: string, kind: string}
     */
    public static function describe(object $b, array $roles = []): array
    {
        $v = fn ($k) => round((float) ($b->{$k} ?? 0), 2);
        $owe = $v('payable') + $v('salary_payable');

        if ($v('capital') - $v('drawings') > 0.5) {
            return ['amount' => $v('capital') - $v('drawings'), 'label' => 'Capital', 'kind' => 'cap'];
        }
        if ($v('loan') > 0.5) {
            return ['amount' => $v('loan'), 'label' => 'Loan বাকি', 'kind' => 'pay'];
        }
        if ($v('receivable') > 0.5 && $owe > 0.5) {
            $net = $owe - $v('receivable');

            return $net >= 0
                ? ['amount' => $net, 'label' => 'Net দিবো', 'kind' => 'pay']
                : ['amount' => -$net, 'label' => 'Net পাবো', 'kind' => 'due'];
        }
        if ($v('receivable') > 0.5) {
            return ['amount' => $v('receivable'), 'label' => 'পাবো', 'kind' => 'due'];
        }
        if ($v('payable') > 0.5) {
            return ['amount' => $v('payable'), 'label' => 'দিবো', 'kind' => 'pay'];
        }
        if ($v('salary_payable') > 0.5) {
            return ['amount' => $v('salary_payable'), 'label' => 'বেতন বাকি', 'kind' => 'pay'];
        }
        if ($v('salary_advance') > 0.5) {
            return ['amount' => $v('salary_advance'), 'label' => 'Salary advance', 'kind' => 'adv'];
        }
        if ($v('customer_advance') > 0.5) {
            return ['amount' => $v('customer_advance'), 'label' => 'Advance নেওয়া', 'kind' => 'adv'];
        }
        if ($v('supplier_advance') > 0.5) {
            return ['amount' => $v('supplier_advance'), 'label' => 'Advance দেওয়া', 'kind' => 'adv'];
        }

        return ['amount' => 0.0, 'label' => 'Clear', 'kind' => 'clear'];
    }

    /** Tailwind classes for each balance kind (design: due/pay/adv/clear/cap). */
    public static function kindClass(string $kind): string
    {
        return match ($kind) {
            'due'   => 'font-bold text-[#047857]',
            'pay'   => 'font-bold text-[#b91c1c]',
            'adv'   => 'font-bold text-[#4338ca]',
            'cap'   => 'font-bold text-[#92400e]',
            default => 'font-bold text-[#6b7280]',
        };
    }

    public static function money(float|int|null $v): string
    {
        return Bdt::money($v);
    }
}
