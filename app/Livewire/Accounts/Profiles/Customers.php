<?php

namespace App\Livewire\Accounts\Profiles;

use App\Actions\Profiles\SendProfileSms;
use App\Enums\Accounts\JournalEntryStatus;
use App\Enums\Accounts\TransactionType;
use App\Livewire\Accounts\Profiles\Concerns\HandlesVoucherModals;
use App\Models\City;
use App\Models\Customer;
use App\Models\InstallmentPlan;
use App\Models\MasterProfile;
use App\Models\MasterProfileRole;
use App\Services\Profiles\LedgerAccounts;
use App\Services\Profiles\ProfileBalances;
use App\Support\Bdt;
use App\Support\BdPhone;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Accounts Panel › Customers — design: Customers.dc.html.
 * Due (receivable) per customer from the posted ledger, age from the
 * oldest open invoice, credit-limit usage, kisti plan and last payment.
 */
#[Title('Customers')]
#[Layout('layouts.accounts.app')]
class Customers extends Component
{
    use HandlesVoucherModals, WithPagination;

    public const PER_PAGE = 25;

    public const CHIPS = ['all' => 'All', 'due' => 'Due', 'overdue' => 'Overdue 60+', 'adv' => 'Advance', 'clear' => 'Clear'];

    public const SORTS = ['due' => 'Highest due', 'oldest' => 'Oldest due', 'recent' => 'Recent activity', 'name' => 'Name A–Z'];

    #[Url(except: '')]
    public string $q = '';

    #[Url(except: '')]
    public string $district = '';

    #[Url(except: 'due')]
    public string $sort = 'due';

    #[Url(except: false)]
    public bool $kisti = false;

    #[Url(except: 'all')]
    public string $st = 'all';

    public function updated($name): void
    {
        if (in_array($name, ['q', 'district', 'sort', 'kisti'], true)) {
            $this->resetPage();
        }
    }

    public function setSt(string $st): void
    {
        $this->st = array_key_exists($st, self::CHIPS) ? $st : 'all';
        $this->resetPage();
    }

    /** Oldest open invoice and the 60+ day part of the due, per profile. */
    protected function invoiceAges()
    {
        return DB::table('accounts_customer_invoices as i')
            ->join('customers as c', 'c.id', '=', 'i.customer_id')
            ->whereIn('i.status', ['open', 'partial'])
            ->whereNotNull('c.master_profile_id')
            ->groupBy('c.master_profile_id')
            ->selectRaw('c.master_profile_id as profile_id, MIN(i.created_at) as oldest,
                COALESCE(SUM(CASE WHEN i.created_at <= ? THEN i.amount - i.amount_allocated ELSE 0 END), 0) as over60', [now()->subDays(60)]);
    }

    protected function base(): Builder
    {
        return MasterProfile::query()
            ->withRole('customer')
            ->leftJoinSub(app(ProfileBalances::class)->query(), 'pb', 'pb.profile_id', '=', 'master_profiles.id')
            ->leftJoinSub($this->invoiceAges(), 'inv', 'inv.profile_id', '=', 'master_profiles.id');
    }

    protected function stSql(string $st): ?string
    {
        $due = 'COALESCE(pb.receivable, 0)';
        $adv = 'COALESCE(pb.customer_advance, 0)';
        $cut = DB::escape(now()->subDays(60)->toDateTimeString());

        return match ($st) {
            'due'     => "{$due} > 0.5 AND (inv.oldest IS NULL OR inv.oldest > {$cut})",
            'overdue' => "{$due} > 0.5 AND inv.oldest <= {$cut}",
            'adv'     => "{$due} <= 0.5 AND {$adv} > 0.5",
            'clear'   => "{$due} <= 0.5 AND {$adv} <= 0.5",
            default   => null,
        };
    }

    protected function filtered(): Builder
    {
        $query = $this->base()
            ->select('master_profiles.*')
            ->selectRaw('COALESCE(pb.receivable, 0) as due, COALESCE(pb.customer_advance, 0) as advance, pb.last_at, inv.oldest');

        if ($this->q !== '') {
            $q = trim($this->q);
            $phone = BdPhone::normalize($q);
            $query->where(fn ($w) => $w->where('master_profiles.display_name', 'like', "%{$q}%")
                ->orWhere('master_profiles.code', 'like', "%{$q}%")
                ->when($phone && strlen($phone) >= 4, fn ($w) => $w->orWhere('master_profiles.phone', 'like', '%' . ltrim($phone, '0') . '%')));
        }

        if ($this->district !== '') {
            $query->where('master_profiles.city_id', (int) $this->district);
        }

        if ($this->kisti) {
            $query->whereHas('installmentPlans', fn ($p) => $p->where('role', 'customer')->where('status', 'active'));
        }

        if ($sql = $this->stSql($this->st)) {
            $query->whereRaw($sql);
        }

        return match ($this->sort) {
            'oldest' => $query->orderByRaw('CASE WHEN inv.oldest IS NULL THEN 1 ELSE 0 END')->orderBy('inv.oldest'),
            'recent' => $query->orderByRaw('COALESCE(pb.last_at, master_profiles.updated_at) DESC'),
            'name'   => $query->orderBy('master_profiles.display_name'),
            default  => $query->orderByRaw('COALESCE(pb.receivable, 0) DESC')->orderByDesc('master_profiles.id'),
        };
    }

    #[Computed]
    public function rows()
    {
        $page = $this->filtered()->with(['roles', 'city:id,name', 'policeStation:id,name'])->paginate(self::PER_PAGE);
        $ids = $page->pluck('id')->all();

        $purchases = $ids ? DB::table('orders as o')->join('customers as c', 'c.id', '=', 'o.customer_id')
            ->whereIn('c.master_profile_id', $ids)
            ->whereNotIn('o.status', ['cancelled', 'returned', 'refunded'])
            ->groupBy('c.master_profile_id')
            ->selectRaw('c.master_profile_id as pid, COALESCE(SUM(o.total_amount), 0) as total, COUNT(*) as orders')
            ->get()->keyBy('pid') : collect();

        $plans = $ids ? InstallmentPlan::whereIn('master_profile_id', $ids)->where('role', 'customer')->where('status', 'active')
            ->withCount(['installments as paid_count' => fn ($q) => $q->where('status', 'paid')])
            ->get()->keyBy('master_profile_id') : collect();

        $lastPay = $this->lastPayments($ids);

        $page->getCollection()->transform(function (MasterProfile $p) use ($purchases, $plans, $lastPay) {
            $p->setAttribute('purchase', $purchases->get($p->id));
            $p->setAttribute('plan', $plans->get($p->id));
            $p->setAttribute('last_payment', $lastPay[$p->id] ?? null);

            return $p;
        });

        return $page;
    }

    /** Latest posted receipt per profile: [profile_id => ['amount', 'date']]. */
    protected function lastPayments(array $ids): array
    {
        if (! $ids) {
            return [];
        }

        $ar = app(LedgerAccounts::class)->id(LedgerAccounts::RECEIVABLE);

        return DB::table('journal_entry_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('customers as c', fn ($j) => $j->on('c.id', '=', 'jl.subledger_id')->where('jl.subledger_type', Customer::class))
            ->whereIn('c.master_profile_id', $ids)
            ->where('jl.account_id', $ar)
            ->where('jl.credit', '>', 0)
            ->where('je.status', JournalEntryStatus::POSTED->value)
            ->where('je.transaction_type', TransactionType::PAYMENT_RECEIPT->value)
            ->orderByDesc('je.entry_date')->orderByDesc('je.id')
            ->get(['c.master_profile_id as pid', 'jl.credit', 'je.entry_date'])
            ->unique('pid')
            ->mapWithKeys(fn ($r) => [$r->pid => ['amount' => (float) $r->credit, 'date' => Carbon::parse($r->entry_date)]])
            ->all();
    }

    #[Computed]
    public function chipCounts(): array
    {
        $select = collect(array_keys(self::CHIPS))->map(fn ($st) => $st === 'all'
            ? 'COUNT(*) as c_all'
            : "COALESCE(SUM(CASE WHEN {$this->stSql($st)} THEN 1 ELSE 0 END), 0) as c_{$st}")->implode(', ');

        $row = $this->base()->toBase()->selectRaw($select)->first();

        return collect(array_keys(self::CHIPS))->mapWithKeys(fn ($st) => [$st => (int) $row->{"c_{$st}"}])->all();
    }

    #[Computed]
    public function summary(): array
    {
        $row = $this->base()->toBase()->selectRaw('
            COALESCE(SUM(CASE WHEN pb.receivable > 0 THEN pb.receivable ELSE 0 END), 0) as due,
            COALESCE(SUM(CASE WHEN pb.receivable > 0.5 THEN 1 ELSE 0 END), 0) as due_count,
            COALESCE(SUM(CASE WHEN inv.over60 > 0.5 THEN inv.over60 ELSE 0 END), 0) as over60,
            COALESCE(SUM(CASE WHEN inv.over60 > 0.5 THEN 1 ELSE 0 END), 0) as over60_count,
            COALESCE(SUM(CASE WHEN pb.customer_advance > 0 THEN pb.customer_advance ELSE 0 END), 0) as advance')->first();

        $ar = app(LedgerAccounts::class)->id(LedgerAccounts::RECEIVABLE);
        $collected = (float) DB::table('journal_entry_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->where('jl.account_id', $ar)
            ->where('je.status', JournalEntryStatus::POSTED->value)
            ->where('je.transaction_type', TransactionType::PAYMENT_RECEIPT->value)
            ->where('je.entry_date', '>=', now()->startOfMonth()->toDateString())
            ->sum('jl.credit');

        return [
            'count'        => $this->chipCounts['all'],
            'due_count'    => (int) $row->due_count,
            'kisti_count'  => InstallmentPlan::where('role', 'customer')->where('status', 'active')->distinct('master_profile_id')->count('master_profile_id'),
            'due'          => (float) $row->due,
            'collected'    => $collected,
            'over60'       => (float) $row->over60,
            'over60_count' => (int) $row->over60_count,
            'over_limit'   => $this->overLimitCount(),
            'advance'      => (float) $row->advance,
        ];
    }

    /** Customers whose due is at or above their credit limit (new credit sales blocked). */
    protected function overLimitCount(): int
    {
        $limits = MasterProfileRole::where('role', 'customer')->whereNotNull('settings')->get(['master_profile_id', 'settings'])
            ->mapWithKeys(fn ($r) => [$r->master_profile_id => (float) ($r->settings['credit_limit'] ?? 0)])
            ->filter(fn ($l) => $l > 0);

        if ($limits->isEmpty()) {
            return 0;
        }

        $due = app(ProfileBalances::class)->forProfiles($limits->keys()->all());

        return $limits->filter(fn ($limit, $id) => (float) $due[$id]->receivable >= $limit)->count();
    }

    #[Computed(persist: true, seconds: 3600)]
    public function districts(): array
    {
        return City::query()->whereIn('id', MasterProfile::withRole('customer')->whereNotNull('city_id')->select('city_id'))
            ->orderBy('name')->pluck('name', 'id')->all();
    }

    /** Due reminder SMS to the selected customers, or to everyone with due when none are selected. */
    public function sendReminders(array $ids = []): void
    {
        $profiles = $ids
            ? MasterProfile::whereIn('id', array_map('intval', $ids))->get()
            : MasterProfile::whereIn('id', $this->base()->whereRaw('COALESCE(pb.receivable, 0) > 0.5')->limit(1000)->pluck('master_profiles.id'))->get();

        $sms = app(SendProfileSms::class);
        $sent = $profiles->filter(fn ($p) => $sms->dueReminder($p))->count();

        $this->dispatch('toast', type: $sent ? 'success' : 'warning', message: "Due reminder SMS sent to {$sent} customer(s).");
    }

    public function sendReminder(int $id): void
    {
        $ok = app(SendProfileSms::class)->dueReminder(MasterProfile::findOrFail($id));
        $this->dispatch('toast', type: $ok ? 'success' : 'warning', message: $ok ? 'Due reminder SMS sent.' : 'No due, or no valid phone number.');
    }

    /** Customer row figures in the design's shape. */
    public function present(MasterProfile $p): array
    {
        $due = round((float) $p->due, 2);
        $adv = round((float) $p->advance, 2);
        $oldest = $p->oldest ? Carbon::parse($p->oldest) : null;
        $days = $oldest ? (int) $oldest->copy()->startOfDay()->diffInDays(today()) : null;

        $st = match (true) {
            $due > 0.5 && $days !== null && $days >= 60 => 'overdue',
            $due > 0.5 => 'due',
            $adv > 0.5 => 'adv',
            default => 'clear',
        };

        $limit = (float) ($p->roleSettings('customer')['credit_limit'] ?? 0);
        $pct = $limit > 0 ? (int) round(max(0, $due) / $limit * 100) : 0;

        $plan = $p->plan;

        return [
            'st'         => $st,
            'due'        => $st === 'adv' ? Bdt::money($adv) : Bdt::money(max(0, $due)),
            'dueColor'   => ['due' => '#047857', 'overdue' => '#b45309', 'adv' => '#4338ca', 'clear' => '#6b7280'][$st],
            'age'        => match ($st) {
                'overdue' => "{$days} দিন পুরনো",
                'due'     => $days !== null ? "{$days} দিন" : 'Opening',
                'adv'     => 'Advance নেওয়া',
                default   => 'Clear',
            },
            'limit'      => $limit > 0 ? Bdt::money($limit) : '—',
            'pct'        => $pct,
            'pctLabel'   => $limit > 0 ? "{$pct}%" : '',
            'bar'        => $pct >= 100 ? '#dc2626' : ($pct >= 85 ? '#f59e0b' : '#10b981'),
            'kisti'      => $plan ? $plan->badge((int) $plan->paid_count) : '—',
            'total'      => Bdt::money($p->purchase->total ?? 0),
            'orders'     => (int) ($p->purchase->orders ?? 0),
            'lastAmt'    => $p->last_payment ? Bdt::money($p->last_payment['amount']) : '—',
            'lastDate'   => $p->last_payment ? Bdt::relative($p->last_payment['date']) : '',
        ];
    }

    public function render()
    {
        return view('livewire.accounts.profiles.customers', [
            'money' => fn ($v) => Bdt::money($v),
        ])->layoutData(['active' => 'customers']);
    }
}
