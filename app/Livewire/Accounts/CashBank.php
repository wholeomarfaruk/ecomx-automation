<?php

namespace App\Livewire\Accounts;

use App\Enums\Accounts\JournalEntryStatus;
use App\Enums\Accounts\TransactionType;
use App\Models\Account;
use App\Models\BankReconciliation;
use App\Models\CashCount;
use App\Models\JournalEntry;
use App\Services\Accounts\ExpenseCategories;
use App\Services\Accounts\Vouchers;
use App\Services\Profiles\LedgerAccounts;
use App\Support\AccountsAccess;
use App\Support\AccountsPeriod;
use App\Support\Bdt;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use RuntimeException;

/**
 * Accounts Panel › Cash & Bank — design: CashBank.dc.html.
 * Every cash / bank / mobile-banking account with its posted balance,
 * a ledger with running balance, cash count (shortage / excess voucher),
 * reconcile against the statement, and transfers with a charge.
 */
#[Title('Cash & Bank')]
#[Layout('layouts.accounts.app')]
class CashBank extends Component
{
    use WithFileUploads;

    public const KINDS = ['cash' => ['Cash', 'bg-[#fef3c7] text-[#92400e]', '#f59e0b'], 'bank' => ['Bank', 'bg-[#e0f2fe] text-[#075985]', '#0ea5e9'], 'mobile_banking' => ['Mobile banking', 'bg-[#fce7f3] text-[#9d174d]', '#db2777']];

    public const PARENTS = ['cash' => '1010', 'bank' => '1020', 'mobile_banking' => '1030'];

    #[Url(as: 'account', except: null)]
    public ?int $sel = null;

    #[Url(as: 'period', except: 'week')]
    public string $ledgerPeriod = 'week';

    public string $from = '';
    public string $to = '';

    public string $statement = '';

    public array $transfer = [];

    public bool $showNew = false;
    public array $newAccount = ['name' => '', 'kind' => 'bank', 'number' => '', 'opening' => null];

    public bool $showImport = false;
    public $importFile = null;
    public ?array $importResult = null;

    public function mount(): void
    {
        AccountsAccess::authorize('view');
        $this->sel ??= $this->accounts->first()['id'] ?? null;
        $this->resetTransfer();
    }

    protected function resetTransfer(): void
    {
        $ids = $this->accounts->pluck('id');
        $this->transfer = ['from' => $ids[0] ?? null, 'to' => $ids[1] ?? null, 'amount' => null, 'charge' => null, 'date' => today()->toDateString(), 'note' => ''];
    }

    #[On('voucher-saved')]
    public function refreshData(): void
    {
        unset($this->accounts, $this->drafts);
    }

    /** Account cards: posted balance, today's in / out, reconciliation state. */
    #[Computed]
    public function accounts(): Collection
    {
        $v = app(Vouchers::class);
        $raw = app(LedgerAccounts::class)->cashAccounts();
        $ids = array_column($raw, 'id');

        $today = DB::table('journal_entry_lines as l')->join('journal_entries as je', 'je.id', '=', 'l.journal_entry_id')
            ->whereIn('l.account_id', $ids ?: [0])->where('je.status', 'posted')->whereDate('je.entry_date', today())
            ->groupBy('l.account_id')->selectRaw('l.account_id, SUM(l.debit) as i, SUM(l.credit) as o')->get()->keyBy('account_id');

        $counts = CashCount::whereIn('account_id', $ids ?: [0])->select('account_id', DB::raw('MAX(count_date) as last'))->groupBy('account_id')->pluck('last', 'account_id');

        return collect($raw)->map(function ($a) use ($v, $today, $counts) {
            $kind = $a['subtype'];
            $last = $kind === 'cash' ? ($counts[$a['id']] ?? null) : $a['last_reconciled_at'];
            $days = $last ? (int) CarbonImmutable::parse($last)->startOfDay()->diffInDays(today()) : null;

            $rec = match (true) {
                $kind === 'cash' && $days === null => ['Not counted yet', false],
                $kind === 'cash' => [$days === 0 ? 'Counted today' : ($days === 1 ? 'Counted yesterday' : "Counted {$days} days ago"), $days <= 1],
                $days === null => ['Not reconciled', false],
                $days === 0 => ['Matched today', true],
                default => ["Not reconciled {$days} days", $days <= 3],
            };

            return $a + [
                'kind'    => $kind,
                'balance' => $v->postedBalance($a['id']),
                'in'      => (float) ($today[$a['id']]->i ?? 0),
                'out'     => (float) ($today[$a['id']]->o ?? 0),
                'rec'     => $rec[0],
                'recOk'   => $rec[1],
            ];
        })->values();
    }

    /** Drafts / scheduled vouchers touching money accounts: per account delta + refs. */
    #[Computed]
    public function drafts(): Collection
    {
        return DB::table('journal_entry_lines as l')->join('journal_entries as je', 'je.id', '=', 'l.journal_entry_id')
            ->whereIn('l.account_id', $this->accounts->pluck('id')->all() ?: [0])
            ->whereIn('je.status', [JournalEntryStatus::DRAFT->value, JournalEntryStatus::SCHEDULED->value])
            ->get(['l.account_id', 'l.debit', 'l.credit', 'je.id', 'je.voucher_ref', 'je.entry_number'])
            ->groupBy('account_id');
    }

    #[Computed]
    public function current(): ?array
    {
        return $this->accounts->firstWhere('id', $this->sel) ?? $this->accounts->first();
    }

    public function pick(int $id): void
    {
        $this->sel = $id;
        $this->statement = '';
    }

    #[Computed]
    public function range(): array
    {
        return AccountsPeriod::range($this->ledgerPeriod === 'custom' ? 'custom' : $this->ledgerPeriod, $this->from ?: null, $this->to ?: null);
    }

    /** The selected account's posted ledger in the period: opening, rows with running balance, totals. */
    #[Computed]
    public function ledger(): array
    {
        $a = $this->current;
        if (! $a) {
            return ['opening' => 0, 'rows' => [], 'in' => 0, 'out' => 0, 'closing' => 0];
        }

        [$start, $end] = $this->range;
        $v = app(Vouchers::class);
        $account = Account::find($a['id']);

        $before = DB::table('journal_entry_lines as l')->join('journal_entries as je', 'je.id', '=', 'l.journal_entry_id')
            ->where('l.account_id', $a['id'])->where('je.status', 'posted')->where('je.entry_date', '<', $start->toDateString())
            ->selectRaw('COALESCE(SUM(l.debit), 0) as d, COALESCE(SUM(l.credit), 0) as c')->first();
        $opening = (float) $account->opening_balance + (float) $before->d - (float) $before->c;

        $entries = JournalEntry::with(['lines' => fn ($q) => $q->where('account_id', $a['id']), 'profile:id,display_name'])
            ->where('status', 'posted')
            ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->whereHas('lines', fn ($q) => $q->where('account_id', $a['id']))
            ->orderBy('entry_date')->orderBy('id')->get();

        $run = $opening;
        $in = $out = 0;
        $rows = $entries->map(function ($e) use (&$run, &$in, &$out, $v) {
            $d = (float) $e->lines->sum('debit');
            $c = (float) $e->lines->sum('credit');
            $run += $d - $c;
            $in += $d;
            $out += $c;

            return ['date' => $e->entry_date->format('d M'), 'ref' => $e->ref(), 'id' => $e->id, 'text' => trim(($e->description ?: $v->typeLabel($e)) . ($v->partyOf($e) !== ($e->description ?: '—') ? ' · ' . $v->partyOf($e) : ''), ' ·'),
                'in' => $d, 'out' => $c, 'bal' => $run];
        })->all();

        return ['opening' => $opening, 'rows' => $rows, 'in' => $in, 'out' => $out, 'closing' => $run, 'start' => $start, 'end' => $end];
    }

    /** "2 draft entry: EMI-0319 (− ৳22,500), PV-0877 (− ৳18,000). Post হলে balance হবে ৳83,800।" */
    #[Computed]
    public function draftStrip(): ?string
    {
        $a = $this->current;
        $lines = $a ? ($this->drafts[$a['id']] ?? collect()) : collect();
        if ($lines->isEmpty()) {
            return null;
        }

        $byEntry = $lines->groupBy('id');
        $parts = $byEntry->map(function ($ls) {
            $delta = (float) $ls->sum('debit') - (float) $ls->sum('credit');

            return ($ls->first()->voucher_ref ?: $ls->first()->entry_number) . ' (' . Bdt::money($delta, true) . ')';
        })->take(4)->implode(', ');
        $after = $a['balance'] + (float) $lines->sum('debit') - (float) $lines->sum('credit');

        return $byEntry->count() . " draft entry: {$parts}। Post হলে balance হবে " . Bdt::money($after) . '।';
    }

    #[Computed]
    public function totals(): array
    {
        $acc = $this->accounts;
        $total = $acc->sum('balance');
        $draftDelta = $this->drafts->flatten(1)->sum(fn ($l) => (float) $l->debit - (float) $l->credit);
        $byKind = collect(self::KINDS)->keys()->mapWithKeys(fn ($k) => [$k => $acc->where('kind', $k)->sum('balance')])->all();
        $positive = max(1, collect($byKind)->sum(fn ($v) => max(0, $v)));

        return [
            'total'      => $total,
            'after'      => $total + $draftDelta,
            'draftCount' => $this->drafts->flatten(1)->pluck('id')->unique()->count(),
            'byKind'     => $byKind,
            'pct'        => collect($byKind)->map(fn ($v) => round(max(0, $v) / $positive * 100, 1))->all(),
        ];
    }

    /* ---------------- Cash count ---------------- */

    public function saveCount(array $counts): void
    {
        AccountsAccess::authorize('cash');
        $a = $this->current;
        abort_unless($a && $a['kind'] === 'cash', 422);

        $clean = collect(CashCount::DENOMINATIONS)->mapWithKeys(fn ($d, $k) => [$k => max(0, (int) ($counts[$k] ?? 0))])->all();
        $counted = collect($clean)->sum(fn ($n, $k) => $n * CashCount::DENOMINATIONS[$k][1]);
        $book = round((float) $a['balance'], 2);
        $diff = round($counted - $book, 2);

        DB::transaction(function () use ($a, $clean, $counted, $book, $diff) {
            $entry = null;

            if (abs($diff) >= 0.5) {
                $shortLedger = app(LedgerAccounts::class)->require('5900');
                $lines = $diff < 0
                    ? [['account_id' => $shortLedger, 'debit' => abs($diff), 'memo' => 'Cash shortage'], ['account_id' => $a['id'], 'credit' => abs($diff)]]
                    : [['account_id' => $a['id'], 'debit' => $diff], ['account_id' => $shortLedger, 'credit' => $diff, 'memo' => 'Cash excess']];

                $entry = app(Vouchers::class)->draft([
                    'type'             => 'journal',
                    'transaction_type' => TransactionType::CASH_ADJUSTMENT->value,
                    'date'             => today()->toDateString(),
                    'description'      => ($diff < 0 ? 'Cash shortage ' : 'Cash excess ') . Bdt::money(abs($diff)) . " — {$a['name']} count",
                    'party_label'      => $a['name'],
                    'money_account_id' => $a['id'],
                    'lines'            => $lines,
                ]);
            }

            CashCount::create([
                'account_id' => $a['id'], 'count_date' => today(), 'denominations' => $clean,
                'counted' => $counted, 'book' => $book, 'difference' => $diff,
                'adjustment_journal_entry_id' => $entry?->id, 'user_id' => auth()->id(),
            ]);
        });

        unset($this->accounts, $this->drafts);
        $this->dispatch('toast', type: 'success', message: abs($diff) < 0.5
            ? 'Cash count saved — মিলে গেছে।'
            : ($diff < 0 ? 'Shortage ' : 'Excess ') . Bdt::money(abs($diff)) . ' recorded as a draft voucher.');
    }

    #[Computed]
    public function lastCount(): ?CashCount
    {
        return $this->current ? CashCount::where('account_id', $this->current['id'])->latest('id')->first() : null;
    }

    /* ---------------- Reconcile ---------------- */

    /** Difference + the suggestion box (match a draft, bank interest, or a missing entry). */
    #[Computed]
    public function reconcile(): ?array
    {
        $a = $this->current;
        if (! $a || $a['kind'] === 'cash') {
            return null;
        }

        $stmt = $this->statement === '' ? null : (float) preg_replace('/[^\d.\-]/', '', $this->statement);
        $diff = $stmt === null ? null : round($stmt - $a['balance'], 2);

        $match = null;
        if ($diff !== null && abs($diff) >= 0.5) {
            $match = ($this->drafts[$a['id']] ?? collect())->groupBy('id')
                ->first(fn ($ls) => abs(((float) $ls->sum('debit') - (float) $ls->sum('credit')) - $diff) < 0.5);
        }

        [$text, $primary, $secondary, $action] = match (true) {
            $diff === null => ['Statement / app-এর balance লিখুন — মিলছে কিনা দেখাবে।', 'Mark reconciled', 'View statement', 'none'],
            abs($diff) < 0.5 => ['App / statement-এর balance আর software মিলে গেছে।', 'Mark reconciled', 'View statement', 'mark'],
            (bool) $match => ['Statement-এ ' . Bdt::money(abs($diff)) . ' ' . ($diff > 0 ? 'জমা' : 'খরচ') . ' আছে, কিন্তু ' . ($match->first()->voucher_ref ?: $match->first()->entry_number) . ' এখনো draft। Post করলে মিলে যাবে।', 'Match with ' . ($match->first()->voucher_ref ?: $match->first()->entry_number), 'Add bank entry', 'match'],
            $diff > 0 && $diff <= 2000 => [Bdt::money($diff) . ' বেশি — সম্ভবত bank interest / cash-back, এখনো entry হয়নি।', 'Add interest ' . Bdt::money($diff), 'Ignore', 'interest'],
            $diff < 0 && $diff >= -2000 => [Bdt::money(abs($diff)) . ' কম — সম্ভবত bank / MFS charge, এখনো entry হয়নি।', 'Add charge ' . Bdt::money(abs($diff)), 'Ignore', 'charge'],
            default => [Bdt::money(abs($diff)) . ' ' . ($diff > 0 ? 'বেশি' : 'কম') . ' — কোনো entry বাদ পড়েছে কিনা দেখুন।', 'Add bank entry', 'Ignore', 'entry'],
        };

        return [
            'stmt' => $stmt, 'diff' => $diff, 'text' => $text, 'primary' => $primary, 'secondary' => $secondary,
            'action' => $action, 'matchId' => $match?->first()->id, 'ok' => $diff !== null && abs($diff) < 0.5,
            'last' => $a['last_reconciled_at']?->format('d M Y') ?? 'Never',
        ];
    }

    public function reconcileAction(): void
    {
        AccountsAccess::authorize('cash');
        $r = $this->reconcile;
        $a = $this->current;

        match ($r['action'] ?? 'none') {
            'mark'     => $this->markReconciled(),
            'match'    => $this->redirectRoute('accounts.posting', ['v' => $r['matchId'], 'tab' => 'open'], navigate: true),
            'interest' => $this->quickEntry('receipt', '4100', $r['diff'], 'Bank interest / cash-back'),
            'charge'   => $this->quickEntry('payment', ExpenseCategories::CHARGES, abs($r['diff']), 'Bank / MFS charge'),
            'entry'    => $this->dispatch('voucher-form', type: $r['diff'] > 0 ? 'receipt' : 'payment', preset: ['account_id' => $a['id'], 'amount' => abs($r['diff'])]),
            default    => null,
        };
    }

    public function reconcileSecondary(): void
    {
        $r = $this->reconcile;
        $a = $this->current;

        if (in_array($r['action'] ?? 'none', ['none', 'mark'], true)) {
            $this->redirectRoute('accounts.transactions', ['account' => $a['id'], 'period' => 'month'], navigate: true);
        } elseif ($r['action'] === 'match') {
            $this->dispatch('voucher-form', type: $r['diff'] > 0 ? 'receipt' : 'payment', preset: ['account_id' => $a['id'], 'amount' => abs($r['diff'])]);
        } else {
            $this->statement = '';
        }
    }

    protected function markReconciled(): void
    {
        $a = $this->current;
        BankReconciliation::create([
            'account_id' => $a['id'], 'statement_date' => today(), 'statement_balance' => $this->reconcile['stmt'],
            'book_balance' => $a['balance'], 'status' => 'completed',
        ]);
        Account::whereKey($a['id'])->update(['last_reconciled_at' => today(), 'last_reconciled_balance' => $a['balance']]);

        unset($this->accounts);
        $this->statement = '';
        $this->dispatch('toast', type: 'success', message: "{$a['name']} reconciled.");
    }

    /** Draft receipt / payment on the selected account against $code (interest, charges…). */
    protected function quickEntry(string $type, string $code, float $amount, string $note): void
    {
        $a = $this->current;
        $ledger = app(LedgerAccounts::class)->require($code);
        $lines = $type === 'receipt'
            ? [['account_id' => $a['id'], 'debit' => $amount], ['account_id' => $ledger, 'credit' => $amount]]
            : [['account_id' => $ledger, 'debit' => $amount], ['account_id' => $a['id'], 'credit' => $amount]];

        $entry = app(Vouchers::class)->draft(['type' => $type, 'date' => today()->toDateString(), 'description' => "{$note} — {$a['name']}",
            'party_label' => $a['name'], 'money_account_id' => $a['id'], 'lines' => $lines]);

        unset($this->drafts);
        $this->dispatch('toast', type: 'success', message: "{$entry->ref()} created as draft — post it to match.");
    }

    /* ---------------- Transfer ---------------- */

    public function saveTransfer(bool $post = false): void
    {
        AccountsAccess::authorize('create');
        $this->validate([
            'transfer.from'   => 'required|integer|exists:accounts,id',
            'transfer.to'     => 'required|integer|exists:accounts,id|different:transfer.from',
            'transfer.amount' => 'required|numeric|min:1',
            'transfer.charge' => 'nullable|numeric|min:0',
            'transfer.date'   => 'required|date',
            'transfer.note'   => 'nullable|string|max:255',
        ], [], ['transfer.from' => 'from', 'transfer.to' => 'to', 'transfer.amount' => 'amount']);

        $t = $this->transfer;
        $amount = round((float) $t['amount'], 2);
        $charge = round((float) ($t['charge'] ?: 0), 2);
        $lines = [['account_id' => (int) $t['to'], 'debit' => $amount]];
        if ($charge > 0) {
            $lines[] = ['account_id' => app(LedgerAccounts::class)->require(ExpenseCategories::CHARGES), 'debit' => $charge, 'memo' => 'Transfer charge'];
        }
        $lines[] = ['account_id' => (int) $t['from'], 'credit' => round($amount + $charge, 2)];

        $v = app(Vouchers::class);
        try {
            $entry = $v->draft([
                'type' => 'contra', 'date' => $t['date'], 'description' => $t['note'] ?: 'Transfer',
                'party_label' => Account::whereKey($t['from'])->value('name') . ' → ' . Account::whereKey($t['to'])->value('name'),
                'money_account_id' => (int) $t['from'], 'lines' => $lines,
            ]);
            $message = "{$entry->ref()} saved as draft.";
            if ($post) {
                $v->post($entry);
                $message = "{$entry->ref()} — transferred.";
            }
        } catch (RuntimeException $e) {
            $message = isset($entry) ? "{$entry->ref()} saved as draft. Not posted: {$e->getMessage()}" : $e->getMessage();
            $this->dispatch('toast', type: isset($entry) ? 'warning' : 'error', message: $message);
            unset($this->accounts, $this->drafts);

            return;
        }

        $this->resetTransfer();
        unset($this->accounts, $this->drafts);
        $this->dispatch('toast', type: 'success', message: $message);
    }

    /* ---------------- New account ---------------- */

    public function createAccount(): void
    {
        AccountsAccess::authorize('settings');
        $this->validate([
            'newAccount.name'    => 'required|string|max:120|unique:accounts,name',
            'newAccount.kind'    => 'required|in:cash,bank,mobile_banking',
            'newAccount.number'  => 'nullable|string|max:60',
            'newAccount.opening' => 'nullable|numeric',
        ], [], ['newAccount.name' => 'account name']);

        $parent = Account::where('code', self::PARENTS[$this->newAccount['kind']])->firstOrFail();
        $code = (int) Account::where('parent_id', $parent->id)->max('code') ?: ((int) $parent->code * 10);
        do {
            $code++;
        } while (Account::withTrashed()->where('code', (string) $code)->exists());

        $account = Account::create([
            'code' => (string) $code, 'name' => trim($this->newAccount['name']), 'account_number' => $this->newAccount['number'] ?: null,
            'type' => $parent->type, 'subtype' => $parent->subtype, 'normal_balance' => $parent->normal_balance,
            'parent_id' => $parent->id, 'is_system' => false, 'is_active' => true, 'opening_balance' => (float) ($this->newAccount['opening'] ?: 0),
        ]);

        activity('accounts')->causedBy(auth()->user())->performedOn($account)->event('created')->log("Account \"{$account->name}\" was added under {$parent->name}");

        $this->showNew = false;
        $this->newAccount = ['name' => '', 'kind' => 'bank', 'number' => '', 'opening' => null];
        unset($this->accounts);
        $this->sel = $account->id;
        $this->dispatch('toast', type: 'success', message: "{$account->name} added.");
    }

    /* ---------------- Statement import ---------------- */

    /**
     * CSV / XLSX statement (date, description, amount — or debit / credit
     * columns) matched against the selected account's vouchers by amount
     * within ±3 days. Unmatched lines can be added as draft entries.
     */
    public function updatedImportFile(): void
    {
        AccountsAccess::authorize('cash');
        $this->validate(['importFile' => 'required|file|max:5120|mimes:csv,txt,xlsx'], [], ['importFile' => 'statement file']);

        $reader = strtolower($this->importFile->getClientOriginalExtension()) === 'xlsx' ? new XlsxReader() : new CsvReader();
        $reader->open($this->importFile->getRealPath());
        $rows = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = array_map(fn ($v) => $v instanceof \DateTimeInterface ? $v->format('Y-m-d') : trim((string) $v), $row->toArray());
            }
            break;
        }
        $reader->close();

        $head = array_map(fn ($h) => mb_strtolower($h), array_shift($rows) ?? []);
        $col = fn (array $names) => collect($head)->search(fn ($h) => collect($names)->contains(fn ($n) => str_contains($h, $n)));
        $iDate = $col(['date', 'তারিখ']);
        $iDesc = $col(['desc', 'particular', 'details', 'narration', 'remark']);
        $iAmt = $col(['amount']);
        $iDr = $col(['debit', 'withdraw', 'out']);
        $iCr = $col(['credit', 'deposit', 'in']);

        $a = $this->current;
        $lines = DB::table('journal_entry_lines as l')->join('journal_entries as je', 'je.id', '=', 'l.journal_entry_id')
            ->where('l.account_id', $a['id'])->whereIn('je.status', ['posted', 'draft', 'scheduled'])
            ->get(['je.id', 'je.voucher_ref', 'je.entry_number', 'je.entry_date', 'je.status', DB::raw('l.debit - l.credit as delta')]);

        $used = [];
        $out = collect($rows)->filter(fn ($r) => implode('', $r) !== '')->map(function ($r) use ($iDate, $iDesc, $iAmt, $iDr, $iCr, $lines, &$used) {
            $num = fn ($i) => $i === false ? 0.0 : (float) preg_replace('/[^\d.\-]/', '', (string) ($r[$i] ?? 0));
            $amount = $iAmt !== false ? $num($iAmt) : ($num($iCr) - $num($iDr));
            try {
                $date = CarbonImmutable::parse($r[$iDate] ?? '');
            } catch (\Throwable) {
                $date = null;
            }

            $hit = $lines->first(fn ($l) => ! in_array($l->id, $used, true) && abs((float) $l->delta - $amount) < 0.5
                && (! $date || abs(CarbonImmutable::parse($l->entry_date)->diffInDays($date)) <= 3));
            if ($hit) {
                $used[] = $hit->id;
            }

            return ['date' => $date?->toDateString(), 'desc' => $iDesc !== false ? ($r[$iDesc] ?? '') : '', 'amount' => $amount,
                'match' => $hit ? (($hit->voucher_ref ?: $hit->entry_number) . ($hit->status === 'posted' ? '' : ' (draft)')) : null, 'matchId' => $hit?->id];
        })->values()->all();

        $this->importResult = ['rows' => $out, 'matched' => collect($out)->whereNotNull('match')->count(), 'total' => count($out)];
        $this->importFile = null;
    }

    public function addFromStatement(int $i): void
    {
        $r = $this->importResult['rows'][$i] ?? null;
        if (! $r) {
            return;
        }

        $this->dispatch('voucher-form', type: $r['amount'] >= 0 ? 'receipt' : 'payment',
            preset: ['account_id' => $this->current['id'], 'amount' => abs($r['amount']), 'date' => $r['date'] ?: today()->toDateString(), 'note' => $r['desc']]);
    }

    public function render()
    {
        return view('livewire.accounts.cash-bank', [
            'money' => fn ($v, $signed = false) => Bdt::money($v, $signed),
        ])->layoutData(['active' => 'cash', 'heading' => 'Cash & Bank', 'crumb' => 'Accounts / Cash & Bank']);
    }
}
