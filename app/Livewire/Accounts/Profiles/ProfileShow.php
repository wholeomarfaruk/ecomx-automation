<?php

namespace App\Livewire\Accounts\Profiles;

use App\Actions\Profiles\InstallmentPlans;
use App\Actions\Profiles\ProfileVouchers;
use App\Actions\Profiles\SendProfileSms;
use App\Enums\Purchase\SupplierInvoiceType;
use App\Exceptions\Accounts\LockedFiscalPeriodException;
use App\Livewire\Accounts\Profiles\Concerns\HandlesVoucherModals;
use App\Models\AccountsSupplierBill;
use App\Models\InstallmentPlan;
use App\Models\JournalEntry;
use App\Models\Loan;
use App\Models\MasterProfile;
use App\Models\Order;
use App\Models\SupplierInvoice;
use App\Services\Profiles\InstallmentScheduler;
use App\Services\Profiles\ProfileBalances;
use App\Services\Profiles\ProfileLedger;
use App\Support\Bdt;
use App\Support\BdPhone;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Accounts Panel › Profile details — design: ProfileDetail.dc.html.
 * Header, one position card per money role, net position with set-off,
 * the statement (ledger) across all roles, kisti, sales, purchases,
 * documents and the notes / activity timeline.
 */
#[Title('Profile')]
#[Layout('layouts.accounts.profiles')]
class ProfileShow extends Component
{
    use HandlesVoucherModals, WithFileUploads;

    public const TABS = ['ledger' => 'Ledger', 'kisti' => 'Installments', 'sales' => 'Sales', 'purchases' => 'Purchases', 'docs' => 'Documents', 'activity' => 'Notes & activity'];

    public MasterProfile $profile;

    #[Url(except: 'ledger')]
    public string $tab = 'ledger';

    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    #[Url(as: 'role', except: '')]
    public string $ledgerRole = '';

    public string $note = '';

    public ?int $voucherEntryId = null;

    public bool $confirmSetOff = false;

    public $docFile = null;
    public string $docTitle = '';
    public ?string $docExpires = null;

    public bool $showPlanForm = false;
    public array $plan = [];

    public function mount(MasterProfile $profile): void
    {
        $this->profile = $profile->load(['roles', 'city:id,name', 'policeStation:id,name', 'state:id,name']);
        $this->tab = array_key_exists($this->tab, self::TABS) ? $this->tab : 'ledger';
        $this->from = $this->from ?: now()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $this->to = $this->to ?: today()->toDateString();
    }

    public function setTab(string $tab): void
    {
        $this->tab = array_key_exists($tab, self::TABS) ? $tab : 'ledger';
    }

    #[Computed]
    public function balances(): object
    {
        return app(ProfileBalances::class)->forProfile($this->profile->id);
    }

    /** Position cards (one per money role) + the net position. */
    #[Computed]
    public function positions(): array
    {
        $p = $this->profile;
        $b = $this->balances;
        $cards = [];

        if ($p->hasRole('customer')) {
            $limit = (float) ($p->roleSettings('customer')['credit_limit'] ?? 0);
            $due = max(0, (float) $b->receivable);
            $cards[] = [
                'label' => 'Customer হিসেবে · পাবো', 'value' => Bdt::money($due), 'color' => '#047857',
                'sub'   => $limit > 0 ? 'Credit limit ' . Bdt::money($limit) . ' · ' . (int) round($due / $limit * 100) . '% used'
                    : ((float) $b->customer_advance > 0.5 ? 'Advance নেওয়া ' . Bdt::money($b->customer_advance) : 'No credit limit'),
            ];
        }

        if ($p->hasRole('supplier')) {
            $set = $p->roleSettings('supplier');
            $terms = (string) ($set['terms'] ?? 'cash');
            $bill = AccountsSupplierBill::whereIn('supplier_id', $p->suppliers()->pluck('id'))->whereIn('status', ['open', 'partial'])->oldest()->first();
            $dueOn = $bill ? $bill->created_at->copy()->addDays(is_numeric($terms) ? (int) $terms : 0) : null;
            $cards[] = [
                'label' => 'Supplier হিসেবে · দিবো', 'value' => Bdt::money(max(0, (float) $b->payable)), 'color' => '#b91c1c',
                'sub'   => trim(($dueOn ? 'Due ' . $dueOn->format('d M') . ' · ' : '') . (is_numeric($terms) ? "credit {$terms} days" : ucfirst($terms))
                    . ((float) $b->supplier_advance > 0.5 ? ' · advance ' . Bdt::money($b->supplier_advance) : '')),
            ];
        }

        if ($p->hasRole('lender')) {
            $next = \App\Models\LoanRepaymentSchedule::whereIn('loan_id', $p->loans()->pluck('id'))->where('status', '!=', 'paid')->orderBy('due_date')->first();
            $cards[] = [
                'label' => 'Lender হিসেবে · Loan বাকি', 'value' => Bdt::money(max(0, (float) $b->loan)), 'color' => '#b91c1c',
                'sub'   => $next ? 'Next kisti ' . $next->due_date->format('d M') . ' · ' . Bdt::money($next->payable() - (float) $next->paid_amount) : 'No fixed kisti',
            ];
        }

        if ($p->hasRole('owner')) {
            $share = (float) ($p->roleSettings('owner')['share_percent'] ?? 0);
            $cards[] = [
                'label' => 'Owner হিসেবে · Capital', 'value' => Bdt::money((float) $b->capital - (float) $b->drawings), 'color' => '#92400e',
                'sub'   => 'Capital ' . Bdt::money($b->capital) . ' − drawings ' . Bdt::money($b->drawings) . ($share ? " · share {$share}%" : ''),
            ];
        }

        if ($p->hasRole('employee')) {
            $salary = (float) ($p->roleSettings('employee')['salary'] ?? 0);
            $owed = (float) $b->salary_payable;
            $cards[] = [
                'label' => 'Employee হিসেবে · ' . ($owed > 0.5 ? 'বেতন বাকি' : 'Salary advance'),
                'value' => Bdt::money($owed > 0.5 ? $owed : (float) $b->salary_advance),
                'color' => $owed > 0.5 ? '#b91c1c' : '#4338ca',
                'sub'   => 'Salary ' . Bdt::money($salary) . ' / month',
            ];
        }

        $receivable = max(0, (float) $b->receivable);
        $owe = max(0, (float) $b->payable) + max(0, (float) $b->loan) + max(0, (float) $b->salary_payable);
        $net = null;

        if ($receivable > 0.5 && $owe > 0.5) {
            $diff = $owe - $receivable;
            $net = [
                'value'  => ($diff >= 0 ? 'দিবো ' : 'পাবো ') . Bdt::money(abs($diff)),
                'sub'    => Bdt::money($owe) . ' − ' . Bdt::money($receivable),
                'setoff' => $p->hasRole('customer') && $p->hasRole('supplier') && (float) $b->payable > 0.5
                    ? round(min($receivable, (float) $b->payable), 2) : null,
            ];
        }

        return ['cards' => $cards, 'net' => $net];
    }

    public function setOff(): void
    {
        try {
            $entry = app(ProfileVouchers::class)->setOff($this->profile, today()->toDateString());
        } catch (LockedFiscalPeriodException|\RuntimeException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        }

        $this->confirmSetOff = false;
        unset($this->balances, $this->positions, $this->statement);
        $this->dispatch('toast', type: $entry ? 'success' : 'warning', message: $entry ? "Set-off posted ({$entry->entry_number})." : 'Nothing to set off.');
    }

    /* ---------------- Ledger ---------------- */

    #[Computed]
    public function statement(): array
    {
        return app(ProfileLedger::class)->statement($this->profile, $this->from ?: null, $this->to ?: null, $this->ledgerRole ?: null);
    }

    public function showVoucherEntry(int $id): void
    {
        $this->voucherEntryId = $id;
    }

    #[Computed]
    public function voucherEntry(): ?JournalEntry
    {
        return $this->voucherEntryId ? JournalEntry::with(['lines.account:id,code,name', 'createdBy:id,name', 'postedBy:id,name'])->find($this->voucherEntryId) : null;
    }

    public function sendStatement(): void
    {
        $ok = app(SendProfileSms::class)->statement($this->profile, (float) $this->statement['closing']);
        $this->dispatch('toast', type: $ok ? 'success' : 'warning', message: $ok ? 'Statement sent by SMS.' : 'Could not send — check the mobile number / SMS gateway.');
    }

    public function whatsappUrl(): ?string
    {
        $phone = BdPhone::normalize($this->profile->phone);
        if (! $phone) {
            return null;
        }

        $closing = (float) $this->statement['closing'];
        $text = "{$this->profile->display_name}, " . today()->format('d M Y') . ' পর্যন্ত হিসাব: '
            . (abs($closing) < 0.5 ? 'Clear' : ($closing > 0 ? 'আপনার কাছে পাওনা ' : 'আপনাকে দেওয়া বাকি ') . Bdt::money(abs($closing)));

        return 'https://wa.me/88' . $phone . '?text=' . rawurlencode($text);
    }

    /* ---------------- Installments ---------------- */

    #[Computed]
    public function plans(): array
    {
        $plans = InstallmentPlan::with('installments')->where('master_profile_id', $this->profile->id)->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")->latest()->get()
            ->map(function (InstallmentPlan $plan) {
                $next = $plan->installments->first(fn ($k) => $k->status !== 'paid');

                return [
                    'title'  => ($plan->reference ? $plan->reference . ' · ' : '') . ucfirst($plan->frequency) . ' kisti plan' . ($plan->role === 'supplier' ? ' (supplier)' : ''),
                    'sub'    => "{$plan->count} × " . Bdt::money($plan->amount) . ' = ' . Bdt::money($plan->total) . ' · started ' . $plan->start_date->format('d M')
                        . ' · support ' . ($plan->support_rule === 'principal' ? 'reduces the last kisti' : 'adjusts next kisti'),
                    'badge'  => $plan->installments->where('status', 'paid')->count() . " / {$plan->count} paid",
                    'boxes'  => $plan->installments->map(fn ($k) => $this->kistiBox($k->no, $k->due_date, (float) $k->amount, (float) $k->adjusted_amount, (float) $k->paid_amount, (float) $k->support_amount, $k->status === 'paid', $next?->id === $k->id))->all(),
                ];
            });

        $loans = Loan::with(['schedules' => fn ($q) => $q->orderBy('due_date')])->where('master_profile_id', $this->profile->id)->get()
            ->map(function (Loan $loan) {
                $next = $loan->schedules->first(fn ($s) => $s->status !== 'paid');

                return [
                    'title' => $loan->name . ' · ' . InstallmentScheduler::ruleLabel($loan->frequency ?? 'monthly', $loan->schedules->count() ?: null, $loan->custom_offsets),
                    'sub'   => 'Loan ' . Bdt::money($loan->principal) . ' · started ' . $loan->start_date->format('d M Y') . ' · support ' . ($loan->support_rule === 'principal' ? 'reduces principal' : 'adjusts next kisti'),
                    'badge' => $loan->schedules->where('status', 'paid')->count() . ' / ' . $loan->schedules->count() . ' paid',
                    'boxes' => $loan->schedules->map(fn ($s) => $this->kistiBox($s->installment_no ?? 0, $s->due_date, $s->emi(), $s->payable(), (float) $s->paid_amount, (float) $s->support_amount, $s->status === 'paid', $next?->id === $s->id))->all(),
                ];
            });

        return $plans->concat($loans)->all();
    }

    protected function kistiBox(int $no, Carbon $date, float $amount, float $adjusted, float $paid, float $support, bool $isPaid, bool $isNext): array
    {
        return [
            'no'     => $no,
            'date'   => $date->format('d M'),
            'amount' => Bdt::money($isPaid ? max($paid, $adjusted) : $adjusted - $paid),
            'note'   => match (true) {
                $isPaid && $support > 0 => 'Paid · +' . Bdt::money($support) . ' support',
                $isPaid => 'Paid',
                $isNext && $adjusted < $amount => 'Next · ' . Bdt::money($amount - $adjusted) . ' adjusted',
                $isNext && $paid > 0 => 'Next · ' . Bdt::money($paid) . ' paid',
                $isNext => $date->lt(today()) ? 'Overdue' : 'Next',
                $adjusted < $amount => Bdt::money($amount - $adjusted) . ' adjusted',
                default => 'Upcoming',
            },
            'state'  => $isPaid ? 'paid' : ($isNext ? 'next' : 'later'),
        ];
    }

    public function openPlanForm(): void
    {
        $this->resetErrorBag();
        $b = $this->balances;
        $role = $this->profile->hasRole('customer') ? 'customer' : 'supplier';
        $this->plan = [
            'role'         => $role,
            'total'        => round(max(0, $role === 'customer' ? (float) $b->receivable : (float) $b->payable), 2) ?: null,
            'frequency'    => 'weekly',
            'count'        => 8,
            'start_date'   => today()->toDateString(),
            'offsets'      => '',
            'support_rule' => 'next_kisti',
            'reference'    => '',
        ];
        $this->showPlanForm = true;
    }

    public function savePlan(): void
    {
        $this->validate([
            'plan.role'         => 'required|in:customer,supplier',
            'plan.total'        => 'required|numeric|min:1',
            'plan.frequency'    => 'required|in:' . implode(',', InstallmentScheduler::FREQUENCIES),
            'plan.count'        => 'required_unless:plan.frequency,custom|nullable|integer|min:1|max:600',
            'plan.start_date'   => 'required|date',
            'plan.support_rule' => 'required|in:next_kisti,principal',
            'plan.reference'    => 'nullable|string|max:60',
        ], [], ['plan.total' => 'amount', 'plan.count' => 'number of kisti']);

        $offsets = array_values(array_filter(array_map('intval', preg_split('/[\s,\/]+/', (string) $this->plan['offsets'])), fn ($d) => $d > 0));
        if ($this->plan['frequency'] === 'custom' && ! $offsets) {
            $this->addError('plan.offsets', 'Give the day offsets, e.g. 15, 30, 45.');

            return;
        }

        app(InstallmentPlans::class)->create(
            $this->profile, $this->plan['role'], (float) $this->plan['total'], $this->plan['frequency'], (int) $this->plan['count'],
            $this->plan['start_date'], $offsets, $this->plan['support_rule'], $this->plan['reference'] ?: null,
        );

        $this->showPlanForm = false;
        unset($this->plans);
        $this->dispatch('toast', type: 'success', message: 'Kisti plan created.');
    }

    /* ---------------- Sales / purchases ---------------- */

    #[Computed]
    public function sales()
    {
        $customerIds = $this->profile->customers()->pluck('id');

        return Order::whereIn('customer_id', $customerIds)->withCount('items')->withSum('items', 'quantity')
            ->latest()->limit(50)->get();
    }

    #[Computed]
    public function purchases(): array
    {
        $supplierIds = $this->profile->suppliers()->pluck('id');
        $terms = (string) ($this->profile->roleSettings('supplier')['terms'] ?? 'cash');

        $bills = AccountsSupplierBill::whereIn('supplier_id', $supplierIds)->whereNotNull('supplier_invoice_id')->get()->keyBy('supplier_invoice_id');

        return SupplierInvoice::whereIn('supplier_id', $supplierIds)->where('type', SupplierInvoiceType::PURCHASE->value)
            ->withCount('items')->orderByDesc('invoice_date')->limit(50)->get()
            ->map(function (SupplierInvoice $inv) use ($bills, $terms) {
                $bill = $bills->get($inv->id);
                $open = $bill && $bill->amountDue() > 0.01;

                return [
                    'date'   => $inv->invoice_date?->format('d M') ?? '—',
                    'ref'    => $inv->invoice_number ?: 'PUR-' . str_pad((string) $inv->serial_number, 4, '0', STR_PAD_LEFT),
                    'items'  => $inv->items_count ? $inv->items_count . ' items' . ($inv->notes ? ' · ' . \Illuminate\Support\Str::limit($inv->notes, 30) : '') : ($inv->notes ?: '—'),
                    'amount' => Bdt::money($inv->amount),
                    'due'    => $open ? $inv->invoice_date?->copy()->addDays(is_numeric($terms) ? (int) $terms : 0)->format('d M') . ' · ' . Bdt::money($bill->amountDue()) : 'Paid',
                ];
            })->all();
    }

    /* ---------------- Documents ---------------- */

    public function uploadDocument(): void
    {
        $this->validate([
            'docFile'    => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,webp',
            'docTitle'   => 'nullable|string|max:120',
            'docExpires' => 'nullable|date',
        ], [], ['docFile' => 'file']);

        $this->profile->documents()->create([
            'title'       => $this->docTitle ?: pathinfo($this->docFile->getClientOriginalName(), PATHINFO_FILENAME),
            'path'        => $this->docFile->store("profile-documents/{$this->profile->id}", 'local'),
            'mime'        => $this->docFile->getMimeType(),
            'size'        => $this->docFile->getSize(),
            'expires_on'  => $this->docExpires ?: null,
            'uploaded_by' => auth()->id(),
        ]);

        $this->profile->logActivity('document', 'Document uploaded: ' . ($this->docTitle ?: $this->docFile->getClientOriginalName()));
        $this->reset('docFile', 'docTitle', 'docExpires');
        $this->dispatch('toast', type: 'success', message: 'Document uploaded.');
    }

    public function deleteDocument(int $id): void
    {
        $doc = $this->profile->documents()->findOrFail($id);
        Storage::disk('local')->delete($doc->path);
        $doc->delete();
        $this->profile->logActivity('document', "Document removed: {$doc->title}");
    }

    /* ---------------- Notes & activity ---------------- */

    public function addNote(): void
    {
        $this->validate(['note' => 'required|string|max:500']);
        $this->profile->logActivity('note', trim($this->note));
        $this->note = '';
    }

    #[Computed]
    public function activity()
    {
        return $this->profile->activities()->with('user:id,name')->latest('created_at')->latest('id')->limit(50)->get();
    }

    /** Pay / Collect buttons open the voucher that fits the profile's roles. */
    public function payType(): ?string
    {
        foreach (['supplier' => 'pay', 'lender' => 'kisti', 'employee' => 'salary', 'owner' => 'drawing'] as $role => $type) {
            if ($this->profile->hasRole($role)) {
                return $type;
            }
        }

        return null;
    }

    public function collectType(): ?string
    {
        return $this->profile->hasRole('customer') ? 'collect' : ($this->profile->hasRole('owner') ? 'capital' : null);
    }

    public function render()
    {
        return view('livewire.accounts.profiles.show', [
            'money' => fn ($v) => Bdt::money($v),
        ])->title($this->profile->display_name)->layoutData(['active' => 'profiles']);
    }
}
