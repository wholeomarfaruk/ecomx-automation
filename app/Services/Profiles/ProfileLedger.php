<?php

namespace App\Services\Profiles;

use App\Enums\Accounts\JournalEntryStatus;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\MasterProfile;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

/**
 * One profile's statement across all its roles: every posted line on a
 * party account (receivable, payable, advances, loan, capital, drawings,
 * salary) that belongs to the profile. Dr = they owe us (পাবো),
 * Cr = we owe them (দিবো); the running balance is Σ(debit − credit).
 */
class ProfileLedger
{
    public function __construct(
        protected LedgerAccounts $accounts,
        protected ProfileBalances $balances,
    ) {}

    /** Ledger account ids per role, for the Role filter. */
    public function accountsByRole(MasterProfile $profile): array
    {
        $a = fn (string $code) => $this->accounts->id($code);

        return [
            'customer' => [$a(LedgerAccounts::RECEIVABLE), $a(LedgerAccounts::CUSTOMER_CREDIT), $a(LedgerAccounts::CUSTOMER_ADVANCE)],
            'supplier' => [$a(LedgerAccounts::PAYABLE), $a(LedgerAccounts::SUPPLIER_ADVANCE)],
            'lender'   => Loan::where('master_profile_id', $profile->id)->pluck('loan_payable_account_id')->map(fn ($id) => (int) $id)->all(),
            'owner'    => [$a(LedgerAccounts::CAPITAL), $a(LedgerAccounts::DRAWINGS)],
            'employee' => [$a(LedgerAccounts::SALARY_PAYABLE), $a(LedgerAccounts::SALARY_ADVANCE)],
        ];
    }

    /**
     * @return array{opening: float, rows: list<array>, debit: float, credit: float, closing: float}
     */
    public function statement(MasterProfile $profile, ?string $from, ?string $to, ?string $role = null): array
    {
        $byRole   = $this->accountsByRole($profile);
        $accounts = $role && isset($byRole[$role]) ? $byRole[$role] : array_merge(...array_values($byRole));
        $accounts = array_values(array_filter(array_unique($accounts)));

        if (! $accounts) {
            return ['opening' => 0.0, 'rows' => [], 'debit' => 0.0, 'credit' => 0.0, 'closing' => 0.0];
        }

        $base = fn () => $this->lines($profile->id, $accounts);

        $opening = $from
            ? (float) $base()->where('je.entry_date', '<', $from)->sum(DB::raw('jl.debit - jl.credit'))
            : 0.0;

        $lines = $base()
            ->when($from, fn ($q) => $q->where('je.entry_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('je.entry_date', '<=', $to))
            ->orderBy('je.entry_date')->orderBy('je.id')->orderBy('jl.id')
            ->get(['jl.id', 'jl.debit', 'jl.credit', 'jl.account_id', 'jl.memo', 'je.id as entry_id', 'je.entry_number', 'je.entry_date', 'je.description']);

        $roleOf = [];
        foreach ($byRole as $r => $ids) {
            foreach ($ids as $id) {
                $roleOf[$id] ??= $r;
            }
        }

        $via = $this->cashAccountNames($lines->pluck('entry_id')->unique()->all());

        $running = $opening;
        $rows = $lines->map(function ($l) use (&$running, $roleOf, $via) {
            $running += (float) $l->debit - (float) $l->credit;

            return [
                'entry_id' => (int) $l->entry_id,
                'date'     => \Carbon\Carbon::parse($l->entry_date),
                'ref'      => $l->entry_number,
                'text'     => $l->description ?: ($l->memo ?: 'Journal entry'),
                'sub'      => $via[$l->entry_id] ?? ucfirst($roleOf[$l->account_id] ?? ''),
                'debit'    => (float) $l->debit,
                'credit'   => (float) $l->credit,
                'balance'  => round($running, 2),
            ];
        })->all();

        return [
            'opening' => round($opening, 2),
            'rows'    => $rows,
            'debit'   => round((float) $lines->sum('debit'), 2),
            'credit'  => round((float) $lines->sum('credit'), 2),
            'closing' => round($running, 2),
        ];
    }

    protected function lines(int $profileId, array $accounts)
    {
        return DB::table('journal_entry_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->leftJoin('customers as c', fn ($j) => $j->on('c.id', '=', 'jl.subledger_id')->where('jl.subledger_type', Customer::class))
            ->leftJoin('suppliers as s', fn ($j) => $j->on('s.id', '=', 'jl.subledger_id')->where('jl.subledger_type', Supplier::class))
            ->leftJoin('loans as l', 'l.loan_payable_account_id', '=', 'jl.account_id')
            ->where('je.status', JournalEntryStatus::POSTED->value)
            ->whereIn('jl.account_id', $accounts)
            ->whereRaw($this->balances->profileIdExpression() . ' = ?', [$profileId]);
    }

    /** entry id → "Cash box" / "City Bank" (the money side of each voucher). */
    protected function cashAccountNames(array $entryIds): array
    {
        if (! $entryIds) {
            return [];
        }

        return DB::table('journal_entry_lines as jl')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->whereIn('jl.journal_entry_id', $entryIds)
            ->whereIn('a.subtype', LedgerAccounts::CASH_SUBTYPES)
            ->orderBy('jl.id')
            ->get(['jl.journal_entry_id', 'a.name'])
            ->unique('journal_entry_id')
            ->pluck('name', 'journal_entry_id')
            ->all();
    }

    /** "৳21,500 Cr" */
    public static function drCr(float $balance): string
    {
        if (abs($balance) < 0.005) {
            return '৳0';
        }

        return \App\Support\Bdt::money(abs($balance)) . ($balance > 0 ? ' Dr' : ' Cr');
    }
}
