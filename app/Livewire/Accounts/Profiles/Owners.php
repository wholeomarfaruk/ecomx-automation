<?php

namespace App\Livewire\Accounts\Profiles;

use App\Enums\Accounts\AccountType;
use App\Enums\Accounts\JournalEntryStatus;
use App\Enums\Accounts\TransactionType;
use App\Livewire\Accounts\Profiles\Concerns\HandlesVoucherModals;
use App\Models\Loan;
use App\Models\MasterProfile;
use App\Services\Profiles\LedgerAccounts;
use App\Services\Profiles\ProfileBalances;
use App\Support\Bdt;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Accounts Panel › Owners — design: Owners.dc.html.
 * Capital and drawings per owner (Owner Capital / Owner Drawings lines
 * tagged to the owner's profile), profit for the year from posted entries,
 * profit share = profit × share %. Drawings are equity, never expense.
 */
#[Title('Owners')]
#[Layout('layouts.accounts.app')]
class Owners extends Component
{
    use HandlesVoucherModals;

    public bool $allTx = false;

    #[Computed]
    public function owners(): Collection
    {
        return MasterProfile::withRole('owner')->with('roles')->orderBy('id')->get();
    }

    /** Posted profit for the year: income (credit − debit) − expense (debit − credit). */
    #[Computed]
    public function profit(): float
    {
        $row = DB::table('journal_entry_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('je.status', JournalEntryStatus::POSTED->value)
            ->whereBetween('je.entry_date', [now()->startOfYear()->toDateString(), now()->toDateString()])
            ->selectRaw('
                COALESCE(SUM(CASE WHEN a.type = ? THEN jl.credit - jl.debit ELSE 0 END), 0) as income,
                COALESCE(SUM(CASE WHEN a.type = ? THEN jl.debit - jl.credit ELSE 0 END), 0) as expense',
                [AccountType::INCOME->value, AccountType::EXPENSE->value])
            ->first();

        return round((float) $row->income - (float) $row->expense, 2);
    }

    /** Drawings this year by owner and month: [profile_id => [1 => amount, …]] plus the untagged total. */
    #[Computed]
    public function drawingsByMonth(): array
    {
        $drw = app(LedgerAccounts::class)->id(LedgerAccounts::DRAWINGS);
        $month = DB::connection()->getDriverName() === 'sqlite' ? "CAST(strftime('%m', je.entry_date) AS INTEGER)" : 'MONTH(je.entry_date)';

        $rows = DB::table('journal_entry_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->where('jl.account_id', $drw)
            ->where('je.status', JournalEntryStatus::POSTED->value)
            ->whereBetween('je.entry_date', [now()->startOfYear()->toDateString(), now()->toDateString()])
            ->groupByRaw("jl.subledger_type, jl.subledger_id, {$month}")
            ->selectRaw("jl.subledger_type, jl.subledger_id, {$month} as m, COALESCE(SUM(jl.debit - jl.credit), 0) as amount")
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $key = $r->subledger_type === MasterProfile::class ? (int) $r->subledger_id : 0;
            $out[$key][(int) $r->m] = ($out[$key][(int) $r->m] ?? 0) + (float) $r->amount;
        }

        return $out;
    }

    #[Computed]
    public function summary(): array
    {
        $a = app(LedgerAccounts::class);
        $capital = $this->accountBalance($a->id(LedgerAccounts::CAPITAL), 'cr');
        $drawingsYear = collect($this->drawingsByMonth)->flatten()->sum();
        $thisMonth = collect($this->drawingsByMonth)->sum(fn ($months) => $months[now()->month] ?? 0);
        $shares = $this->owners->map(fn ($o) => (float) ($o->roleSettings('owner')['share_percent'] ?? 0))->filter();

        return [
            'capital'     => $capital,
            'count'       => $this->owners->count(),
            'shares'      => $shares->map(fn ($s) => rtrim(rtrim(number_format($s, 2), '0'), '.'))->implode(' / '),
            'profit'      => $this->profit,
            'drawings'    => $drawingsYear,
            'month'       => $thisMonth,
            'equity'      => $capital + $this->profit - $drawingsYear,
        ];
    }

    protected function accountBalance(int $accountId, string $dir): float
    {
        $sum = DB::table('journal_entry_lines as jl')->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->where('jl.account_id', $accountId)->where('je.status', JournalEntryStatus::POSTED->value)
            ->selectRaw('COALESCE(SUM(jl.debit), 0) as d, COALESCE(SUM(jl.credit), 0) as c')->first();

        return round($dir === 'cr' ? (float) $sum->c - (float) $sum->d : (float) $sum->d - (float) $sum->c, 2);
    }

    /** Owner card in the design's shape. */
    #[Computed]
    public function cards(): array
    {
        $balances = app(ProfileBalances::class)->forProfiles($this->owners->pluck('id')->all());
        $months = range(1, now()->month);
        $labels = ['J', 'F', 'M', 'A', 'M', 'J', 'J', 'A', 'S', 'O', 'N', 'D'];
        $max = max(1, collect($this->drawingsByMonth)->flatten()->max() ?? 1);

        return $this->owners->map(function (MasterProfile $o) use ($balances, $months, $labels, $max) {
            $b = $balances[$o->id];
            $share = (float) ($o->roleSettings('owner')['share_percent'] ?? 0);
            $profitShare = round($this->profit * $share / 100, 2);
            $byMonth = $this->drawingsByMonth[$o->id] ?? [];
            $drawings = array_sum($byMonth);
            $capital = round((float) $b->capital, 2);

            return [
                'profile'  => $o,
                'share'    => rtrim(rtrim(number_format($share, 2), '0'), '.') . '%',
                'lender'   => $o->hasRole('lender') && (float) $b->loan > 0.5 ? Bdt::money($b->loan) : null,
                'capital'  => Bdt::money($capital),
                'profit'   => Bdt::money($profitShare),
                'drawings' => Bdt::money($drawings),
                'equity'   => Bdt::money($capital + $profitShare - $drawings),
                'months'   => array_map(fn ($m) => [
                    'label'  => $labels[$m - 1],
                    'height' => max((int) round(($byMonth[$m] ?? 0) / $max * 40), 2),
                    'on'     => ($byMonth[$m] ?? 0) > 0,
                    'title'  => Carbon::create(null, $m)->format('M') . ' · ' . Bdt::money($byMonth[$m] ?? 0),
                ], $months),
            ];
        })->all();
    }

    /** Capital in / drawing / owner-loan repaid entries, newest first. */
    #[Computed]
    public function transactions(): array
    {
        $ownerIds = $this->owners->pluck('id')->all();
        $ownerLoans = Loan::whereIn('master_profile_id', $ownerIds)->pluck('master_profile_id', 'id');
        $names = $this->owners->pluck('display_name', 'id');

        $entries = DB::table('journal_entries as je')
            ->where('je.status', JournalEntryStatus::POSTED->value)
            ->where(fn ($q) => $q->whereIn('je.transaction_type', [TransactionType::OWNER_INVESTMENT->value, TransactionType::OWNER_WITHDRAWAL->value])
                ->orWhere(fn ($q) => $q->where('je.transaction_type', TransactionType::LOAN_REPAYMENT->value)
                    ->where('je.source_type', Loan::class)->whereIn('je.source_id', $ownerLoans->keys())))
            ->orderByDesc('je.entry_date')->orderByDesc('je.id')
            ->limit($this->allTx ? 100 : 8)
            ->get(['je.id', 'je.entry_date', 'je.transaction_type', 'je.description', 'je.source_id']);

        $lines = DB::table('journal_entry_lines as jl')->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->whereIn('jl.journal_entry_id', $entries->pluck('id'))
            ->get(['jl.journal_entry_id', 'jl.debit', 'jl.credit', 'jl.subledger_type', 'jl.subledger_id', 'a.subtype', 'a.name'])
            ->groupBy('journal_entry_id');

        return $entries->map(function ($e) use ($lines, $names, $ownerLoans) {
            $ls = $lines->get($e->id, collect());
            $cash = $ls->first(fn ($l) => in_array($l->subtype, LedgerAccounts::CASH_SUBTYPES, true));
            $tagged = $ls->first(fn ($l) => $l->subledger_type === MasterProfile::class);
            $ownerId = $tagged?->subledger_id ?? ($e->transaction_type === TransactionType::LOAN_REPAYMENT->value ? $ownerLoans[$e->source_id] ?? null : null);

            $type = match ($e->transaction_type) {
                TransactionType::OWNER_INVESTMENT->value => 'Capital in',
                TransactionType::OWNER_WITHDRAWAL->value => 'Drawing',
                default => 'Loan repaid',
            };
            $amount = $cash ? (float) $cash->debit + (float) $cash->credit : (float) $ls->sum('debit');
            $out = $type !== 'Capital in';

            return [
                'date'      => Carbon::parse($e->entry_date)->format('d M'),
                'owner'     => $names[$ownerId] ?? '—',
                'type'      => $type,
                'typeClass' => ['Drawing' => 'bg-[#fee2e2] text-[#991b1b]', 'Capital in' => 'bg-[#d1fae5] text-[#065f46]'][$type] ?? 'bg-[#e0e7ff] text-[#3730a3]',
                'account'   => $cash?->name ?? '—',
                'note'      => $e->description ?? '',
                'amount'    => Bdt::money($out ? -$amount : $amount, ! $out),
                'out'       => $out,
            ];
        })->all();
    }

    public function render()
    {
        return view('livewire.accounts.profiles.owners', [
            'money' => fn ($v) => Bdt::money($v),
        ])->layoutData(['active' => 'owners']);
    }
}
