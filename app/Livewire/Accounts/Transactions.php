<?php

namespace App\Livewire\Accounts;

use App\Enums\Accounts\JournalEntryStatus;
use App\Models\JournalEntry;
use App\Services\Accounts\Vouchers;
use App\Services\Profiles\LedgerAccounts;
use App\Support\AccountsAccess;
use App\Support\AccountsPeriod;
use App\Support\Bdt;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;

/**
 * Accounts Panel › Transactions (day book) — design: Transactions.dc.html.
 * Every voucher in the period grouped by day, with money in / out on the
 * cash, bank and mobile-banking accounts. Drafts show faded, reversed /
 * rejected struck through, transfers grey on both sides.
 */
#[Title('Transactions')]
#[Layout('layouts.accounts.app')]
class Transactions extends Component
{
    use WithPagination;

    public const PER_PAGE = 25;

    public const PERIODS = ['today' => 'Today', 'yesterday' => 'Yesterday', 'week' => 'This week', 'month' => 'This month', 'custom' => 'Custom'];

    public const TYPES = ['' => 'All', 'receipt' => 'Receipt', 'payment' => 'Payment', 'expense' => 'Expense', 'contra' => 'Transfer', 'journal' => 'Journal', 'emi' => 'Loan / EMI', 'salary' => 'Salary'];

    #[Url(except: 'week')]
    public string $period = 'week';

    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    #[Url(except: '')]
    public string $type = '';

    #[Url(except: '')]
    public string $account = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: true)]
    public bool $drafts = true;

    #[Url(except: '')]
    public string $q = '';

    #[Url(as: 'v', except: null)]
    public ?int $sel = null;

    public ?int $reversing = null;
    public string $reverseReason = '';

    public function mount(): void
    {
        AccountsAccess::authorize('view');
        $this->period = array_key_exists($this->period, self::PERIODS) ? $this->period : 'week';
    }

    public function updated($name): void
    {
        if (in_array($name, ['period', 'from', 'to', 'type', 'account', 'status', 'drafts', 'q'], true)) {
            $this->resetPage();
        }
    }

    public function setPeriod(string $p): void
    {
        $this->period = array_key_exists($p, self::PERIODS) ? $p : 'week';
        if ($p === 'custom' && ! $this->from) {
            $this->from = now()->startOfMonth()->toDateString();
            $this->to = today()->toDateString();
        }
        $this->resetPage();
    }

    public function setType(string $t): void
    {
        $this->type = array_key_exists($t, self::TYPES) ? $t : '';
        $this->resetPage();
    }

    #[On('voucher-saved')]
    public function saved(?int $id = null): void
    {
        $this->sel = $id ?? $this->sel;
    }

    #[Computed]
    public function range(): array
    {
        return AccountsPeriod::range($this->period, $this->from ?: null, $this->to ?: null);
    }

    #[Computed]
    public function cashIds(): array
    {
        return collect(app(LedgerAccounts::class)->cashAccounts())->pluck('id')->all();
    }

    /** Transaction types behind each voucher-type chip (for module entries without a voucher type). */
    protected function typeFilter(Builder $q, string $type): void
    {
        $map = collect(\App\Enums\Accounts\TransactionType::cases())
            ->filter(fn ($t) => app(Vouchers::class)->typeKey(new JournalEntry(['transaction_type' => $t])) === $type)
            ->map->value->values()->all();

        $q->where(fn ($w) => $w->where('voucher_type', $type)
            ->orWhere(fn ($w2) => $w2->whereNull('voucher_type')->whereIn('transaction_type', $map)));
    }

    protected function base(): Builder
    {
        [$start, $end] = $this->range;

        $q = JournalEntry::query()
            ->where('status', '!=', JournalEntryStatus::VOID->value)
            ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()]);

        if ($this->type !== '') {
            $this->typeFilter($q, $this->type);
        }
        if ($this->account !== '') {
            $q->whereHas('lines', fn ($l) => $l->where('account_id', (int) $this->account));
        }
        if (! $this->drafts) {
            $q->whereNotIn('status', [JournalEntryStatus::DRAFT->value, JournalEntryStatus::SCHEDULED->value]);
        }
        match ($this->status) {
            'posted'   => $q->where('status', 'posted')->whereDoesntHave('reversingEntry'),
            'draft'    => $q->whereIn('status', ['draft', 'scheduled']),
            'reversed' => $q->where('status', 'posted')->whereHas('reversingEntry'),
            'rejected' => $q->where('status', 'rejected'),
            default    => null,
        };
        if (($s = trim($this->q)) !== '') {
            $q->where(fn ($w) => $w->where('voucher_ref', 'like', "%{$s}%")->orWhere('entry_number', 'like', "%{$s}%")
                ->orWhere('description', 'like', "%{$s}%")->orWhere('party_label', 'like', "%{$s}%")
                ->orWhereHas('profile', fn ($p) => $p->where('display_name', 'like', "%{$s}%"))
                ->when(is_numeric($s), fn ($w) => $w->orWhere('amount', (float) $s))
                // "#123" or "order 123" finds every journal of that order.
                ->when(preg_match('/^(?:#|order\s*#?)\s*(\d+)$/i', $s, $m), fn ($w) => $w->orWhere(fn ($o) => $o->where('source_type', \App\Models\Order::class)->where('source_id', (int) $m[1]))));
        }

        return $q;
    }

    #[Computed]
    public function entries()
    {
        return $this->base()
            ->with(['lines.account:id,name,subtype', 'profile:id,display_name', 'createdBy:id,name', 'postedBy:id,name', 'reversingEntry:id,reversed_journal_entry_id,voucher_ref,entry_number', 'attachments'])
            ->orderByDesc('entry_date')->orderByDesc('id')
            ->paginate(self::PER_PAGE);
    }

    /** Cash-side view of an entry: direction, amount, account text, status. */
    public function present(JournalEntry $e): array
    {
        $v = app(Vouchers::class);
        $cash = $e->lines->filter(fn ($l) => in_array($l->account_id, $this->cashIds, true));
        $dr = (float) $cash->sum('debit');
        $cr = (float) $cash->sum('credit');
        $key = $v->typeKey($e);

        $dir = match (true) {
            $key === 'contra' && $cash->count() >= 2 => 'transfer',
            $dr - $cr > 0.004 => 'in',
            $cr - $dr > 0.004 => 'out',
            default => 'none',
        };
        $amount = match ($dir) {
            'in' => $dr - $cr, 'out' => $cr - $dr,
            'transfer' => (float) $cash->sum('debit'),
            default => (float) $e->amount,
        };

        $status = match (true) {
            $e->status === JournalEntryStatus::REJECTED => 'Rejected',
            $e->status->isPending() => 'Draft',
            $e->reversingEntry->isNotEmpty() => 'Reversed',
            default => 'Posted',
        };

        $account = $dir === 'transfer'
            ? ($cash->firstWhere('credit', '>', 0)?->account?->name . ' → ' . $cash->firstWhere('debit', '>', 0)?->account?->name)
            : ($cash->first()?->account?->name ?? '—');

        return [
            'dir' => $dir, 'amount' => round($amount, 2), 'status' => $status, 'account' => $account,
            'type' => $v->typeLabel($e), 'party' => $v->partyOf($e),
        ];
    }

    /** Period summary over money accounts. */
    #[Computed]
    public function summary(): array
    {
        [$start, $end] = $this->range;
        $cash = $this->cashIds ?: [0];

        $rows = DB::table('journal_entry_lines as l')
            ->join('journal_entries as je', 'je.id', '=', 'l.journal_entry_id')
            ->whereIn('l.account_id', $cash)
            ->whereBetween('je.entry_date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('je.status', ['posted', 'draft', 'scheduled'])
            ->selectRaw("je.id, je.status, CASE WHEN je.voucher_type = 'contra' OR je.transaction_type = 'transfer' THEN 1 ELSE 0 END as is_transfer,
                SUM(l.debit) as d, SUM(l.credit) as c")
            ->groupBy('je.id', 'je.status', 'je.voucher_type', 'je.transaction_type')
            ->get();

        $sum = fn ($status, $col, $transfer = 0) => round($rows->filter(fn ($r) => ($status === 'posted' ? $r->status === 'posted' : $r->status !== 'posted') && (int) $r->is_transfer === $transfer)
            ->sum(fn ($r) => max(0, $col === 'in' ? (float) $r->d - (float) $r->c : (float) $r->c - (float) $r->d)), 2);

        $counts = JournalEntry::whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->where('status', '!=', 'void')->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');

        $in = $sum('posted', 'in');
        $out = $sum('posted', 'out');
        $posted = (int) ($counts['posted'] ?? 0);
        $draft = (int) ($counts['draft'] ?? 0) + (int) ($counts['scheduled'] ?? 0);
        $total = (int) $counts->sum();

        return [
            'in' => $in, 'inDraft' => $sum('draft', 'in'), 'out' => $out, 'outDraft' => $sum('draft', 'out'),
            'net' => $in - $out,
            'transfer' => round($rows->filter(fn ($r) => $r->status === 'posted' && (int) $r->is_transfer === 1)->sum(fn ($r) => (float) $r->d), 2),
            'count' => $total, 'posted' => $posted, 'draft' => $draft, 'other' => $total - $posted - $draft,
        ];
    }

    /** Posted in / out per day for the day header rows. */
    #[Computed]
    public function dayTotals(): array
    {
        [$start, $end] = $this->range;

        return DB::table('journal_entry_lines as l')
            ->join('journal_entries as je', 'je.id', '=', 'l.journal_entry_id')
            ->whereIn('l.account_id', $this->cashIds ?: [0])
            ->where('je.status', 'posted')
            ->whereRaw("NOT (COALESCE(je.voucher_type, '') = 'contra' OR je.transaction_type = 'transfer')")
            ->whereBetween('je.entry_date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('je.entry_date')
            ->selectRaw('je.entry_date as d, SUM(l.debit) as i, SUM(l.credit) as o')
            ->get()
            ->mapWithKeys(fn ($r) => [substr((string) $r->d, 0, 10) => ['in' => (float) $r->i, 'out' => (float) $r->o]])
            ->all();
    }

    #[Computed]
    public function current(): ?JournalEntry
    {
        $id = $this->sel ?? $this->entries->first()?->id;

        return $id ? JournalEntry::with(['lines.account', 'attachments', 'profile:id,display_name', 'createdBy:id,name', 'postedBy:id,name', 'reversingEntry', 'reversedEntry', 'rejectedBy:id,name'])->find($id) : null;
    }

    public function open(int $id): void
    {
        $this->sel = $id;
    }

    public function reverse(): void
    {
        try {
            app(Vouchers::class)->reverse(JournalEntry::findOrFail($this->reversing), $this->reverseReason ?: null);
        } catch (RuntimeException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        }

        $this->reset('reversing', 'reverseReason');
        $this->dispatch('toast', type: 'success', message: 'Reversed — an opposite journal was posted.');
    }

    public function export()
    {
        AccountsAccess::authorize('reports');
        $path = storage_path('app/transactions-' . now()->format('Ymd-His') . '-' . auth()->id() . '.xlsx');
        $writer = new Writer();
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(['Date', 'Voucher', 'Type', 'Party', 'Note', 'Account', 'In', 'Out', 'Status']));

        $this->base()->with(['lines', 'profile:id,display_name', 'reversingEntry:id,reversed_journal_entry_id'])
            ->orderBy('entry_date')->orderBy('id')
            ->chunk(500, function ($chunk) use ($writer) {
                foreach ($chunk as $e) {
                    $p = $this->present($e);
                    $writer->addRow(Row::fromValues([
                        $e->entry_date->toDateString(), $e->ref(), $p['type'], $p['party'], $e->description, $p['account'],
                        in_array($p['dir'], ['in', 'transfer'], true) ? $p['amount'] : null,
                        in_array($p['dir'], ['out', 'transfer'], true) ? $p['amount'] : null,
                        $p['status'],
                    ]));
                }
            });

        $writer->close();

        return response()->download($path, 'day-book.xlsx')->deleteFileAfterSend();
    }

    public function render()
    {
        [$start, $end] = $this->range;

        return view('livewire.accounts.transactions', [
            'money'     => fn ($v) => Bdt::money($v),
            'rangeText' => AccountsPeriod::label($start, $end),
            'accounts'  => app(LedgerAccounts::class)->cashAccounts(),
        ])->layoutData(['active' => 'transactions', 'heading' => 'Transactions', 'crumb' => 'Accounts / Transactions']);
    }
}
