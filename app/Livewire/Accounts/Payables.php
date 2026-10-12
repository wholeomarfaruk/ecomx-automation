<?php

namespace App\Livewire\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\AccountsSupplierBill;
use App\Models\Installment;
use App\Models\LoanRepaymentSchedule;
use App\Models\MasterProfile;
use App\Models\PayrollItem;
use App\Models\RecurringExpense;
use App\Services\Accounts\ExpenseCategories;
use App\Services\Accounts\PayablesSchedule;
use App\Services\Accounts\Vouchers;
use App\Services\Profiles\LedgerAccounts;
use App\Support\AccountsAccess;
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
use RuntimeException;

/**
 * Accounts Panel › Payables — design: Payables.dc.html.
 * Every payable for the next 30 days (+ overdue) in one list, a 30-day
 * cash plan, bulk "Create payments" (drafts), reschedule, supplier aging
 * and recurring bills.
 */
#[Title('Payables')]
#[Layout('layouts.accounts.app')]
class Payables extends Component
{
    public const WHEN = ['all' => 'All · 30 days', 'overdue' => 'Overdue', 'week' => 'This week', 'next' => 'Next week', 'later' => 'Later'];

    public const SOURCES = ['all' => 'All sources', 'supplier' => 'Suppliers', 'loan' => 'Loan / EMI', 'salary' => 'Salary', 'bill' => 'Bills'];

    #[Url(except: 'all')]
    public string $when = 'all';

    #[Url(except: 'all')]
    public string $src = 'all';

    public ?int $payFrom = null;

    public ?string $payKey = null;
    public array $pay = [];

    public ?string $rescheduleKey = null;
    public ?string $rescheduleDate = null;

    public bool $showBill = false;
    public array $bill = [];

    public bool $showRecurring = false;
    public array $recurring = [];

    public function mount(): void
    {
        AccountsAccess::authorize('view');
        $this->payFrom = $this->cashAccounts[0]['id'] ?? null;
    }

    #[On('voucher-saved')]
    public function refreshData(): void
    {
        unset($this->all);
    }

    #[Computed]
    public function all(): Collection
    {
        return app(PayablesSchedule::class)->rows()
            ->map(fn ($r) => $r + ['when' => PayablesSchedule::when($r['due'])]);
    }

    #[Computed]
    public function rows(): Collection
    {
        return $this->all->filter(fn ($r) => ($this->when === 'all' || $r['when'] === $this->when) && ($this->src === 'all' || $r['src'] === $this->src))->values();
    }

    #[Computed]
    public function cashAccounts(): array
    {
        $v = app(Vouchers::class);

        return collect(app(LedgerAccounts::class)->cashAccounts())->map(fn ($a) => $a + ['balance' => $v->postedBalance($a['id'])])->sortByDesc('balance')->values()->all();
    }

    #[Computed]
    public function cards(): array
    {
        $all = $this->all;
        $group = fn ($w) => $all->where('when', $w);
        $weekEnd = \App\Support\AccountsPeriod::weekStart(CarbonImmutable::today())->addDays(6);
        $overdue = $group('overdue');
        $later = $group('later');

        return [
            'overdue' => [$overdue->sum('amount'), $overdue->isEmpty() ? 'কিছু বাকি পড়ে নেই' : $overdue->first()['payee'] . ' · ' . PayablesSchedule::note($overdue->first()['due']) . ($overdue->count() > 1 ? ' + ' . ($overdue->count() - 1) . ' more' : '')],
            'week'    => [$group('week')->sum('amount'), $group('week')->count() . ' payments', 'by ' . $weekEnd->format('d M')],
            'next'    => [$group('next')->sum('amount'), $group('next')->count() . ' payments', $weekEnd->addDay()->format('d') . '–' . $weekEnd->addDays(7)->format('d M')],
            'later'   => [$later->sum('amount'), $later->sortByDesc('amount')->first()['for'] ?? '—', 'to ' . CarbonImmutable::today()->addDays(30)->format('d M')],
        ];
    }

    /** 30-day cash plan: payable vs money on hand + expected inflow, and the tightest day. */
    #[Computed]
    public function plan(): array
    {
        $payable = $this->all->sum('amount');
        $cash = collect($this->cashAccounts)->sum('balance');
        $v = app(Vouchers::class);
        $until = CarbonImmutable::today()->addDays(30)->toDateString();

        $cod = \App\Models\Account::where('subtype', 'courier')->pluck('id')->sum(fn ($id) => $v->postedBalance($id));
        $promised = (float) \App\Models\PromiseToPay::where('kind', 'promise')->where('status', 'open')->where('due_date', '<=', $until)->sum('amount');
        $kisti = (float) Installment::whereHas('plan', fn ($q) => $q->where('role', 'customer')->where('status', 'active'))
            ->where('status', '!=', 'paid')->where('due_date', '<=', $until)->sum(DB::raw('adjusted_amount - paid_amount'));
        $inflow = $cod + $promised + $kisti;

        $tight = $this->all->where('when', '!=', 'overdue')->groupBy(fn ($r) => $r['due']->toDateString())->sortByDesc(fn ($g) => $g->sum('amount'))->first();
        $biggest = collect($this->cashAccounts)->first();
        $tightText = $tight
            ? 'সবচেয়ে টাইট দিন: ' . $tight->first()['due']->format('d M') . ' — ' . $tight->pluck('payee')->unique()->take(3)->implode(' + ') . ' একসাথে ' . Bdt::money($tight->sum('amount')) . '।'
                . ($biggest ? " {$biggest['name']}-এ " . Bdt::money($biggest['balance']) . ' আছে।' : '')
            : 'আগামী ৩০ দিনে বড় কোনো payment নেই।';

        return ['payable' => $payable, 'cash' => $cash, 'inflow' => $inflow, 'left' => $cash + $inflow - $payable, 'tight' => $tightText];
    }

    /** Supplier aging: not due / due within 7 days / overdue. */
    #[Computed]
    public function aging(): array
    {
        $s = $this->all->where('src', 'supplier');
        $soon = CarbonImmutable::today()->addDays(7);
        $a = [
            'notdue'  => $s->filter(fn ($r) => $r['due']->gt($soon))->sum('amount'),
            'soon'    => $s->filter(fn ($r) => ! $r['due']->lt(CarbonImmutable::today()) && $r['due']->lte($soon))->sum('amount'),
            'overdue' => $s->filter(fn ($r) => $r['due']->lt(CarbonImmutable::today()))->sum('amount'),
        ];
        $total = max(1, array_sum($a));

        return $a + ['pct' => array_map(fn ($v) => round($v / $total * 100, 1), $a)];
    }

    #[Computed]
    public function recurringBills(): Collection
    {
        return RecurringExpense::with('account:id,name')->where('is_active', true)->orderBy('next_run_date')->get();
    }

    public function setWhen(string $w): void
    {
        $this->when = array_key_exists($w, self::WHEN) ? $w : 'all';
    }

    public function setSrc(string $s): void
    {
        $this->src = array_key_exists($s, self::SOURCES) ? $s : 'all';
    }

    /* ---------------- payments ---------------- */

    /** Bulk "Create payments": one draft per selected row, paid from the chosen account. */
    public function createPayments(array $keys): void
    {
        AccountsAccess::authorize('create');
        $made = [];
        $skipped = [];

        foreach ($keys as $key) {
            $row = $this->all->firstWhere('key', $key);
            if (! $row) {
                continue;
            }
            try {
                $made[] = $this->draftFor($row, (int) $this->payFrom, $row['amount'], today()->toDateString())->ref();
            } catch (RuntimeException $e) {
                $skipped[] = "{$row['payee']} ({$e->getMessage()})";
            }
        }

        unset($this->all);
        $this->dispatch('clear-selection');
        $this->dispatch('toast', type: $skipped ? 'warning' : 'success',
            message: count($made) . ' payment draft(s) created: ' . implode(', ', $made) . ($skipped ? '. Skipped: ' . implode('; ', $skipped) : '') . '. Post them from Posting Pending.');
    }

    public function openPay(string $key): void
    {
        $row = $this->all->firstWhere('key', $key);
        abort_unless($row, 404);
        $this->payKey = $key;
        $this->pay = ['account_id' => $this->payFrom, 'amount' => $row['amount'], 'date' => today()->toDateString()];
    }

    public function savePay(bool $post = false): void
    {
        AccountsAccess::authorize('create');
        $this->validate(['pay.account_id' => 'required|integer|exists:accounts,id', 'pay.amount' => 'required|numeric|min:1', 'pay.date' => 'required|date'], [], ['pay.amount' => 'amount', 'pay.account_id' => 'pay from']);
        $row = $this->all->firstWhere('key', $this->payKey);

        try {
            $entry = $this->draftFor($row, (int) $this->pay['account_id'], round((float) $this->pay['amount'], 2), $this->pay['date']);
            $message = "{$entry->ref()} created as draft.";
            if ($post) {
                app(Vouchers::class)->post($entry);
                $message = "{$entry->ref()} paid.";
            }
        } catch (RuntimeException $e) {
            $message = isset($entry) ? "{$entry->ref()} saved as draft. Not posted: {$e->getMessage()}" : null;
            if (! $message) {
                $this->addError('pay.amount', $e->getMessage());

                return;
            }
        }

        $this->payKey = null;
        unset($this->all);
        $this->dispatch('toast', type: str_contains($message, 'Not posted') ? 'warning' : 'success', message: $message);
    }

    /** Build the payment draft that settles one payable row. */
    protected function draftFor(array $row, int $accountId, float $amount, string $date): \App\Models\JournalEntry
    {
        if ($row['draft']) {
            throw new RuntimeException("draft {$row['draft']} already waiting");
        }

        $v = app(Vouchers::class);
        $ledgers = app(LedgerAccounts::class);
        $ref = $row['ref'];
        $profile = $row['profile_id'] ? MasterProfile::find($row['profile_id']) : null;

        return match ($ref['type']) {
            'bill', 'kisti', 'balance' => $v->draft([
                'type' => 'payment', 'date' => $date, 'description' => "{$row['for']} — {$row['payee']}", 'profile_id' => $profile?->id,
                'money_account_id' => $accountId,
                'lines' => [
                    ['account_id' => $ledgers->require(LedgerAccounts::PAYABLE), 'debit' => $amount] + $v->partyFields($ledgers->require(LedgerAccounts::PAYABLE), $profile),
                    ['account_id' => $accountId, 'credit' => $amount],
                ],
            ]),
            'emi' => $this->emiDraft(LoanRepaymentSchedule::with('loan')->findOrFail($ref['id']), $accountId, $amount, $date),
            'salary' => $v->draft([
                'type' => 'salary', 'date' => $date, 'description' => "{$row['for']} — {$row['payee']}", 'profile_id' => $profile?->id,
                'money_account_id' => $accountId, 'source_type' => PayrollItem::class, 'source_id' => $ref['id'],
                'lines' => [
                    ['account_id' => $ledgers->require(LedgerAccounts::SALARY_PAYABLE), 'debit' => $amount, 'subledger_type' => MasterProfile::class, 'subledger_id' => $profile?->id],
                    ['account_id' => $accountId, 'credit' => $amount],
                ],
            ]),
            'recurring' => app(PayablesSchedule::class)->draftRecurring(RecurringExpense::findOrFail($ref['id']), $accountId, $amount),
            default => throw new RuntimeException('prepare the payroll first (Employees › Prepare payroll)'),
        };
    }

    /** EMI payment draft: principal to the loan, interest to Interest expense; posting settles the kisti. */
    protected function emiDraft(LoanRepaymentSchedule $s, int $accountId, float $amount, string $date): \App\Models\JournalEntry
    {
        $interestLeft = max(0, round((float) $s->interest_due - (float) $s->paid_amount, 2));
        $interest = round(min($interestLeft, $amount), 2);
        $principal = round($amount - $interest, 2);
        $ledgers = app(LedgerAccounts::class);

        $lines = [];
        if ($principal > 0) {
            $lines[] = ['account_id' => $s->loan->loan_payable_account_id, 'debit' => $principal];
        }
        if ($interest > 0) {
            $lines[] = ['account_id' => $ledgers->require(LedgerAccounts::INTEREST_EXPENSE), 'debit' => $interest];
        }
        $lines[] = ['account_id' => $accountId, 'credit' => $amount];

        return app(Vouchers::class)->draft([
            'type' => 'emi', 'date' => $date, 'description' => 'Kisti ' . ($s->installment_no ?? '') . ' — ' . $s->loan->name,
            'profile_id' => $s->loan->master_profile_id, 'money_account_id' => $accountId,
            'source_type' => LoanRepaymentSchedule::class, 'source_id' => $s->id,
            'meta' => ['principal' => $principal, 'interest' => $interest], 'lines' => $lines,
        ]);
    }

    /* ---------------- reschedule ---------------- */

    public function openReschedule(string $key): void
    {
        $row = $this->all->firstWhere('key', $key);
        abort_unless($row, 404);
        $this->rescheduleKey = $key;
        $this->rescheduleDate = $row['due']->toDateString();
    }

    public function reschedule(): void
    {
        AccountsAccess::authorize('create');
        $this->validate(['rescheduleDate' => 'required|date'], [], ['rescheduleDate' => 'date']);
        $row = $this->all->firstWhere('key', $this->rescheduleKey);
        $d = $this->rescheduleDate;

        match ($row['ref']['type']) {
            'bill'      => AccountsSupplierBill::whereKey($row['ref']['id'])->update(['due_date' => $d]),
            'kisti'     => Installment::whereKey($row['ref']['id'])->update(['due_date' => $d]),
            'emi'       => LoanRepaymentSchedule::whereKey($row['ref']['id'])->update(['due_date' => $d]),
            'salary'    => PayrollItem::find($row['ref']['id'])?->run?->update(['due_on' => $d]),
            'recurring' => RecurringExpense::whereKey($row['ref']['id'])->update(['next_run_date' => $d]),
            default     => null,
        };

        if ($row['profile_id']) {
            MasterProfile::find($row['profile_id'])?->logActivity('setting', "{$row['for']} rescheduled to " . CarbonImmutable::parse($d)->format('d M Y'));
        }

        $this->rescheduleKey = null;
        unset($this->all);
        $this->dispatch('toast', type: 'success', message: 'Rescheduled to ' . CarbonImmutable::parse($d)->format('d M') . '.');
    }

    /* ---------------- + Bill ---------------- */

    #[Computed]
    public function supplierOptions(): array
    {
        return MasterProfile::withRole('supplier')->orderBy('display_name')->limit(1000)->pluck('display_name', 'id')->all();
    }

    #[Computed]
    public function billLedgers(): array
    {
        $inv = app(LedgerAccounts::class)->id('1200');

        return ($inv ? [$inv => 'Inventory · মাল কেনা'] : []) + ExpenseCategories::all()->pluck('name', 'id')->all();
    }

    public function openBill(): void
    {
        $this->bill = ['profile_id' => null, 'ledger_id' => array_key_first($this->billLedgers), 'amount' => null, 'date' => today()->toDateString(), 'due' => today()->addDays(15)->toDateString(), 'note' => ''];
        $this->showBill = true;
    }

    /** Draft journal Dr inventory / expense, Cr payable (supplier); posting opens the bill with its due date. */
    public function saveBill(): void
    {
        AccountsAccess::authorize('create');
        $this->validate([
            'bill.profile_id' => 'required|integer|exists:master_profiles,id', 'bill.ledger_id' => 'required|integer|exists:accounts,id',
            'bill.amount' => 'required|numeric|min:1', 'bill.date' => 'required|date', 'bill.due' => 'required|date|after_or_equal:bill.date',
            'bill.note' => 'nullable|string|max:200',
        ], [], ['bill.profile_id' => 'supplier', 'bill.amount' => 'amount', 'bill.due' => 'due date']);

        $v = app(Vouchers::class);
        $p = MasterProfile::findOrFail($this->bill['profile_id']);
        $ap = app(LedgerAccounts::class)->require(LedgerAccounts::PAYABLE);
        $amount = round((float) $this->bill['amount'], 2);

        $entry = $v->draft([
            'type' => 'journal', 'transaction_type' => TransactionType::SUPPLIER_BILL->value, 'date' => $this->bill['date'],
            'description' => $this->bill['note'] ?: "Bill — {$p->display_name}", 'profile_id' => $p->id,
            'meta' => ['supplier_bill' => true, 'due_date' => $this->bill['due']],
            'lines' => [
                ['account_id' => (int) $this->bill['ledger_id'], 'debit' => $amount],
                ['account_id' => $ap, 'credit' => $amount] + $v->partyFields($ap, $p),
            ],
        ]);

        $this->showBill = false;
        $this->dispatch('toast', type: 'success', message: "{$entry->ref()} created as draft — it shows here once posted.");
    }

    /* ---------------- recurring ---------------- */

    public function openRecurring(): void
    {
        $this->recurring = ['name' => '', 'payee' => '', 'account_id' => ExpenseCategories::all()->first()?->id, 'amount' => null, 'varies' => false, 'day' => 10, 'from' => $this->payFrom];
        $this->showRecurring = true;
    }

    public function saveRecurring(): void
    {
        AccountsAccess::authorize('create');
        $this->validate([
            'recurring.name' => 'required|string|max:120', 'recurring.payee' => 'nullable|string|max:120',
            'recurring.account_id' => 'required|integer|exists:accounts,id', 'recurring.amount' => 'required|numeric|min:1',
            'recurring.day' => 'required|integer|min:1|max:28', 'recurring.from' => 'required|integer|exists:accounts,id',
        ], [], ['recurring.name' => 'name', 'recurring.amount' => 'amount', 'recurring.day' => 'day of month']);

        $day = (int) $this->recurring['day'];
        $next = today()->day <= $day ? today()->day($day) : today()->addMonthNoOverflow()->day($day);

        RecurringExpense::create([
            'name' => $this->recurring['name'], 'payee' => $this->recurring['payee'] ?: null, 'account_id' => (int) $this->recurring['account_id'],
            'from_account_id' => (int) $this->recurring['from'], 'amount' => (float) $this->recurring['amount'], 'varies' => (bool) $this->recurring['varies'],
            'cadence' => 'monthly', 'day_of_month' => $day, 'next_run_date' => $next->toDateString(), 'is_active' => true,
        ]);

        $this->showRecurring = false;
        unset($this->recurringBills, $this->all);
        $this->dispatch('toast', type: 'success', message: 'Recurring bill added — a draft is created on its day each month.');
    }

    public function stopRecurring(int $id): void
    {
        AccountsAccess::authorize('create');
        RecurringExpense::whereKey($id)->update(['is_active' => false]);
        unset($this->recurringBills, $this->all);
    }

    public function render()
    {
        return view('livewire.accounts.payables', [
            'money' => fn ($v) => Bdt::money($v),
        ])->layoutData(['active' => 'payables', 'heading' => 'Payables', 'crumb' => 'Accounts / Payables']);
    }
}
