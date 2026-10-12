<?php

namespace App\Livewire\Accounts;

use App\Enums\Accounts\JournalEntryStatus;
use App\Models\JournalEntry;
use App\Models\RecurringExpense;
use App\Services\Accounts\ExpenseCategories;
use App\Services\Accounts\Vouchers;
use App\Services\Profiles\LedgerAccounts;
use App\Support\AccountsAccess;
use App\Support\AccountsSettings;
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
use RuntimeException;

/**
 * Accounts Panel › Expenses — design: Expenses.dc.html.
 * Month view of expense categories vs budget (posted + draft), the expense
 * list, quick add (draft or post, receipt above the threshold, optional
 * monthly repeat) and recurring expenses.
 */
#[Title('Expenses')]
#[Layout('layouts.accounts.app')]
class Expenses extends Component
{
    use WithFileUploads;

    #[Url(except: '')]
    public string $month = '';

    #[Url(except: 0)]
    public int $cat = 0;

    public array $qa = [];

    public $receipt = null;

    public function mount(): void
    {
        AccountsAccess::authorize('view');
        if (! preg_match('/^\d{4}-\d{2}$/', $this->month)) {
            $this->month = '';
        }
        $this->resetQuick();
    }

    protected function resetQuick(): void
    {
        $this->qa = [
            'account_id' => ExpenseCategories::all()->first()?->id, 'amount' => null, 'date' => today()->toDateString(),
            'from' => $this->cashAccounts[0]['id'] ?? null, 'to' => '', 'note' => '', 'repeat' => false,
        ];
        $this->receipt = null;
    }

    #[On('voucher-saved')]
    public function refreshData(): void
    {
        unset($this->cats, $this->rows);
    }

    public function pickCat(int $id): void
    {
        $this->cat = $this->cat === $id ? 0 : $id;
    }

    public function start(): CarbonImmutable
    {
        return $this->month ? CarbonImmutable::createFromFormat('Y-m-d', $this->month . '-01')->startOfDay() : CarbonImmutable::today()->startOfMonth();
    }

    #[Computed]
    public function months(): array
    {
        $m = CarbonImmutable::today()->startOfMonth();

        return collect(range(0, 11))->mapWithKeys(fn ($i) => [$i === 0 ? '' : $m->subMonths($i)->format('Y-m') => $m->subMonths($i)->format('F Y')])->all();
    }

    #[Computed]
    public function categories(): Collection
    {
        return ExpenseCategories::all();
    }

    #[Computed]
    public function cashAccounts(): array
    {
        $v = app(Vouchers::class);

        return collect(app(LedgerAccounts::class)->cashAccounts())->map(fn ($a) => $a + ['balance' => $v->postedBalance($a['id'])])->all();
    }

    /** Net debit per category in a date range, split posted / draft. */
    protected function spend(string $from, string $to, ?array $ids = null): Collection
    {
        $ids ??= $this->categories->pluck('id')->all();

        return DB::table('journal_entry_lines as l')->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->whereIn('l.account_id', $ids ?: [0])->whereBetween('e.entry_date', [$from, $to])
            ->whereIn('e.status', [JournalEntryStatus::POSTED->value, JournalEntryStatus::DRAFT->value, JournalEntryStatus::SCHEDULED->value])
            ->groupBy('l.account_id')
            ->selectRaw("l.account_id, SUM(CASE WHEN e.status = 'posted' THEN l.debit - l.credit ELSE 0 END) as posted, SUM(CASE WHEN e.status <> 'posted' THEN l.debit - l.credit ELSE 0 END) as draft")
            ->get()->keyBy('account_id');
    }

    #[Computed]
    public function cats(): Collection
    {
        $s = $this->start();
        $spend = $this->spend($s->toDateString(), $s->endOfMonth()->toDateString());

        return $this->categories->map(function ($a) use ($spend) {
            $posted = (float) ($spend[$a->id]->posted ?? 0);
            $draft = (float) ($spend[$a->id]->draft ?? 0);
            $budget = (float) $a->monthly_budget;
            $used = $posted + $draft;

            return ['id' => $a->id, 'name' => $a->name, 'posted' => $posted, 'draft' => $draft, 'used' => $used, 'budget' => $budget,
                'pct' => $budget > 0 ? (int) round($used / $budget * 100) : 0, 'over' => $budget > 0 && $used > $budget + 0.004];
        })->filter(fn ($c) => $c['used'] != 0 || $c['budget'] > 0)
            ->sortByDesc(fn ($c) => [$c['over'] ? 1 : 0, $c['used']])->values();
    }

    #[Computed]
    public function summary(): array
    {
        $cats = $this->cats;
        $s = $this->start();
        $isNow = $s->isSameMonth(CarbonImmutable::today());
        $budget = $cats->sum('budget');
        $used = $cats->sum('used');
        $over = $cats->where('over', true);

        $trend = collect(range(5, 0))->map(function ($i) use ($s) {
            $m = $s->subMonths($i);
            $t = $this->spend($m->toDateString(), $m->endOfMonth()->toDateString());

            return ['label' => $m->format('M'), 'value' => (float) $t->sum('posted') + ($i === 0 ? (float) $t->sum('draft') : 0)];
        });
        $max = max(1, $trend->max('value'));

        return [
            'posted' => $cats->sum('posted'), 'draft' => $cats->sum('draft'), 'budget' => $budget,
            'pct' => $budget > 0 ? (int) round($used / $budget * 100) : 0,
            'days' => ($isNow ? CarbonImmutable::today()->day : $s->daysInMonth) . ' of ' . $s->daysInMonth . ' days',
            'over' => $over->count(), 'overNote' => $over->isEmpty() ? 'সব category budget-এর মধ্যে' : $over->pluck('name')->map(fn ($n) => explode(' ', $n)[0])->implode(', '),
            'last' => $trend[4]['value'], 'lastLabel' => $s->subMonth()->format('F'),
            'trend' => $trend->map(fn ($t, $i) => $t + ['h' => max(2, (int) round($t['value'] / $max * 26)), 'now' => $i === 5])->all(),
        ];
    }

    #[Computed]
    public function rows(): Collection
    {
        $s = $this->start();
        $ids = $this->cat ? [$this->cat] : $this->categories->pluck('id')->all();
        $names = $this->categories->pluck('name', 'id');

        return JournalEntry::with(['lines.account:id,name', 'moneyAccount:id,name', 'profile:id,display_name'])->withCount('attachments')
            ->whereBetween('entry_date', [$s->toDateString(), $s->endOfMonth()->toDateString()])
            ->whereIn('status', [JournalEntryStatus::POSTED->value, JournalEntryStatus::DRAFT->value, JournalEntryStatus::SCHEDULED->value])
            ->whereHas('lines', fn ($q) => $q->whereIn('account_id', $ids ?: [0])->where('debit', '>', 0))
            ->orderByDesc('entry_date')->orderByDesc('id')->limit(300)->get()
            ->map(function (JournalEntry $e) use ($ids, $names) {
                $exp = $e->lines->filter(fn ($l) => in_array($l->account_id, $ids) && (float) $l->debit > 0);
                $credit = $e->lines->first(fn ($l) => (float) $l->credit > 0);
                $amount = (float) $exp->sum('debit');

                return [
                    'id' => $e->id, 'date' => $e->entry_date->format('d M'), 'ref' => $e->ref(), 'text' => $e->description ?: '—',
                    'cat' => $exp->map(fn ($l) => $names[$l->account_id] ?? $l->account?->name)->unique()->implode(', '),
                    'to' => $e->party_label ?: ($e->profile?->display_name ?? '—'),
                    'acc' => $e->moneyAccount?->name ?? $credit?->account?->name ?? '—',
                    'receipt' => $e->attachments_count > 0, 'amount' => $amount,
                    'status' => $e->status, 'posted' => $e->status === JournalEntryStatus::POSTED,
                ];
            });
    }

    #[Computed]
    public function recurring(): Collection
    {
        $drafts = JournalEntry::where('source_type', RecurringExpense::class)->where('status', JournalEntryStatus::DRAFT->value)->pluck('source_id')->all();

        return RecurringExpense::with('fromAccount:id,name')->where('is_active', true)->orderBy('next_run_date')->get()
            ->map(fn ($r) => ['r' => $r, 'draft' => in_array($r->id, $drafts)]);
    }

    /** Receipt is required above the category's own limit, else the Settings threshold. */
    #[Computed]
    public function receiptLimit(): float
    {
        $cat = $this->categories->firstWhere('id', (int) ($this->qa['account_id'] ?? 0));

        return (float) ($cat?->receipt_required_above ?: AccountsSettings::attachmentThreshold());
    }

    public function save(bool $post = false): void
    {
        AccountsAccess::authorize('create');
        $limit = $this->receiptLimit;
        $this->validate([
            'qa.account_id' => 'required|integer|exists:accounts,id', 'qa.amount' => 'required|numeric|min:1', 'qa.date' => 'required|date',
            'qa.from' => 'required|integer|exists:accounts,id', 'qa.to' => 'nullable|string|max:120', 'qa.note' => 'nullable|string|max:200',
            'receipt' => [$limit > 0 && (float) $this->qa['amount'] > $limit ? 'required' : 'nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf'],
        ], ['receipt.required' => Bdt::money($limit) . '-এর বেশি খরচে receipt লাগবে।'], [
            'qa.account_id' => 'category', 'qa.amount' => 'amount', 'qa.from' => 'paid from',
        ]);

        $v = app(Vouchers::class);
        $amount = round((float) $this->qa['amount'], 2);
        $cat = $this->categories->firstWhere('id', (int) $this->qa['account_id']);

        $entry = $v->draft([
            'type' => 'expense', 'date' => $this->qa['date'], 'description' => $this->qa['note'] ?: $cat?->name,
            'party_label' => $this->qa['to'] ?: null, 'money_account_id' => (int) $this->qa['from'],
            'lines' => [['account_id' => (int) $this->qa['account_id'], 'debit' => $amount], ['account_id' => (int) $this->qa['from'], 'credit' => $amount]],
        ]);
        if ($this->receipt) {
            $v->attach($entry, $this->receipt);
        }

        if ($this->qa['repeat']) {
            $d = CarbonImmutable::parse($this->qa['date']);
            $day = min(28, $d->day);
            RecurringExpense::create([
                'name' => $this->qa['note'] ?: $cat?->name, 'payee' => $this->qa['to'] ?: null, 'account_id' => (int) $this->qa['account_id'],
                'from_account_id' => (int) $this->qa['from'], 'amount' => $amount, 'cadence' => 'monthly', 'day_of_month' => $day,
                'next_run_date' => $d->startOfMonth()->addMonth()->day($day)->toDateString(), 'is_active' => true,
            ]);
        }

        $message = "{$entry->ref()} saved as draft.";
        $type = 'success';
        if ($post) {
            try {
                $v->post($entry->fresh());
                $message = "{$entry->ref()} posted.";
            } catch (RuntimeException $e) {
                $message = "{$entry->ref()} saved as draft. Not posted: {$e->getMessage()}";
                $type = 'warning';
            }
        }

        $repeat = (bool) $this->qa['repeat'];
        $this->resetQuick();
        unset($this->cats, $this->rows, $this->recurring, $this->summary);
        $this->dispatch('toast', type: $type, message: $message . ($repeat ? ' প্রতি মাসে repeat হবে।' : ''));
    }

    public function render()
    {
        return view('livewire.accounts.expenses', [
            'money' => fn ($v) => Bdt::money($v),
        ])->layoutData(['active' => 'expenses', 'heading' => 'Expenses', 'crumb' => 'Accounts / Expenses']);
    }
}
