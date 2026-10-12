<?php

namespace App\Livewire\Accounts\Profiles;

use App\Enums\Purchase\SupplierInvoiceType;
use App\Livewire\Accounts\Profiles\Concerns\HandlesVoucherModals;
use App\Models\MasterProfile;
use App\Services\Profiles\ProfileBalances;
use App\Support\Bdt;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Accounts Panel › Suppliers — design: Suppliers.dc.html.
 * Payable from the posted ledger; next due = oldest open bill + the
 * supplier's credit terms. Supplier lists are small, so the rows are
 * shaped once and filtered in memory.
 */
#[Title('Suppliers')]
#[Layout('layouts.accounts.profiles')]
class Suppliers extends Component
{
    use HandlesVoucherModals, WithPagination;

    public const PER_PAGE = 25;

    public const CHIPS = ['all' => 'All', 'pay' => 'Payable', 'overdue' => 'Overdue', 'adv' => 'Advance given', 'clear' => 'Clear'];

    public const CATEGORIES = ['Fabric', 'Embroidery', 'Accessories', 'Packaging', 'Printing'];

    public const TERMS = ['cash' => 'Cash', '7' => 'Credit 7 days', '15' => 'Credit 15 days', '30' => 'Credit 30 days', 'kisti' => 'Kisti (custom)'];

    #[Url(except: '')]
    public string $q = '';

    #[Url(except: '')]
    public string $category = '';

    #[Url(except: '')]
    public string $terms = '';

    #[Url(except: 'all')]
    public string $st = 'all';

    public function updated($name): void
    {
        if (in_array($name, ['q', 'category', 'terms'], true)) {
            $this->resetPage();
        }
    }

    public function setSt(string $st): void
    {
        $this->st = array_key_exists($st, self::CHIPS) ? $st : 'all';
        $this->resetPage();
    }

    /** Every supplier, shaped for the table. */
    #[Computed]
    public function all(): Collection
    {
        $profiles = MasterProfile::withRole('supplier')->with('roles')->orderBy('display_name')->get();
        $ids = $profiles->pluck('id')->all();
        $balances = app(ProfileBalances::class)->forProfiles($ids);
        $year = now()->startOfYear()->toDateString();

        $purchases = $ids ? DB::table('supplier_invoices as i')->join('suppliers as s', 's.id', '=', 'i.supplier_id')
            ->whereIn('s.master_profile_id', $ids)
            ->where('i.type', SupplierInvoiceType::PURCHASE->value)
            ->groupBy('s.master_profile_id')
            ->selectRaw('s.master_profile_id as pid,
                COALESCE(SUM(CASE WHEN i.invoice_date >= ? THEN i.amount ELSE 0 END), 0) as year_total,
                MAX(i.invoice_date) as last_date', [$year])
            ->get()->keyBy('pid') : collect();

        $bills = $ids ? DB::table('accounts_supplier_bills as b')->join('suppliers as s', 's.id', '=', 'b.supplier_id')
            ->whereIn('s.master_profile_id', $ids)->whereIn('b.status', ['open', 'partial'])
            ->groupBy('s.master_profile_id')
            ->selectRaw('s.master_profile_id as pid, MIN(b.created_at) as oldest')
            ->get()->keyBy('pid') : collect();

        return $profiles->map(fn (MasterProfile $p) => $this->shape($p, $balances[$p->id], $purchases->get($p->id), $bills->get($p->id)));
    }

    protected function shape(MasterProfile $p, object $b, ?object $purchase, ?object $bill): array
    {
        $settings = $p->roleSettings('supplier');
        $termKey = (string) ($settings['terms'] ?? 'cash');
        $termDays = is_numeric($termKey) ? (int) $termKey : 0;
        $payable = round((float) $b->payable, 2);
        $advance = round((float) $b->supplier_advance, 2);
        $receivable = round((float) $b->receivable, 2);

        $due = null;
        if ($payable > 0.5) {
            $due = ($bill?->oldest ? Carbon::parse($bill->oldest) : ($p->updated_at ?? now()))->copy()->startOfDay()->addDays($termDays);
        }

        $overdue = $due && $due->lt(today());
        $st = match (true) {
            $payable > 0.5 && $overdue => 'overdue',
            $payable > 0.5 => 'pay',
            $advance > 0.5 => 'adv',
            default => 'clear',
        };

        $days = $due ? (int) today()->diffInDays($due, false) : null;

        return [
            'profile'      => $p,
            'st'           => $st,
            'category'     => $settings['category'] ?? '—',
            'terms'        => self::TERMS[$termKey] ?? (string) $termKey,
            'termKey'      => $termKey,
            'purchase'     => (float) ($purchase->year_total ?? 0),
            'balance'      => $st === 'adv' ? $advance : $payable,
            'balLabel'     => match ($st) {
                'adv'   => 'Advance দেওয়া',
                'clear' => 'Clear',
                default => 'দিবো' . ($receivable > 0.5 ? ' · net ' . Bdt::money(abs($payable - $receivable)) : ''),
            },
            'alsoCustomer' => $p->hasRole('customer'),
            'due'          => $due?->format('d M') ?? '—',
            'dueDate'      => $due,
            'dueNote'      => match (true) {
                $days === null => '',
                $days < 0 => abs($days) . ' days overdue',
                $days === 0 => 'today',
                default => "in {$days} days",
            },
            'last'         => $purchase?->last_date ? Carbon::parse($purchase->last_date)->format('d M') : '—',
            'payable'      => $payable,
        ];
    }

    #[Computed]
    public function filtered(): Collection
    {
        $q = mb_strtolower(trim($this->q));

        return $this->all->filter(function ($r) use ($q) {
            $p = $r['profile'];

            return ($this->st === 'all' || $r['st'] === $this->st)
                && ($this->category === '' || $r['category'] === $this->category)
                && ($this->terms === '' || $r['termKey'] === $this->terms)
                && ($q === '' || str_contains(mb_strtolower($p->display_name . ' ' . $p->phone . ' ' . $p->contact_person . ' ' . $p->code), $q));
        })->values();
    }

    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        $page = $this->getPage();

        return new LengthAwarePaginator($this->filtered->forPage($page, self::PER_PAGE)->values(), $this->filtered->count(), self::PER_PAGE, $page);
    }

    #[Computed]
    public function summary(): array
    {
        $all = $this->all;
        $week = today()->addDays(7);
        $payables = $all->where('payable', '>', 0.5);

        $month = DB::table('supplier_invoices as i')->join('suppliers as s', 's.id', '=', 'i.supplier_id')
            ->whereNotNull('s.master_profile_id')
            ->where('i.type', SupplierInvoiceType::PURCHASE->value)
            ->where('i.invoice_date', '>=', now()->startOfMonth()->toDateString())
            ->selectRaw('COALESCE(SUM(i.amount), 0) as total, COUNT(*) as n')->first();

        return [
            'count'        => $all->count(),
            'alsoCustomer' => $all->where('alsoCustomer', true)->count(),
            'payable'      => $payables->sum('payable'),
            'payableCount' => $payables->count(),
            'week'         => $payables->filter(fn ($r) => $r['dueDate'] && $r['dueDate']->lte($week))->sum('payable'),
            'overdue'      => $payables->where('st', 'overdue')->sum('payable'),
            'advance'      => $all->where('st', 'adv')->sum('balance'),
            'month'        => (float) $month->total,
            'monthCount'   => (int) $month->n,
            'chips'        => collect(self::CHIPS)->keys()->mapWithKeys(fn ($k) => [$k => $k === 'all' ? $all->count() : $all->where('st', $k)->count()])->all(),
        ];
    }

    public function render()
    {
        return view('livewire.accounts.profiles.suppliers', [
            'money' => fn ($v) => Bdt::money($v),
        ])->layoutData(['active' => 'suppliers']);
    }
}
