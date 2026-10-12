<?php

namespace App\Livewire\Accounts;

use App\Actions\Accounts\CalculateStockValue;
use App\Enums\Accounts\AccountType;
use App\Enums\Accounts\JournalEntryStatus;
use App\Enums\Accounts\TransactionType;
use App\Exceptions\Accounts\LockedFiscalPeriodException;
use App\Models\Account;
use App\Models\AccountsSupplierBill;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\Loan;
use App\Models\LoanRepaymentSchedule;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RecurringExpense;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Accounts Panel home (হিসাবের সারাংশ) — /accounts.
 *
 * Figures come from the ledger (journal_entry_lines), the open-item tables
 * (customer invoices / supplier bills), loans and inventory, the same
 * sources as Admin > Accounts > Dashboard. A few things on this page have
 * no backing feature yet and stay as sample values, marked "SAMPLE" below:
 * support advance, owner count, stock in transit, and customer / supplier /
 * landlord installment rules (only loan EMIs exist).
 *
 * Speed: everything is aggregated in SQL (no per-row queries, no loading
 * whole tables into PHP). Figures that don't depend on the period filter
 * are persisted for 60s so switching Today / Week / Month only re-runs the
 * period queries. The EMI frequency filter and voucher checkboxes are
 * client-side (Alpine) and never round-trip to the server.
 */
#[Title('Accounts Home')]
#[Layout('layouts.accounts.app')]
class Dashboard extends Component
{
    public const PERIODS = [
        'today'     => 'Today',
        'yesterday' => 'Yesterday',
        'week'      => 'This week',
        'month'     => 'This month',
        'year'      => 'This year',
        'custom'    => 'Custom',
    ];

    public const FREQUENCIES = ['all', 'monthly', 'weekly', 'daily', 'yearly', 'custom'];

    protected const CASH_SUBTYPES = ['cash', 'bank', 'mobile_banking'];

    #[Url(as: 'period')]
    public string $period = 'today';

    #[Url]
    public ?string $from = null;

    #[Url]
    public ?string $to = null;

    /* ------------------------------------------------------------------
     |  Actions
     * ------------------------------------------------------------------ */

    public function setPeriod(string $period): void
    {
        if (! array_key_exists($period, self::PERIODS)) {
            return;
        }

        $this->period = $period;

        if ($period === 'custom' && (! $this->from || ! $this->to)) {
            $this->from = now()->startOfMonth()->toDateString();
            $this->to   = now()->toDateString();
        }
    }

    public function applyCustom(): void
    {
        $this->validate([
            'from' => ['required', 'date'],
            'to'   => ['required', 'date', 'after_or_equal:from'],
        ]);

        $this->period = 'custom';
    }

    public function postVoucher(int $id): void
    {
        $entry = JournalEntry::where('status', JournalEntryStatus::DRAFT)->find($id);

        if (! $entry) {
            return;
        }

        try {
            $this->postDraft($entry);
        } catch (\RuntimeException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        }

        session()->now('accounts.status', "Voucher {$entry->entry_number} posted.");
    }

    /** @param  list<int|string>  $ids  checkbox selection, held client-side in Alpine */
    public function postSelected(array $ids): void
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if (empty($ids)) {
            return;
        }

        $posted  = 0;
        $skipped = [];

        JournalEntry::where('status', JournalEntryStatus::DRAFT)
            ->whereIn('id', $ids)
            ->get()
            ->each(function (JournalEntry $entry) use (&$posted, &$skipped) {
                try {
                    $this->postDraft($entry);
                    $posted++;
                } catch (\RuntimeException) {
                    $skipped[] = $entry->entry_number;
                }
            });

        $message = "{$posted} voucher(s) posted.";
        if ($skipped) {
            $message .= ' Skipped: ' . implode(', ', $skipped) . '.';
        }

        session()->now('accounts.status', $message);
    }

    /**
     * Draft → posted. Same guards PostJournalEntry applies when an entry is
     * posted directly: lines must balance and the period must be open.
     */
    protected function postDraft(JournalEntry $entry): void
    {
        $entry->loadMissing('lines');

        if (abs($entry->totalDebit() - $entry->totalCredit()) > 0.01 || $entry->lines->count() < 2) {
            throw new \RuntimeException("{$entry->entry_number} is not balanced.");
        }

        $period = FiscalPeriod::query()->containingDate($entry->entry_date->toDateString())->first();

        if ($period?->is_locked) {
            throw LockedFiscalPeriodException::forDate($entry->entry_date->toDateString());
        }

        DB::transaction(function () use ($entry, $period) {
            // Re-read under a row lock so two people posting the same draft can't both succeed.
            $stillDraft = JournalEntry::whereKey($entry->id)->lockForUpdate()->value('status') === JournalEntryStatus::DRAFT;

            if (! $stillDraft) {
                throw new \RuntimeException("{$entry->entry_number} was already posted.");
            }

            $entry->update([
                'status'           => JournalEntryStatus::POSTED,
                'fiscal_period_id' => $entry->fiscal_period_id ?? $period?->id,
                'posted_by'        => auth()->id(),
                'posted_at'        => now(),
            ]);

            activity('accounts')
                ->causedBy(auth()->user())
                ->performedOn($entry)
                ->event('posted')
                ->log("Journal entry {$entry->entry_number} was posted from the Accounts Panel");
        });
    }

    /* ------------------------------------------------------------------
     |  Date range
     * ------------------------------------------------------------------ */

    /** @return array{0: Carbon, 1: Carbon} */
    #[Computed]
    public function range(): array
    {
        $now = now();

        return match ($this->period) {
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'week'      => [$now->copy()->startOfWeek(Carbon::SATURDAY), $now->copy()->endOfDay()], // BD week starts Saturday
            'month'     => [$now->copy()->startOfMonth(), $now->copy()->endOfDay()],
            'year'      => [$now->copy()->startOfYear(), $now->copy()->endOfDay()],
            'custom'    => [
                Carbon::parse($this->from ?? $now->copy()->startOfMonth())->startOfDay(),
                Carbon::parse($this->to ?? $now)->endOfDay(),
            ],
            default     => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
        };
    }

    #[Computed]
    public function rangeLabel(): string
    {
        [$start, $end] = $this->range;

        return $start->isSameDay($end)
            ? $start->format('d M Y')
            : $start->format('d M') . ' – ' . $end->format('d M Y');
    }

    /** entry_date is a DATE column — compare on date strings. */
    protected function rangeDates(): array
    {
        [$start, $end] = $this->range;

        return [$start->toDateString(), $end->toDateString()];
    }

    /* ------------------------------------------------------------------
     |  Snapshots — not tied to the period filter, persisted for 60s.
     |  Each returns a plain array built from a few SQL aggregates.
     * ------------------------------------------------------------------ */

    /** Cash / bank / mobile-banking balances + owner capital & this month's drawings — one ledger query. */
    #[Computed(persist: true, seconds: 60)]
    public function balances(): array
    {
        $accounts = Account::query()
            ->where(fn ($q) => $q->where('is_active', true)->whereIn('subtype', self::CASH_SUBTYPES))
            ->orWhereIn('code', ['3000', '3100'])
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'subtype', 'normal_balance', 'is_active']);

        $totals = DB::table('journal_entry_lines as l')
            ->join('journal_entries as je', 'je.id', '=', 'l.journal_entry_id')
            ->where('je.status', '!=', JournalEntryStatus::VOID->value)
            ->whereIn('l.account_id', $accounts->pluck('id'))
            ->groupBy('l.account_id')
            ->selectRaw('l.account_id,
                COALESCE(SUM(l.debit), 0) as dr,
                COALESCE(SUM(l.credit), 0) as cr,
                COALESCE(SUM(CASE WHEN je.entry_date >= ? THEN l.debit ELSE 0 END), 0) as dr_month', [now()->startOfMonth()->toDateString()])
            ->get()
            ->keyBy('account_id');

        // Same rule as Account::balance(), without a query per account.
        $balance = fn (Account $a) => $a->normal_balance->signedDelta(
            (float) ($totals[$a->id]->dr ?? 0),
            (float) ($totals[$a->id]->cr ?? 0),
        );

        $isCash   = fn (Account $a) => $a->is_active && in_array($a->subtype, self::CASH_SUBTYPES, true);
        $capital  = $accounts->firstWhere('code', '3000');
        $drawings = $accounts->firstWhere('code', '3100');

        return [
            'cash_accounts'  => $accounts->filter($isCash)
                ->map(fn ($a) => ['name' => $a->name, 'subtype' => $a->subtype, 'balance' => $balance($a)])
                ->values()
                ->all(),
            'cash_ids'       => $accounts->filter($isCash)->pluck('id')->values()->all(),
            'capital'        => $capital ? $balance($capital) : 0,
            'drawings_month' => $drawings ? (float) ($totals[$drawings->id]->dr_month ?? 0) : 0,
        ];
    }

    /** Open customer invoices: total, 60+ days, and aging buckets — one query. */
    #[Computed(persist: true, seconds: 60)]
    public function receivables(): array
    {
        $due = '(amount - amount_allocated)';

        $row = DB::table('accounts_customer_invoices')
            ->whereIn('status', ['open', 'partial'])
            ->selectRaw("
                COALESCE(SUM({$due}), 0) as total,
                COALESCE(SUM(CASE WHEN created_at < ? THEN {$due} ELSE 0 END), 0) as over_60,
                COALESCE(SUM(CASE WHEN created_at > ? THEN {$due} ELSE 0 END), 0) as b30,
                COALESCE(SUM(CASE WHEN created_at <= ? AND created_at > ? THEN {$due} ELSE 0 END), 0) as b60,
                COALESCE(SUM(CASE WHEN created_at <= ? AND created_at > ? THEN {$due} ELSE 0 END), 0) as b90,
                COALESCE(SUM(CASE WHEN created_at <= ? THEN {$due} ELSE 0 END), 0) as b90plus", [
                now()->subDays(60),
                now()->subDays(31),
                now()->subDays(31), now()->subDays(61),
                now()->subDays(61), now()->subDays(91),
                now()->subDays(91),
            ])
            ->first();

        return [
            'total'   => (float) $row->total,
            'over_60' => (float) $row->over_60,
            'buckets' => [(float) $row->b30, (float) $row->b60, (float) $row->b90, (float) $row->b90plus],
        ];
    }

    /** Open supplier bills: total + the 4 largest. */
    #[Computed(persist: true, seconds: 60)]
    public function supplierBills(): array
    {
        $open = fn () => AccountsSupplierBill::whereIn('status', ['open', 'partial']);

        return [
            'total' => (float) $open()->sum(DB::raw('amount - amount_allocated')),
            'top'   => $open()
                ->with('supplier:id,name')
                ->orderByRaw('(amount - amount_allocated) desc')
                ->limit(4)
                ->get()
                ->map(fn ($b) => [
                    'party'  => $b->supplier?->name ?? 'Supplier',
                    'date'   => $b->created_at?->format('d M'),
                    'amount' => $b->amountDue(),
                ])
                ->all(),
        ];
    }

    /** Active loans, EMIs due within 7 days, the next pending EMIs, and the next one this week. */
    #[Computed(persist: true, seconds: 60)]
    public function loans(): array
    {
        $loans = Loan::where('status', 'active')
            ->withSum('repayments', 'principal_amount')
            ->get(['id', 'name', 'lender', 'principal']);

        $pending = fn () => LoanRepaymentSchedule::whereIn('status', ['pending', 'overdue']);
        $row = fn (LoanRepaymentSchedule $s) => [
            'party'  => $this->lenderName($s->loan),
            'due'    => $s->due_date->toDateString(),
            'amount' => $this->emiAmount($s),
        ];

        $today = now()->toDateString();
        $week  = now()->addDays(7)->toDateString();
        $next  = $pending()->with('loan:id,name,lender')->whereBetween('due_date', [$today, $week])->orderBy('due_date')->first();

        return [
            'outstanding'  => $loans->sum(fn ($l) => round((float) $l->principal - (float) $l->repayments_sum_principal_amount, 2)),
            'lender_count' => $loans->map(fn ($l) => $this->lenderName($l))->unique()->count(),
            'emi_7_days'   => (float) $pending()->where('due_date', '<=', $week)->sum(DB::raw('principal_due + interest_due')),
            'upcoming'     => $pending()->with('loan:id,name,lender')->orderBy('due_date')->limit(4)->get()->map($row)->all(),
            'next_week'    => $next ? $row($next) : null,
        ];
    }

    /** On-hand stock totals and value (cost + sale). */
    #[Computed(persist: true, seconds: 60)]
    public function stockSnapshot(): array
    {
        if (! $this->inventoryEnabled()) {
            // Inventory module off: stock lives on products/variants.stock_quantity.
            $sql = 'COALESCE(SUM(stock_quantity), 0) as qty,
                COALESCE(SUM(stock_quantity * COALESCE(purchase_price, 0)), 0) as cost,
                COALESCE(SUM(stock_quantity * COALESCE(NULLIF(sale_price, 0), price, 0)), 0) as sale';

            $simple = Product::where('product_type', 'simple')->where('stock_quantity', '>', 0)
                ->toBase()->selectRaw($sql . ', COUNT(*) as products')->first();
            $variants = ProductVariant::where('stock_quantity', '>', 0)
                ->toBase()->selectRaw($sql . ', COUNT(DISTINCT product_id) as products')->first();

            return [
                'total_qty'     => (float) $simple->qty + (float) $variants->qty,
                'product_count' => (int) $simple->products + (int) $variants->products,
                'value_cost'    => (float) $simple->cost + (float) $variants->cost,
                'value_sale'    => (float) $simple->sale + (float) $variants->sale,
            ];
        }

        $stocks = DB::table('inventory_stocks as s')
            ->leftJoin('product_variants as v', fn ($j) => $j->on('v.id', '=', 's.variant_id')->whereNull('v.deleted_at'))
            ->leftJoin('products as p', fn ($j) => $j->on('p.id', '=', 's.product_id')->whereNull('p.deleted_at'))
            ->where('s.quantity', '>', 0)
            ->selectRaw('COALESCE(SUM(s.quantity), 0) as qty,
                COUNT(DISTINCT s.product_id) as products,
                COALESCE(SUM(s.quantity * COALESCE(NULLIF(v.sale_price, 0), v.price, NULLIF(p.sale_price, 0), p.price, 0)), 0) as sale_value')
            ->first();

        return [
            'total_qty'     => (float) $stocks->qty,
            'product_count' => (int) $stocks->products,
            'value_cost'    => app(CalculateStockValue::class)->handle(),
            'value_sale'    => (float) $stocks->sale_value,
        ];
    }

    #[Computed(persist: true, seconds: 60)]
    public function profileCounts(): array
    {
        $roles = \App\Services\Profiles\ProfileCounts::all();

        return [
            'customers' => $roles['customers'],
            'suppliers' => $roles['suppliers'],
            'owners'    => $roles['owners'],
            'logins'    => DB::table('panel_user')
                ->join('panels', 'panels.id', '=', 'panel_user.panel_id')
                ->where('panels.slug', 'accounts')
                ->count(),
        ];
    }

    /** Admin pages are linked only for users who also hold the admin panel. */
    #[Computed]
    public function hasAdminPanel(): bool
    {
        return auth()->user()?->hasPanel('admin') ?? false;
    }

    protected function inventoryEnabled(): bool
    {
        return (bool) Setting::get('inventory_enabled', false, 'modules');
    }

    protected function lenderName(?Loan $loan): string
    {
        return $loan?->lender ?: ($loan?->name ?? '—');
    }

    protected function emiAmount(LoanRepaymentSchedule $s): float
    {
        return (float) $s->principal_due + (float) $s->interest_due;
    }

    /* ------------------------------------------------------------------
     |  Data shaped for the view
     * ------------------------------------------------------------------ */

    #[Computed]
    public function summary(): array
    {
        [$start, $end] = $this->rangeDates();

        $flow = DB::table('journal_entry_lines as l')
            ->join('journal_entries as je', 'je.id', '=', 'l.journal_entry_id')
            ->whereIn('l.account_id', $this->balances['cash_ids'])
            ->where('je.status', '!=', JournalEntryStatus::VOID->value)
            ->whereBetween('je.entry_date', [$start, $end])
            ->selectRaw('
                COALESCE(SUM(l.debit), 0) as cash_in,
                COALESCE(SUM(l.credit), 0) as cash_out,
                COUNT(DISTINCT CASE WHEN l.debit > 0 THEN je.id END) as receipts,
                COUNT(DISTINCT CASE WHEN l.credit > 0 THEN je.id END) as payments,
                COALESCE(SUM(CASE WHEN je.transaction_type = ? THEN l.debit ELSE 0 END), 0) as cod_in,
                COUNT(DISTINCT CASE WHEN l.credit > 0 AND je.transaction_type = ? THEN je.id END) as emi_count', [
                TransactionType::COD_SETTLEMENT->value,
                TransactionType::LOAN_REPAYMENT->value,
            ])
            ->first();

        $cash = collect($this->balances['cash_accounts']);
        $bank = $cash->where('subtype', 'bank');
        $mfs  = $cash->where('subtype', 'mobile_banking');

        return [
            'cash_in'            => (float) $flow->cash_in,
            'cash_in_note'       => $flow->receipts . ' receipts · COD ' . $this->money((float) $flow->cod_in),
            'cash_out'           => (float) $flow->cash_out,
            'cash_out_note'      => $flow->payments . ' payments · ' . $flow->emi_count . ' EMI',
            'cash_on_hand'       => (float) $cash->where('subtype', 'cash')->sum('balance'),
            'bank_mfs'           => (float) $bank->sum('balance') + (float) $mfs->sum('balance'),
            'bank_mfs_note'      => collect([$bank->count() . ' banks'])->merge($mfs->pluck('name'))->implode(' · '),
            'receivable'         => $this->receivables['total'],
            'receivable_overdue' => $this->receivables['over_60'], // 60+ days since invoiced (no due-date column yet)
            'payable'            => $this->supplierBills['total'],
            'payable_7_days'     => $this->loans['emi_7_days'],    // loan EMIs — supplier bills carry no due date
            'loan_outstanding'   => $this->loans['outstanding'],
            'lender_count'       => $this->loans['lender_count'],
            'support_advance'    => 1500,                          // SAMPLE — no support-advance feature yet
            'profit'             => $this->postedProfit($start, $end),
        ];
    }

    /** Income − expense from posted entries in range (contra lines net out naturally). */
    protected function postedProfit(string $start, string $end): float
    {
        $row = DB::table('journal_entry_lines as l')
            ->join('journal_entries as je', 'je.id', '=', 'l.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'l.account_id')
            ->where('je.status', JournalEntryStatus::POSTED->value)
            ->whereBetween('je.entry_date', [$start, $end])
            ->selectRaw('
                COALESCE(SUM(CASE WHEN a.type = ? THEN l.credit - l.debit ELSE 0 END), 0) as income,
                COALESCE(SUM(CASE WHEN a.type = ? THEN l.debit - l.credit ELSE 0 END), 0) as expense', [
                AccountType::INCOME->value,
                AccountType::EXPENSE->value,
            ])
            ->first();

        return (float) $row->income - (float) $row->expense;
    }

    #[Computed]
    public function stock(): array
    {
        [$start, $end] = $this->range;
        $snapshot  = $this->stockSnapshot;
        $inventory = $this->inventoryEnabled();

        $cogs = (float) DB::table('journal_entry_lines as l')
            ->join('journal_entries as je', 'je.id', '=', 'l.journal_entry_id')
            ->where('je.status', '!=', JournalEntryStatus::VOID->value)
            ->where('je.transaction_type', TransactionType::COGS->value)
            ->whereBetween('je.entry_date', [$start->toDateString(), $end->toDateString()])
            ->sum('l.debit');

        $movements = ! $inventory ? collect() : DB::table('inventory_stock_movements as m')
            ->leftJoin('inventory_batches as b', 'b.id', '=', 'm.batch_id')
            ->leftJoin('product_variants as v', 'v.id', '=', 'm.variant_id')
            ->leftJoin('products as p', 'p.id', '=', 'm.product_id')
            ->whereBetween('m.created_at', [$start, $end])
            ->groupBy('m.type')
            ->selectRaw('m.type,
                SUM(CASE WHEN m.quantity > 0 THEN m.quantity ELSE 0 END) as qty_in,
                SUM(CASE WHEN m.quantity < 0 THEN -m.quantity ELSE 0 END) as qty_out,
                SUM(CASE WHEN m.quantity > 0 THEN m.quantity * COALESCE(b.purchase_price, v.purchase_price, p.purchase_price, 0) ELSE 0 END) as cost_in')
            ->get();

        $note = fn (string $col) => ! $inventory
            ? 'inventory module off'
            : ($movements->filter(fn ($m) => $m->{$col} > 0)
                ->map(fn ($m) => str_replace('_', ' ', $m->type) . ' ' . number_format((float) $m->{$col}))
                ->implode(' · ') ?: 'no movement');

        return [
            'in_qty'        => (float) $movements->sum('qty_in'),
            'in_cost'       => (float) $movements->sum('cost_in'),
            'in_note'       => $note('qty_in'),
            'out_qty'       => (float) $movements->sum('qty_out'),
            'out_cogs'      => $cogs,
            'out_note'      => $note('qty_out'),
            'total_qty'     => $snapshot['total_qty'],
            'product_count' => $snapshot['product_count'],
            'in_transit'    => 45, // SAMPLE — no in-transit tracking yet
            'value_cost'    => $snapshot['value_cost'],
            'value_sale'    => $snapshot['value_sale'],
        ];
    }

    /** Draft journal entries — not counted in profit until posted. */
    #[Computed]
    public function pendingVouchers(): array
    {
        return JournalEntry::where('status', JournalEntryStatus::DRAFT)
            ->with(['lines:id,journal_entry_id,debit,subledger_type,subledger_id', 'lines.subledger', 'createdBy:id,name'])
            ->latest('id')
            ->limit(50)
            ->get(['id', 'entry_number', 'transaction_type', 'description', 'created_by'])
            ->map(function (JournalEntry $e) {
                $party = $e->lines->map->subledger->filter()->first();

                return [
                    'id'     => $e->id,
                    'ref'    => $e->entry_number,
                    'type'   => $e->transaction_type?->label() ?? 'Journal',
                    'detail' => $e->description ?: '—',
                    'party'  => $party?->full_name ?? $party?->name ?? '—',
                    'amount' => $e->totalDebit(),
                    'by'     => $e->createdBy?->name ?? 'System',
                ];
            })
            ->all();
    }

    #[Computed]
    public function masters(): array
    {
        $s = $this->summary;
        $c = $this->profileCounts;

        return [
            ['route' => 'accounts.profiles.customers', 'label' => 'Customers', 'count' => $c['customers'],    'sub' => 'পাবো (due)',     'amount' => $s['receivable'],       'initial' => 'C', 'tone' => 'green'],
            ['route' => 'accounts.profiles.suppliers', 'label' => 'Suppliers', 'count' => $c['suppliers'],    'sub' => 'দিবো (payable)', 'amount' => $s['payable'],          'initial' => 'S', 'tone' => 'red'],
            ['route' => 'accounts.profiles.lenders',   'label' => 'Lenders',   'count' => $s['lender_count'], 'sub' => 'ঋণ বাকি',        'amount' => $s['loan_outstanding'], 'initial' => 'L', 'tone' => 'indigo'],
            ['route' => 'accounts.profiles.owners',    'label' => 'Owners',    'count' => $c['owners'], 'sub' => 'Capital · drawings ' . $this->money($this->balances['drawings_month']) . ' this month', 'amount' => $this->balances['capital'], 'initial' => 'O', 'tone' => 'amber'],
            ['route' => 'admin.users', 'label' => 'Login Profiles', 'count' => $c['logins'], 'sub' => $c['logins'] . ' can post vouchers', 'amount' => null, 'initial' => 'U', 'tone' => 'gray'],
        ];
    }

    /** All installments: loan EMIs from the system + SAMPLE rows for rule types that don't exist yet. Filtered by frequency client-side. */
    #[Computed]
    public function allInstallments(): array
    {
        [$start, $end] = $this->range;

        $schedules = LoanRepaymentSchedule::with(['loan:id,name,lender,term_months', 'repayment'])
            ->where(fn ($q) => $q
                ->where(fn ($q) => $q->whereIn('status', ['pending', 'overdue'])->where('due_date', '<=', now()->addDays(30)->toDateString()))
                ->orWhere(fn ($q) => $q->where('status', 'paid')->whereHas('repayment', fn ($r) => $r->whereBetween('paid_at', [$start, $end]))))
            ->orderBy('due_date')
            ->limit(30)
            ->get();

        // Every schedule date of the loans shown — gives "kisti 5 / 12" without a query per row.
        $datesByLoan = LoanRepaymentSchedule::whereIn('loan_id', $schedules->pluck('loan_id')->unique())
            ->orderBy('due_date')
            ->get(['loan_id', 'due_date'])
            ->groupBy('loan_id');

        $loanRows = $schedules->map(function (LoanRepaymentSchedule $s) use ($datesByLoan) {
            $dates  = $datesByLoan->get($s->loan_id, collect());
            $number = $dates->filter(fn ($d) => $d->due_date->lte($s->due_date))->count();
            $total  = $s->loan?->term_months ?: $dates->count();
            $emi    = $this->emiAmount($s);
            $paid   = $s->repayment ? (float) $s->repayment->principal_amount + (float) $s->repayment->interest_amount : null;

            [$status, $label] = match (true) {
                $s->status === 'paid'                            => ['paid', 'Paid'],
                $s->due_date->lt(now()->startOfDay())            => ['overdue', 'Overdue ' . $this->money($emi - (float) $paid)],
                $s->due_date->lte(now()->addDays(3)->endOfDay()) => ['due', 'Due'],
                default                                          => ['upcoming', 'Upcoming'],
            };

            return [
                'party'        => $this->lenderName($s->loan),
                'role'         => 'Lender · আমি দিবো',
                'frequency'    => 'monthly',
                'rule'         => 'Monthly',
                'no'           => "{$number} / {$total}",
                'due'          => $s->due_date->isToday() ? 'Today' : $s->due_date->format('d M'),
                'emi'          => $emi,
                'paid'         => $paid,
                'support'      => null,
                'status'       => $status,
                'status_label' => $label,
            ];
        })->all();

        // SAMPLE — customer / supplier / landlord installment rules aren't a feature yet.
        $sampleRows = [
            ['party' => 'Karim Enterprise',  'role' => 'Customer · আমি পাবো', 'frequency' => 'weekly', 'rule' => 'Weekly',             'no' => '3 / 8',   'due' => '13 Oct', 'emi' => 6500,   'paid' => null,  'support' => null, 'status' => 'due',      'status_label' => 'Due'],
            ['party' => 'Nasrin Akter',      'role' => 'Customer · আমি পাবো', 'frequency' => 'daily',  'rule' => 'Daily',              'no' => '18 / 60', 'due' => 'Today',  'emi' => 500,    'paid' => 500,   'support' => null, 'status' => 'paid',     'status_label' => 'Paid'],
            ['party' => 'Shop rent advance', 'role' => 'Landlord · আমি দিবো', 'frequency' => 'yearly', 'rule' => 'Yearly',             'no' => '2 / 3',   'due' => '01 Jan', 'emi' => 120000, 'paid' => null,  'support' => null, 'status' => 'upcoming', 'status_label' => 'Upcoming'],
            ['party' => 'Jamal Hossain',     'role' => 'Supplier · আমি দিবো', 'frequency' => 'custom', 'rule' => 'Custom · 15/45/90d', 'no' => '2 / 3',   'due' => '10 Oct', 'emi' => 40000,  'paid' => 25000, 'support' => null, 'status' => 'overdue',  'status_label' => 'Overdue ৳15,000'],
        ];

        return array_merge($loanRows, $sampleRows);
    }

    #[Computed]
    public function freqCounts(): array
    {
        $counts = ['all' => count($this->allInstallments)];

        foreach (array_slice(self::FREQUENCIES, 1) as $f) {
            $counts[$f] = count(array_filter($this->allInstallments, fn ($r) => $r['frequency'] === $f));
        }

        return $counts;
    }

    #[Computed]
    public function aging(): array
    {
        $labels  = ['0–30 দিন', '31–60 দিন', '61–90 দিন', '90+ দিন'];
        $colors  = ['bg-emerald-500', 'bg-amber-500', 'bg-orange-500', 'bg-red-600'];
        $amounts = $this->receivables['buckets'];
        $total   = max(array_sum($amounts), 1);

        return array_map(fn ($i) => [
            'label'  => $labels[$i],
            'amount' => $amounts[$i],
            'color'  => $colors[$i],
            'pct'    => round($amounts[$i] / $total * 100),
        ], array_keys($labels));
    }

    /** Overdue / next loan EMIs and the largest open supplier bills. */
    #[Computed]
    public function payables(): array
    {
        $today = now()->startOfDay();

        $emis = collect($this->loans['upcoming'])->map(function ($e) use ($today) {
            $due = Carbon::parse($e['due']);
            $overdueDays = $due->lt($today) ? (int) $due->diffInDays($today) : 0;

            return [
                'party'   => $e['party'],
                'when'    => $overdueDays ? "{$overdueDays} day" . ($overdueDays > 1 ? 's' : '') . ' overdue' : 'EMI · ' . $due->format('d M'),
                'overdue' => $overdueDays > 0,
                'amount'  => $e['amount'],
                'sort'    => $due->timestamp,
            ];
        });

        $bills = collect($this->supplierBills['top'])->map(fn ($b) => [
            'party'   => $b['party'],
            'when'    => 'Bill · ' . $b['date'],
            'overdue' => false,
            'amount'  => $b['amount'],
            'sort'    => PHP_INT_MAX,
        ]);

        return $emis->concat($bills)
            ->sortBy([['overdue', 'desc'], ['sort', 'asc']])
            ->take(4)
            ->values()
            ->all();
    }

    #[Computed]
    public function accounts(): array
    {
        $kinds = ['cash' => 'Cash', 'bank' => 'Bank', 'mobile_banking' => 'Mobile banking'];

        return array_map(fn ($a) => [
            'name'   => $a['name'],
            'kind'   => $kinds[$a['subtype']] ?? ucfirst((string) $a['subtype']),
            'amount' => $a['balance'],
        ], $this->balances['cash_accounts']);
    }

    /** Entries in range that moved cash / bank / mobile banking. amount: + in, − out. */
    #[Computed]
    public function transactions(): array
    {
        [$start, $end] = $this->rangeDates();
        $cashIds    = $this->balances['cash_ids'];
        $timeFormat = $start === $end ? 'g:i A' : 'd M, g:i A';

        return JournalEntry::query()
            ->where('status', '!=', JournalEntryStatus::VOID)
            ->whereBetween('entry_date', [$start, $end])
            ->whereHas('lines', fn ($q) => $q->whereIn('account_id', $cashIds))
            ->with(['lines' => fn ($q) => $q->whereIn('account_id', $cashIds)->with('account:id,name')])
            ->latest('id')
            ->limit(10)
            ->get(['id', 'entry_number', 'description', 'transaction_type', 'status', 'created_at'])
            ->map(fn (JournalEntry $e) => [
                'time'    => $e->created_at?->format($timeFormat),
                'title'   => $e->description ?: ($e->transaction_type?->label() ?? $e->entry_number),
                'account' => $e->lines->first()?->account?->name ?? '—',
                'posted'  => $e->status === JournalEntryStatus::POSTED,
                'amount'  => (float) $e->lines->sum('debit') - (float) $e->lines->sum('credit'),
            ])
            ->all();
    }

    #[Computed]
    public function alerts(): array
    {
        $alerts = [];
        $today  = now()->startOfDay();

        foreach (collect($this->loans['upcoming'])->filter(fn ($e) => Carbon::parse($e['due'])->lt($today))->take(2) as $e) {
            $alerts[] = [
                'title' => $e['party'] . ' — কিস্তি বাকি',
                'text'  => $this->money($e['amount']) . ' overdue since ' . Carbon::parse($e['due'])->format('d M'),
                'tone'  => 'red',
            ];
        }

        if ($drafts = JournalEntry::where('status', JournalEntryStatus::DRAFT)->count()) {
            $alerts[] = ['title' => "{$drafts} vouchers waiting to post", 'text' => 'Not counted in profit until posted', 'tone' => 'amber'];
        }

        if ($next = $this->loans['next_week']) {
            $days = (int) $today->diffInDays(Carbon::parse($next['due']));
            $alerts[] = [
                'title' => $next['party'] . ' EMI ' . ($days === 0 ? 'today' : "in {$days} days"),
                'text'  => $this->money($next['amount']) . ' · bank balance ' . ($this->summary['bank_mfs'] >= $next['amount'] ? 'enough' : 'low'),
                'tone'  => 'amber',
            ];
        }

        foreach (RecurringExpense::due()->limit(2)->get(['name', 'amount', 'cadence']) as $r) {
            $alerts[] = ['title' => "{$r->name} due", 'text' => $this->money((float) $r->amount) . ' · recurring ' . $r->cadence, 'tone' => 'amber'];
        }

        if (($overdue = $this->summary['receivable_overdue']) > 0) {
            $alerts[] = ['title' => 'Old customer due', 'text' => $this->money($overdue) . ' unpaid for 60+ days', 'tone' => 'red'];
        }

        return array_slice($alerts, 0, 5);
    }

    /* ------------------------------------------------------------------
     |  Helpers
     * ------------------------------------------------------------------ */

    /** ৳ with Bangladeshi grouping: 1775250 → ৳17,75,250 */
    public function money(int|float|null $amount, bool $signed = false): string
    {
        if ($amount === null) {
            return '—';
        }

        $negative = $amount < 0;
        $digits   = (string) (int) round(abs($amount));

        if (strlen($digits) > 3) {
            $last3  = substr($digits, -3);
            $rest   = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', substr($digits, 0, -3));
            $digits = $rest . ',' . $last3;
        }

        $prefix = $negative ? '− ' : ($signed ? '+ ' : '');

        return $prefix . '৳' . $digits;
    }

    public function render()
    {
        return view('livewire.accounts.dashboard');
    }
}
