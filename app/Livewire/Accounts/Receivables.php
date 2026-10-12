<?php

namespace App\Livewire\Accounts;

use App\Actions\Profiles\SendProfileSms;
use App\Models\Account;
use App\Models\Courier;
use App\Models\CourierCodSettlement;
use App\Models\CourierShipment;
use App\Models\InstallmentPlan;
use App\Models\MasterProfile;
use App\Models\MasterProfileActivity;
use App\Models\PromiseToPay;
use App\Services\Accounts\Aging;
use App\Services\Accounts\Vouchers;
use App\Services\Profiles\LedgerAccounts;
use App\Services\Profiles\ProfileBalances;
use App\Support\AccountsAccess;
use App\Support\AccountsSettings;
use App\Support\Bdt;
use App\Support\BdPhone;
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
 * Accounts Panel › Receivables (Due) — design: Receivables.dc.html.
 * Customer aging (FIFO over posted receivable), promises to pay and
 * call-backs, today's follow-up generated from rules, courier COD still
 * with the courier, and advances given to suppliers / staff.
 */
#[Title('Receivables')]
#[Layout('layouts.accounts.app')]
class Receivables extends Component
{
    public const CHIPS = ['all' => 'All', 'b1' => '0–30 দিন', 'b2' => '31–60 দিন', 'b3' => '61–90 দিন', 'b4' => '90+ দিন'];

    public const SORTS = ['due' => 'Highest due', 'oldest' => 'Oldest first', 'promise' => 'Promise date'];

    #[Url(except: 'all')]
    public string $bucket = 'all';

    #[Url(except: 'due')]
    public string $sort = 'due';

    public bool $showAll = false;

    public ?int $promiseFor = null;
    public array $promise = [];

    public ?int $writeOffFor = null;
    public $writeOffAmount = null;

    public bool $showSettle = false;
    public array $settle = [];

    public function mount(): void
    {
        AccountsAccess::authorize('view');
    }

    #[On('voucher-saved')]
    public function refreshData(): void
    {
        unset($this->aging, $this->rows);
    }

    #[Computed]
    public function aging(): Collection
    {
        return app(Aging::class)->receivables()->keyBy('profile_id');
    }

    /** Open promises / call-backs per profile, with kept / broken settled from the current balance. */
    #[Computed]
    public function promises(): Collection
    {
        $aging = $this->aging;

        return PromiseToPay::whereIn('status', ['open', 'broken'])->orderBy('due_date')->get()
            ->map(function (PromiseToPay $p) use ($aging) {
                $due = (float) ($aging[$p->master_profile_id]['total'] ?? 0);

                if ($p->kind === 'promise' && $p->status === 'open' && $p->amount && $due <= (float) $p->balance_at_promise - (float) $p->amount + 0.5) {
                    $p->update(['status' => 'kept', 'resolved_at' => now()]);
                } elseif ($p->kind === 'promise' && $p->status === 'open' && $p->due_date->lt(today())) {
                    $p->update(['status' => 'broken']);
                } elseif ($due <= 0.5) {
                    $p->update(['status' => 'kept', 'resolved_at' => now()]);
                }

                return $p;
            })
            ->whereIn('status', ['open', 'broken'])
            ->groupBy('master_profile_id');
    }

    /** Last follow-up per profile (call / SMS / payment / promise note). */
    #[Computed]
    public function lastFollowUp(): Collection
    {
        $ids = $this->aging->keys()->all();

        return $ids ? MasterProfileActivity::with('user:id,name')->whereIn('master_profile_id', $ids)
            ->whereIn('type', ['call', 'sms', 'receipt', 'promise'])
            ->orderByDesc('created_at')->orderByDesc('id')->get()->unique('master_profile_id')->keyBy('master_profile_id') : collect();
    }

    #[Computed]
    public function rows(): Collection
    {
        $aging = $this->aging;
        $profiles = MasterProfile::with(['city:id,name', 'policeStation:id,name', 'roles'])->whereIn('id', $aging->keys())->get()->keyBy('id');
        $plans = InstallmentPlan::whereIn('master_profile_id', $aging->keys())->where('role', 'customer')->where('status', 'active')
            ->with(['installments' => fn ($q) => $q->where('status', '!=', 'paid')->orderBy('no')])->get()->keyBy('master_profile_id');
        $idx = ['b1' => 0, 'b2' => 1, 'b3' => 2, 'b4' => 3];

        $rows = $aging->filter(fn ($a) => $profiles->has($a['profile_id']) && $profiles[$a['profile_id']]->hasRole('customer'))
            ->filter(fn ($a) => $this->bucket === 'all' || $a['buckets'][$idx[$this->bucket]] > 0.5)
            ->map(function ($a) use ($profiles, $plans) {
                $p = $profiles[$a['profile_id']];
                [$text, $style, $promiseDate] = $this->promiseText($p, $a, $plans->get($p->id));

                return $a + ['profile' => $p, 'promise' => $text, 'promiseStyle' => $style, 'promiseDate' => $promiseDate, 'last' => $this->lastText($p->id)];
            });

        $rows = match ($this->sort) {
            'oldest'  => $rows->sortBy(fn ($r) => $r['oldest']?->timestamp ?? 0),
            'promise' => $rows->sortBy(fn ($r) => $r['promiseDate']?->timestamp ?? PHP_INT_MAX),
            default   => $rows->sortByDesc('total'),
        };

        return $rows->values();
    }

    /** [text, style class, date] for the Promise / follow-up column. */
    protected function promiseText(MasterProfile $p, array $a, ?InstallmentPlan $plan): array
    {
        $promise = ($this->promises[$p->id] ?? collect())->first();

        if ($promise?->status === 'broken') {
            return ['Promise broken · ' . $promise->due_date->format('d M'), 'font-semibold text-[#b91c1c]', $promise->due_date];
        }
        if ($promise?->kind === 'callback') {
            return ['Call again ' . $promise->due_date->format('d M'), 'text-[#92400e]', $promise->due_date];
        }
        if ($promise) {
            return ['Promised ' . $promise->due_date->format('d M') . ($promise->amount ? ' · ' . Bdt::money($promise->amount) : ''), 'text-[#111827]', $promise->due_date];
        }
        if ($plan) {
            $next = $plan->installments->first();

            return [ucfirst($plan->frequency) . ' kisti' . ($next ? ' · next ' . $next->due_date->format('d M') : ' চলছে'), 'text-[#111827]', $next?->due_date];
        }

        $reminders = $this->reminderCount($p->id);
        if ($a['buckets'][3] > 0.5 && $reminders >= 3) {
            return ["No response · {$reminders} reminders", 'font-semibold text-[#b91c1c]', null];
        }

        return ['No follow-up yet', 'text-[#92400e]', null];
    }

    protected function reminderCount(int $profileId): int
    {
        static $counts = null;
        $counts ??= MasterProfileActivity::where('type', 'sms')->where('created_at', '>=', now()->subDays(90))
            ->selectRaw('master_profile_id, COUNT(*) as n')->groupBy('master_profile_id')->pluck('n', 'master_profile_id');

        return (int) ($counts[$profileId] ?? 0);
    }

    protected function lastText(int $profileId): string
    {
        $a = $this->lastFollowUp[$profileId] ?? null;
        if (! $a) {
            return '—';
        }

        $when = $a->created_at->isToday() ? 'today' : $a->created_at->format('d M');
        $by = $a->user?->name ? ' by ' . $a->user->name : '';

        return match ($a->type) {
            'call'    => "Called {$when}{$by}",
            'sms'     => "SMS {$when}",
            'receipt' => str_replace('Payment received', 'Paid', $a->text) . " {$when}",
            default   => "{$a->text} · {$when}",
        };
    }

    /** Today's follow-up (rules from spec §5.5 / §7.9). */
    #[Computed]
    public function followups(): array
    {
        $out = [];
        $balances = app(ProfileBalances::class);

        foreach ($this->rows as $r) {
            $p = $r['profile'];
            $promise = ($this->promises[$p->id] ?? collect())->first();
            $limit = (float) ($p->roleSettings('customer')['credit_limit'] ?? 0);
            $recent = ($this->lastFollowUp[$p->id] ?? null)?->created_at?->gt(now()->subDays(7));

            if ($promise?->status === 'broken') {
                $out[] = ['p' => $p, 'why' => $promise->due_date->format('d M') . '-এর promise রাখেনি' . ($limit > 0 && $r['total'] > $limit ? ' · limit পার' : ''), 'amount' => $r['total'], 'third' => 'Stop credit', 'tone' => 'red', 'rank' => 1];
            } elseif ($r['buckets'][3] > 0.5 && ! $promise && ! $recent) {
                $n = $this->reminderCount($p->id);
                $out[] = ['p' => $p, 'why' => '90+ দিন · ' . ($n ? "{$n}টা reminder-এর উত্তর নেই" : 'কোনো follow-up নেই'), 'amount' => $r['total'], 'third' => 'Write off', 'tone' => 'red', 'rank' => 0];
            } elseif ($promise?->kind === 'callback' && $promise->due_date->lte(today())) {
                $out[] = ['p' => $p, 'why' => $promise->due_date->format('d M') . ' আবার call করতে বলেছে', 'amount' => $r['total'], 'third' => 'Set promise', 'tone' => 'amber', 'rank' => 2];
            } elseif ($promise?->kind === 'promise' && $promise->due_date->lte(today()->addDay())) {
                $out[] = ['p' => $p, 'why' => $promise->due_date->format('d M') . ' ' . ($promise->amount ? Bdt::money($promise->amount) . ' ' : '') . 'দেবে বলেছে', 'amount' => $r['total'], 'third' => 'Set promise', 'tone' => 'blue', 'rank' => 3];
            }
        }

        return collect($out)->sortBy('rank')->take(6)->values()->all();
    }

    #[Computed]
    public function summary(): array
    {
        $rows = $this->rows;
        $buckets = Aging::summarize($rows);
        $total = $rows->sum('total');
        $ledgers = app(LedgerAccounts::class);
        $v = app(Vouchers::class);

        $collected = (float) DB::table('journal_entry_lines as l')->join('journal_entries as je', 'je.id', '=', 'l.journal_entry_id')
            ->where('l.account_id', $ledgers->id(LedgerAccounts::RECEIVABLE))->where('l.credit', '>', 0)
            ->where('je.status', 'posted')->where('je.entry_date', '>=', now()->startOfMonth()->toDateString())
            ->whereExists(fn ($q) => $q->from('journal_entry_lines as m')->join('accounts as a', 'a.id', '=', 'm.account_id')
                ->whereColumn('m.journal_entry_id', 'je.id')->whereIn('a.subtype', ['cash', 'bank', 'mobile_banking', 'courier']))
            ->sum('l.credit');

        $supplierAdv = $v->postedBalance($ledgers->id(LedgerAccounts::SUPPLIER_ADVANCE));
        $staffAdv = $v->postedBalance($ledgers->id(LedgerAccounts::SALARY_ADVANCE));
        $target = AccountsSettings::collectionTarget();

        return [
            'due' => $total, 'over60' => $buckets[2]['amount'] + $buckets[3]['amount'], 'buckets' => $buckets, 'count' => $rows->count(),
            'pct' => collect($buckets)->map(fn ($b) => $total > 0 ? round($b['amount'] / $total * 100, 1) : 0)->all(),
            'cod' => $this->cod->sum('amount'), 'codParcels' => $this->cod->sum('parcels'), 'couriers' => $this->cod->where('amount', '>', 0.5)->count(),
            'supplierAdv' => $supplierAdv, 'staffAdv' => $staffAdv,
            'collected' => $collected, 'target' => $target, 'targetPct' => $target > 0 ? min(100, (int) round($collected / $target * 100)) : null,
        ];
    }

    /** Per courier: COD the courier still holds, delivered parcels since the last settlement, status. */
    #[Computed]
    public function cod(): Collection
    {
        $v = app(Vouchers::class);

        return Account::where('subtype', 'courier')->whereNotNull('courier_id')->with('courier:id,name')->get()
            ->map(function (Account $acc) use ($v) {
                $last = CourierCodSettlement::where('courier_id', $acc->courier_id)
                    ->whereHas('journalEntry', fn ($q) => $q->where('status', 'posted'))->max('settled_at');
                $pending = CourierCodSettlement::with('journalEntry:id,voucher_ref,entry_number,status')->where('courier_id', $acc->courier_id)
                    ->whereHas('journalEntry', fn ($q) => $q->whereIn('status', ['draft', 'scheduled']))->latest('id')->first();

                $parcels = CourierShipment::where('courier_id', $acc->courier_id)->where('cod_amount', '>', 0)->whereNotNull('delivered_at')
                    ->when($last, fn ($q) => $q->where('delivered_at', '>', $last));
                $oldest = (clone $parcels)->min('delivered_at');
                $oldest = $oldest ? CarbonImmutable::parse($oldest) : null;
                $amount = $v->postedBalance($acc->id);

                [$status, $cls] = match (true) {
                    (bool) $pending => ['Received · ' . $pending->journalEntry->ref() . ' draft', 'bg-[#e0e7ff] text-[#3730a3]'],
                    $amount <= 0.5 => ['Settled', 'bg-[#d1fae5] text-[#065f46]'],
                    $oldest && $oldest->lt(today()->subDays(7)) => ['Late', 'bg-[#fee2e2] text-[#991b1b]'],
                    default => ['Expected ' . ($oldest ?? today())->addDays(3)->format('d M'), 'bg-[#f3f4f6] text-[#374151]'],
                };

                return ['account_id' => $acc->id, 'courier_id' => $acc->courier_id, 'name' => $acc->courier?->name ?? $acc->name,
                    'parcels' => (clone $parcels)->count(), 'amount' => $amount, 'oldest' => $oldest, 'late' => $status === 'Late', 'status' => $status, 'cls' => $cls];
            })
            ->filter(fn ($r) => $r['amount'] > 0.5 || $r['parcels'] > 0 || str_starts_with($r['status'], 'Received'))
            ->values();
    }

    #[Computed]
    public function advances(): array
    {
        $rows = DB::query()->fromSub(app(ProfileBalances::class)->query(), 'pb')
            ->where(fn ($q) => $q->where('pb.supplier_advance', '>', 0.5)->orWhere('pb.salary_advance', '>', 0.5))
            ->get(['pb.profile_id', 'pb.supplier_advance', 'pb.salary_advance']);
        $profiles = MasterProfile::with(['advances' => fn ($q) => $q->where('balance', '>', 0)])->whereIn('id', $rows->pluck('profile_id'))->get()->keyBy('id');

        return $rows->flatMap(function ($r) use ($profiles) {
            $p = $profiles[$r->profile_id] ?? null;
            $out = [];
            if ($p && (float) $r->supplier_advance > 0.5) {
                $out[] = ['name' => $p->display_name, 'id' => $p->id, 'note' => 'Supplier', 'amount' => (float) $r->supplier_advance];
            }
            if ($p && (float) $r->salary_advance > 0.5) {
                $monthly = (float) $p->advances->sum('monthly_deduction');
                $out[] = ['name' => $p->display_name, 'id' => $p->id, 'note' => 'Employee' . ($monthly > 0 ? ' · ' . Bdt::money($monthly) . ' / month কাটবে' : ''), 'amount' => (float) $r->salary_advance];
            }

            return $out;
        })->sortByDesc('amount')->values()->all();
    }

    /* ---------------- actions ---------------- */

    public function setBucket(string $b): void
    {
        $this->bucket = array_key_exists($b, self::CHIPS) ? $b : 'all';
    }

    public function collect(int $profileId): void
    {
        $this->dispatch('voucher-form', type: 'receipt', preset: ['profile_id' => $profileId]);
    }

    public function remind(int $profileId): void
    {
        $ok = app(SendProfileSms::class)->dueReminder(MasterProfile::findOrFail($profileId));
        unset($this->lastFollowUp);
        $this->dispatch('toast', type: $ok ? 'success' : 'warning', message: $ok ? 'Reminder SMS sent.' : 'No due, or no valid mobile number.');
    }

    public function remindOld(): void
    {
        $sms = app(SendProfileSms::class);
        $sent = $this->aging->filter(fn ($a) => $a['buckets'][2] + $a['buckets'][3] > 0.5)
            ->filter(fn ($a) => ($p = MasterProfile::find($a['profile_id'])) && $sms->dueReminder($p))->count();

        unset($this->lastFollowUp);
        $this->dispatch('toast', type: 'success', message: "Reminder SMS sent to {$sent} customer(s) with 60+ দিনের বাকি.");
    }

    public function logCall(int $profileId): void
    {
        MasterProfile::findOrFail($profileId)->logActivity('call', 'Called about due');
        unset($this->lastFollowUp);
    }

    public function openPromise(int $profileId): void
    {
        $this->promiseFor = $profileId;
        $this->promise = ['kind' => 'promise', 'date' => today()->addDays(3)->toDateString(), 'amount' => null, 'note' => ''];
    }

    public function savePromise(): void
    {
        $this->validate([
            'promise.kind'   => 'required|in:promise,callback',
            'promise.date'   => 'required|date',
            'promise.amount' => 'nullable|required_if:promise.kind,promise|numeric|min:1',
            'promise.note'   => 'nullable|string|max:200',
        ], [], ['promise.amount' => 'amount', 'promise.date' => 'date']);

        $p = MasterProfile::findOrFail($this->promiseFor);
        PromiseToPay::where('master_profile_id', $p->id)->whereIn('status', ['open', 'broken'])->update(['status' => 'cancelled', 'resolved_at' => now()]);
        PromiseToPay::create([
            'master_profile_id' => $p->id, 'kind' => $this->promise['kind'], 'due_date' => $this->promise['date'],
            'amount' => $this->promise['amount'] ?: null, 'note' => $this->promise['note'] ?: null, 'status' => 'open',
            'balance_at_promise' => (float) ($this->aging[$p->id]['total'] ?? 0), 'created_by' => auth()->id(),
        ]);

        $text = $this->promise['kind'] === 'promise'
            ? 'Promised ' . Bdt::money($this->promise['amount']) . ' by ' . CarbonImmutable::parse($this->promise['date'])->format('d M')
            : 'Call again ' . CarbonImmutable::parse($this->promise['date'])->format('d M');
        $p->logActivity('promise', $text . ($this->promise['note'] ? " — {$this->promise['note']}" : ''));

        $this->promiseFor = null;
        unset($this->promises, $this->lastFollowUp, $this->rows);
        $this->dispatch('toast', type: 'success', message: 'Saved.');
    }

    public function stopCredit(int $profileId): void
    {
        AccountsAccess::authorize('create');
        $p = MasterProfile::with('roles')->findOrFail($profileId);
        $p->addRole('customer', ['credit_blocked' => true]);
        $p->logActivity('setting', 'Credit stopped — no new credit sales');
        $this->dispatch('toast', type: 'success', message: "{$p->display_name}: new credit sales stopped.");
    }

    public function openWriteOff(int $profileId): void
    {
        $this->writeOffFor = $profileId;
        $a = $this->aging[$profileId] ?? null;
        $this->writeOffAmount = $a ? round($a['buckets'][3] ?: $a['total'], 2) : null;
    }

    /** Draft journal: Dr Bad debt expense / Cr Receivable (customer). */
    public function writeOff(): void
    {
        AccountsAccess::authorize('create');
        $this->validate(['writeOffAmount' => 'required|numeric|min:1'], [], ['writeOffAmount' => 'amount']);
        $p = MasterProfile::findOrFail($this->writeOffFor);
        $v = app(Vouchers::class);
        $ar = app(LedgerAccounts::class)->require(LedgerAccounts::RECEIVABLE);
        $amount = round((float) $this->writeOffAmount, 2);

        try {
            $entry = $v->draft([
                'type' => 'journal', 'transaction_type' => \App\Enums\Accounts\TransactionType::BAD_DEBT->value,
                'date' => today()->toDateString(), 'description' => "Bad debt write-off — {$p->display_name}",
                'profile_id' => $p->id,
                'lines' => [
                    ['account_id' => app(LedgerAccounts::class)->require('5600'), 'debit' => $amount],
                    ['account_id' => $ar, 'credit' => $amount] + $v->partyFields($ar, $p),
                ],
            ]);
        } catch (RuntimeException $e) {
            $this->addError('writeOffAmount', $e->getMessage());

            return;
        }

        $p->logActivity('writeoff', 'Write-off ' . Bdt::money($amount) . " proposed ({$entry->ref()}, draft)");
        $this->writeOffFor = null;
        $this->dispatch('toast', type: 'success', message: "{$entry->ref()} created as draft — post it from Posting Pending.");
    }

    /* ---------------- COD settlement ---------------- */

    public function openSettle(?int $courierId = null): void
    {
        $row = $courierId ? $this->cod->firstWhere('courier_id', $courierId) : $this->cod->first();
        $this->settle = [
            'courier_id' => $row['courier_id'] ?? null, 'gross' => $row ? round($row['amount'], 2) : null, 'fee' => null,
            'account_id' => collect(app(LedgerAccounts::class)->cashAccounts())->firstWhere('subtype', 'bank')['id'] ?? (app(LedgerAccounts::class)->cashAccounts()[0]['id'] ?? null),
            'date' => today()->toDateString(), 'parcels' => $row['parcels'] ?? null,
        ];
        $this->showSettle = true;
    }

    public function updatedSettle($value, $key): void
    {
        if ($key !== 'courier_id') {
            return;
        }

        $row = $this->cod->firstWhere('courier_id', (int) $this->settle['courier_id']);
        $this->settle['gross'] = $row ? round($row['amount'], 2) : null;
        $this->settle['parcels'] = $row['parcels'] ?? null;
    }

    /** Courier payout: draft receipt Dr bank (net) + Dr courier expense (fee) / Cr courier cash (gross). */
    public function saveSettle(): void
    {
        AccountsAccess::authorize('create');
        $this->validate([
            'settle.courier_id' => 'required|integer|exists:couriers,id',
            'settle.gross'      => 'required|numeric|min:1',
            'settle.fee'        => 'nullable|numeric|min:0|lte:settle.gross',
            'settle.account_id' => 'required|integer|exists:accounts,id',
            'settle.date'       => 'required|date',
            'settle.parcels'    => 'nullable|integer|min:0',
        ], [], ['settle.gross' => 'amount', 'settle.account_id' => 'received in']);

        $s = $this->settle;
        $courier = Courier::findOrFail($s['courier_id']);
        $cashAcc = Account::where('subtype', 'courier')->where('courier_id', $courier->id)->firstOrFail();
        $gross = round((float) $s['gross'], 2);
        $fee = round((float) ($s['fee'] ?: 0), 2);

        $lines = [['account_id' => (int) $s['account_id'], 'debit' => round($gross - $fee, 2)]];
        if ($fee > 0) {
            $lines[] = ['account_id' => app(LedgerAccounts::class)->require('5120'), 'debit' => $fee, 'memo' => 'Courier COD charge'];
        }
        $lines[] = ['account_id' => $cashAcc->id, 'credit' => $gross];

        try {
            DB::transaction(function () use ($s, $courier, $lines, $gross, $fee) {
                $entry = app(Vouchers::class)->draft([
                    'type' => 'receipt', 'transaction_type' => \App\Enums\Accounts\TransactionType::COD_SETTLEMENT->value,
                    'date' => $s['date'], 'description' => "COD settlement" . ($s['parcels'] ? ", {$s['parcels']} parcels" : ''),
                    'party_label' => $courier->name . ' Courier', 'money_account_id' => (int) $s['account_id'], 'lines' => $lines,
                ]);

                CourierCodSettlement::create([
                    'courier_id' => $courier->id, 'settled_at' => $s['date'], 'gross_amount' => $gross, 'fee_amount' => $fee,
                    'net_amount' => round($gross - $fee, 2), 'journal_entry_id' => $entry->id, 'parcels' => $s['parcels'] ?: null,
                ]);
            });
        } catch (RuntimeException $e) {
            $this->addError('settle.gross', $e->getMessage());

            return;
        }

        $this->showSettle = false;
        unset($this->cod);
        $this->dispatch('toast', type: 'success', message: "{$courier->name} settlement saved as draft — review it in Posting Pending.");
    }

    public function render()
    {
        return view('livewire.accounts.receivables', [
            'money' => fn ($v) => Bdt::money($v),
            'dash'  => fn ($v) => $v > 0.5 ? Bdt::money($v) : '—',
            'tel'   => fn (?string $phone) => ($n = BdPhone::normalize($phone)) ? 'tel:' . $n : null,
        ])->layoutData(['active' => 'receivables', 'heading' => 'Receivables', 'crumb' => 'Accounts / Receivables']);
    }
}
