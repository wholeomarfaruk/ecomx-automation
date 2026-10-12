<?php

namespace App\Livewire\Accounts;

use App\Models\CommissionScheme;
use App\Models\CommissionSettlement;
use App\Models\CommissionTier;
use App\Models\CustomerGroup;
use App\Models\MasterProfile;
use App\Models\Order;
use App\Services\Accounts\Commission as Engine;
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
use RuntimeException;
use Throwable;

/**
 * Accounts Panel › Commission — design: Commission.dc.html (new module).
 * Customer progress, schemes & rules (live recalculation while editing),
 * exclusions (logged with a reason) and settlement.
 */
#[Title('Commission')]
#[Layout('layouts.accounts.app')]
class Commission extends Component
{
    public const TABS = ['progress' => 'Customer progress', 'schemes' => 'Schemes & rules', 'exclusions' => 'Exclusions', 'settlement' => 'Settlement'];

    #[Url(except: 'progress')]
    public string $tab = 'progress';

    #[Url(as: 'scheme', except: 0)]
    public int $schemeId = 0;

    #[Url(except: '')]
    public string $fy = '';

    #[Url(as: 'c', except: 0)]
    public int $sel = 0;

    /** Scheme editor (Schemes tab). */
    public array $edit = [];

    /** Include back / exclude with a reason. */
    public ?array $reasonFor = null;
    public string $reason = '';

    public bool $showExclude = false;
    public array $exclude = ['ref' => '', 'reason' => ''];

    public bool $showAdvance = false;
    public array $advance = [];

    public function mount(): void
    {
        AccountsAccess::authorize('view');
        $this->schemeId = $this->schemeId ?: (int) (CommissionScheme::where('status', 'active')->orderBy('id')->value('id') ?? CommissionScheme::orderBy('id')->value('id') ?? 0);
        $this->loadEdit();
    }

    #[On('voucher-saved')]
    public function refreshData(): void
    {
        unset($this->calc);
    }

    /* ---------------- scheme / year ---------------- */

    #[Computed]
    public function schemes(): Collection
    {
        return CommissionScheme::with('tiers')->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'draft' THEN 1 ELSE 2 END")->orderBy('id')->get();
    }

    #[Computed]
    public function scheme(): ?CommissionScheme
    {
        return $this->schemes->firstWhere('id', $this->schemeId);
    }

    #[Computed]
    public function years(): array
    {
        $fy = AccountsPeriod::fyStart(CarbonImmutable::today());

        return collect(range(0, 2))->mapWithKeys(fn ($i) => [$i === 0 ? '' : (string) $fy->subYears($i)->year => AccountsPeriod::fyLabel($fy->subYears($i)) . ($i === 0 ? ' (' . $fy->format('M') . '–' . $fy->addYear()->subDay()->format('M') . ')' : '')])->all();
    }

    /** The date the numbers are "as of": today, or the end of a past FY. */
    public function at(): CarbonImmutable
    {
        if (! $this->fy) {
            return CarbonImmutable::today();
        }
        $start = AccountsPeriod::fyStart(CarbonImmutable::today())->setYear((int) $this->fy);

        return AccountsPeriod::fyEnd($start);
    }

    public function pickScheme(int $id): void
    {
        $this->schemeId = $id;
        $this->sel = 0;
        $this->loadEdit();
        unset($this->calc, $this->scheme);
    }

    public function setTab(string $t): void
    {
        $this->tab = array_key_exists($t, self::TABS) ? $t : 'progress';
    }

    /* ---------------- calculation ---------------- */

    /** The scheme as being edited — progress recalculates live with unsaved rule changes. */
    #[Computed]
    public function working(): ?CommissionScheme
    {
        $s = $this->scheme;
        if (! $s || ($this->edit['id'] ?? null) !== $s->id) {
            return $s;
        }

        $w = $s->replicate();
        $w->id = $s->id;
        $w->fill($this->attributes());
        $w->setRelation('tiers', collect($this->edit['tiers'])->map(fn ($t) => new CommissionTier(['from_amount' => (float) ($t['from'] ?: 0), 'to_amount' => $t['to'] === '' || $t['to'] === null ? null : (float) $t['to'], 'rate' => (float) ($t['rate'] ?: 0)])));

        return $w;
    }

    #[Computed]
    public function calc(): ?array
    {
        if (! $this->working) {
            return null;
        }

        try {
            return app(Engine::class)->compute($this->working, $this->at());
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    public function dirty(): bool
    {
        $s = $this->scheme;

        return $s && ($this->edit['id'] ?? null) === $s->id && ($this->attributes() != $this->attributesOf($s) || $this->tiersOf($s) != $this->normalTiers());
    }

    #[Computed]
    public function summary(): array
    {
        $rows = $this->calc['rows'] ?? collect();
        $s = $this->working;
        $prevStart = $s ? Engine::period($s, $this->at())[0]->subDay() : null;
        $last = $s ? CommissionSettlement::where('commission_scheme_id', $s->id)->whereDate('period_end', '<=', $prevStart?->toDateString() ?? '1900-01-01')
            ->whereDate('period_end', '>=', $prevStart?->subYear()->toDateString() ?? '1900-01-01')->get() : collect();

        return [
            'eligible' => $rows->sum('eligible'), 'customers' => $rows->count(), 'excluded' => $rows->sum('excluded'),
            'accrued' => $rows->sum('accrue'), 'projected' => $rows->sum('projected'),
            'modeNote' => ($s?->tier_mode === 'slab' ? 'Slab tiers' : 'Whole-amount tiers') . ' · hold বাদে',
            'last' => $last->sum('amount'), 'lastCount' => $last->pluck('master_profile_id')->unique()->count(),
            'lastLabel' => $prevStart ? AccountsPeriod::fyLabel($prevStart) : '',
        ];
    }

    #[Computed]
    public function current(): ?array
    {
        $rows = $this->calc['rows'] ?? collect();

        return $rows->firstWhere('profile_id', $this->sel) ?? $rows->first();
    }

    public function select(int $pid): void
    {
        $this->sel = $pid;
    }

    /* ---------------- scheme editor ---------------- */

    protected function attributesOf(CommissionScheme $s): array
    {
        return [
            'name' => $s->name, 'type' => $s->type, 'basis' => $s->basis, 'period' => $s->period,
            'starts_on' => $s->starts_on?->toDateString(), 'ends_on' => $s->ends_on?->toDateString(),
            'who' => $s->who, 'who_value' => array_values((array) $s->who_value), 'tier_mode' => $s->tier_mode,
            'rules' => collect(array_keys(CommissionScheme::RULES))->mapWithKeys(fn ($k) => [$k => $s->rule($k)])->all(),
            'paid_only' => (bool) $s->paid_only, 'hold_days' => $s->hold_days, 'returns_reverse' => (bool) $s->returns_reverse,
            'settlement_method' => $s->settlement_method, 'accounting' => $s->accounting,
        ];
    }

    protected function tiersOf(CommissionScheme $s): array
    {
        return $s->tiers->map(fn ($t) => ['from' => (float) $t->from_amount, 'to' => $t->to_amount === null ? null : (float) $t->to_amount, 'rate' => (float) $t->rate])->values()->all();
    }

    protected function normalTiers(): array
    {
        return collect($this->edit['tiers'] ?? [])->map(fn ($t) => ['from' => (float) ($t['from'] ?: 0), 'to' => $t['to'] === '' || $t['to'] === null ? null : (float) $t['to'], 'rate' => (float) ($t['rate'] ?: 0)])->values()->all();
    }

    /** Editor values as scheme attributes. */
    protected function attributes(): array
    {
        $e = $this->edit;

        return [
            'name' => $e['name'], 'type' => $e['type'], 'basis' => $e['basis'], 'period' => $e['period'],
            'starts_on' => $e['period'] === 'custom' ? ($e['starts_on'] ?: null) : null, 'ends_on' => $e['period'] === 'custom' ? ($e['ends_on'] ?: null) : null,
            'who' => $e['who'], 'who_value' => array_values(array_filter((array) $e['who_value'], fn ($v) => $v !== '' && $v !== null)), 'tier_mode' => $e['tier_mode'],
            'rules' => collect($e['rules'])->map(fn ($r) => ['on' => (bool) $r['on'], 'value' => (float) ($r['value'] ?? 0)])->all(),
            'paid_only' => (bool) $e['paid_only'], 'hold_days' => $e['hold_on'] ? max(1, (int) $e['hold_days']) : null, 'returns_reverse' => (bool) $e['returns_reverse'],
            'settlement_method' => $e['settlement_method'], 'accounting' => $e['accounting'],
        ];
    }

    protected function loadEdit(): void
    {
        $s = $this->scheme;
        if (! $s) {
            $this->newScheme(false);

            return;
        }

        $a = $this->attributesOf($s);
        $this->edit = $a + ['id' => $s->id, 'status' => $s->status, 'hold_on' => $s->hold_days !== null, 'hold_days' => $s->hold_days ?? 60,
            'tiers' => $this->tiersOf($s) ?: [['from' => 0, 'to' => null, 'rate' => 0]]];
    }

    public function newScheme(bool $goTab = true): void
    {
        $this->edit = [
            'id' => null, 'status' => 'draft', 'name' => '', 'type' => 'rebate', 'basis' => 'net_sales', 'period' => 'yearly', 'starts_on' => null, 'ends_on' => null,
            'who' => 'all', 'who_value' => [], 'tier_mode' => 'whole',
            'rules' => collect(CommissionScheme::RULES)->map(fn ($r) => ['on' => true, 'value' => $r[2] ?? 0])->all(),
            'paid_only' => true, 'hold_on' => true, 'hold_days' => 60, 'returns_reverse' => true, 'settlement_method' => 'credit_note', 'accounting' => 'rebate',
            'tiers' => [['from' => 0, 'to' => 500000, 'rate' => 0], ['from' => 500000, 'to' => 1000000, 'rate' => 2], ['from' => 1000000, 'to' => 2000000, 'rate' => 3], ['from' => 2000000, 'to' => null, 'rate' => 4]],
        ];
        if ($goTab) {
            $this->tab = 'schemes';
        }
    }

    public function editScheme(int $id): void
    {
        $this->pickScheme($id);
    }

    public function addTier(): void
    {
        $tiers = $this->edit['tiers'];
        $last = end($tiers) ?: ['from' => 0, 'to' => null, 'rate' => 0];
        $from = $last['to'] ?: ((float) $last['from'] + 500000);
        $this->edit['tiers'][array_key_last($tiers)]['to'] = $from;
        $this->edit['tiers'][] = ['from' => $from, 'to' => null, 'rate' => (float) $last['rate'] + 1];
    }

    public function removeTier(int $i): void
    {
        if (count($this->edit['tiers']) > 1) {
            unset($this->edit['tiers'][$i]);
            $this->edit['tiers'] = array_values($this->edit['tiers']);
            $this->edit['tiers'][array_key_last($this->edit['tiers'])]['to'] = null;
        }
    }

    public function updatedEdit(): void
    {
        unset($this->working, $this->calc, $this->summary, $this->current);
    }

    #[Computed]
    public function groups(): array
    {
        return CustomerGroup::orderBy('name')->pluck('name', 'id')->all();
    }

    #[Computed]
    public function customerOptions(): array
    {
        return MasterProfile::withRole('customer')->orderBy('display_name')->limit(2000)->pluck('display_name', 'id')->all();
    }

    public function saveScheme(bool $activate = true): void
    {
        AccountsAccess::authorize('settings');
        $this->validate([
            'edit.name' => 'required|string|max:120', 'edit.type' => 'required|in:' . implode(',', array_keys(Engine::TYPES)),
            'edit.basis' => 'required|in:' . implode(',', array_keys(Engine::BASES)), 'edit.period' => 'required|in:' . implode(',', array_keys(Engine::PERIODS)),
            'edit.starts_on' => 'required_if:edit.period,custom|nullable|date', 'edit.ends_on' => 'required_if:edit.period,custom|nullable|date|after:edit.starts_on',
            'edit.who' => 'required|in:' . implode(',', array_keys(Engine::WHO)), 'edit.tier_mode' => 'required|in:whole,slab',
            'edit.tiers' => 'required|array|min:1', 'edit.tiers.*.from' => 'required|numeric|min:0', 'edit.tiers.*.to' => 'nullable|numeric|min:0',
            'edit.tiers.*.rate' => 'required|numeric|min:0|max:100', 'edit.hold_days' => 'nullable|integer|min:1|max:720',
            'edit.settlement_method' => 'required|in:' . implode(',', array_keys(Engine::METHODS)), 'edit.accounting' => 'required|in:rebate,expense',
        ], [], ['edit.name' => 'scheme name', 'edit.tiers.*.from' => 'from', 'edit.tiers.*.rate' => 'rate', 'edit.ends_on' => 'end date']);

        $tiers = collect($this->normalTiers())->sortBy('from')->values();
        foreach ($tiers as $i => $t) {
            if ($i > 0 && $t['from'] < (float) ($tiers[$i - 1]['from'])) {
                $this->addError('edit.tiers', 'Tiers must go up.');

                return;
            }
        }

        $s = DB::transaction(function () use ($activate, $tiers) {
            $s = $this->edit['id'] ? CommissionScheme::findOrFail($this->edit['id']) : new CommissionScheme(['created_by' => auth()->id()]);
            $s->fill($this->attributes() + ['status' => $activate ? 'active' : ($s->status === 'active' ? 'active' : 'draft')])->save();
            $s->tiers()->delete();
            foreach ($tiers as $t) {
                $s->tiers()->create(['from_amount' => $t['from'], 'to_amount' => $t['to'], 'rate' => $t['rate']]);
            }

            return $s;
        });

        unset($this->schemes, $this->scheme);
        $this->pickScheme($s->id);
        $this->dispatch('toast', type: 'success', message: $activate ? "{$s->name} saved and active." : "{$s->name} saved as draft.");
    }

    public function endScheme(): void
    {
        AccountsAccess::authorize('settings');
        $this->scheme?->update(['status' => 'ended']);
        unset($this->schemes, $this->scheme);
        $this->loadEdit();
    }

    /* ---------------- exclusions ---------------- */

    /** Excluded invoices / lines plus rows included back by hand. */
    #[Computed]
    public function exList(): Collection
    {
        $s = $this->working;
        if (! $s) {
            return collect();
        }
        $names = ($this->calc['rows'] ?? collect())->pluck('name', 'profile_id');
        $rows = ($this->calc['exclusions'] ?? collect())->map(fn ($e) => $e + ['name' => $names[$e['profile_id']] ?? '—', 'back' => false]);

        $back = \App\Models\CommissionExclusion::with(['order.customer.masterProfile:id,display_name', 'user:id,name'])->where('commission_scheme_id', $s->id)->where('included_back', true)->latest()->get()
            ->map(fn ($e) => [
                'order_id' => $e->order_id, 'item_id' => $e->order_item_id, 'ref' => 'INV-' . str_pad((string) $e->order_id, 6, '0', STR_PAD_LEFT),
                'date' => $e->order?->placed_at ?? $e->order?->created_at, 'profile_id' => $e->order?->customer?->master_profile_id,
                'name' => $e->order?->customer?->masterProfile?->display_name ?? '—', 'what' => $e->order_item_id ? 'Line item' : 'Whole invoice',
                'rule' => null, 'src' => 'Manual', 'reason' => 'Included back: ' . $e->reason . ($e->user ? " — {$e->user->name}" : ''), 'amount' => null, 'out' => false, 'back' => true,
            ]);

        return $rows->concat($back)->values();
    }

    public function askReason(string $action, int $orderId, ?int $itemId = null): void
    {
        $this->reasonFor = ['action' => $action, 'order_id' => $orderId, 'item_id' => $itemId];
        $this->reason = '';
        $this->resetErrorBag();
    }

    public function saveReason(): void
    {
        AccountsAccess::authorize('create');
        $this->validate(['reason' => 'required|string|min:3|max:200'], [], ['reason' => 'reason']);
        $r = $this->reasonFor;
        $engine = app(Engine::class);
        $r['action'] === 'back'
            ? $engine->includeBack($this->scheme, $r['order_id'], $r['item_id'], $this->reason)
            : $engine->exclude($this->scheme, $r['order_id'], $r['item_id'], $this->reason);

        $this->reasonFor = null;
        unset($this->calc, $this->exList);
        $this->dispatch('toast', type: 'success', message: $r['action'] === 'back' ? 'Included back — logged.' : 'Excluded — logged.');
    }

    public function saveExclude(): void
    {
        AccountsAccess::authorize('create');
        $this->validate(['exclude.ref' => 'required|string|max:30', 'exclude.reason' => 'required|string|min:3|max:200'], [], ['exclude.ref' => 'invoice', 'exclude.reason' => 'reason']);
        $id = (int) preg_replace('/\D/', '', $this->exclude['ref']);
        if (! $id || ! Order::whereKey($id)->exists()) {
            $this->addError('exclude.ref', 'No invoice with that number.');

            return;
        }

        app(Engine::class)->exclude($this->scheme, $id, null, $this->exclude['reason']);
        $this->showExclude = false;
        $this->exclude = ['ref' => '', 'reason' => ''];
        unset($this->calc, $this->exList);
        $this->dispatch('toast', type: 'success', message: 'Invoice excluded — logged.');
    }

    /* ---------------- settlement ---------------- */

    #[Computed]
    public function settleRows(): Collection
    {
        return ($this->calc['rows'] ?? collect())->filter(fn ($r) => $r['tier'] > 0 || $r['amount'] > 0 || $r['hold']);
    }

    public function periodEnded(): bool
    {
        return isset($this->calc['end']) && CarbonImmutable::today()->gt($this->calc['end']);
    }

    public function runSettlement(): void
    {
        AccountsAccess::authorize('create');
        if (! $this->periodEnded()) {
            $this->dispatch('toast', type: 'warning', message: 'Settlement runs after the period ends.');

            return;
        }

        $s = $this->scheme;
        $made = [];
        foreach ($this->settleRows as $r) {
            $due = round($r['amount'] - $r['settled'], 2);
            if ($r['hold'] || $due < 1) {
                continue;
            }
            try {
                $made[] = app(Engine::class)->settle($s, $r['profile_id'], $due, $s->settlement_method, null, $this->calc['start'], $this->calc['end'], $r['rate'])->ref();
            } catch (RuntimeException $e) {
                $this->dispatch('toast', type: 'warning', message: "{$r['name']}: {$e->getMessage()}");
            }
        }

        unset($this->calc);
        $this->dispatch('toast', type: 'success', message: count($made) . ' settlement draft(s) created' . ($made ? ': ' . implode(', ', $made) : '') . '.');
    }

    public function openAdvance(?int $pid = null): void
    {
        $r = ($this->calc['rows'] ?? collect())->firstWhere('profile_id', $pid ?? $this->current['profile_id'] ?? 0);
        $this->resetErrorBag();
        $this->advance = ['profile_id' => $r['profile_id'] ?? null, 'amount' => $r ? max(0, round($r['accrue'] - $r['settled'])) : null,
            'method' => $this->scheme?->settlement_method === 'cash' ? 'cash' : 'credit_note', 'account_id' => app(LedgerAccounts::class)->cashAccounts()[0]['id'] ?? null];
        $this->showAdvance = true;
    }

    public function saveAdvance(): void
    {
        AccountsAccess::authorize('create');
        $this->validate([
            'advance.profile_id' => 'required|integer|exists:master_profiles,id', 'advance.amount' => 'required|numeric|min:1',
            'advance.method' => 'required|in:credit_note,deduct_due,cash', 'advance.account_id' => 'required_if:advance.method,cash|nullable|integer|exists:accounts,id',
        ], [], ['advance.profile_id' => 'customer', 'advance.amount' => 'amount', 'advance.account_id' => 'pay from']);

        try {
            $entry = app(Engine::class)->settle($this->scheme, (int) $this->advance['profile_id'], round((float) $this->advance['amount'], 2), $this->advance['method'],
                $this->advance['account_id'] ? (int) $this->advance['account_id'] : null, $this->calc['start'], $this->calc['end'], 0, true);
        } catch (RuntimeException $e) {
            $this->addError('advance.amount', $e->getMessage());

            return;
        }

        $this->showAdvance = false;
        unset($this->calc);
        $this->dispatch('toast', type: 'success', message: "{$entry->ref()} created — waiting in Posting Pending.");
    }

    #[Computed]
    public function history(): Collection
    {
        $s = $this->scheme;
        if (! $s || ! isset($this->calc['start'])) {
            return collect();
        }

        return CommissionSettlement::with(['profile:id,display_name', 'journalEntry:id,voucher_ref,entry_number,status'])->where('commission_scheme_id', $s->id)
            ->whereDate('period_end', '<', $this->calc['start']->toDateString())->orderByDesc('period_end')->limit(30)->get();
    }

    /* ---------------- statements ---------------- */

    public function sendStatement(?int $pid = null): void
    {
        AccountsAccess::authorize('create');
        $sms = app(\App\Actions\Profiles\SendProfileSms::class);
        $engine = app(Engine::class);
        $sent = 0;

        foreach (($this->calc['rows'] ?? collect())->when($pid, fn ($c) => $c->where('profile_id', $pid)) as $r) {
            if ($r['eligible'] <= 0 || ! $r['phone']) {
                continue;
            }
            $p = MasterProfile::find($r['profile_id']);
            $sent += (int) rescue(fn () => $sms->send($p, $engine->statementText($this->scheme, $r), 'sms'), false, false);
        }

        $this->dispatch('toast', type: $sent ? 'success' : 'warning', message: $sent ? "Statement SMS sent to {$sent} customer(s)." : 'No SMS sent — check phone numbers / SMS settings.');
    }

    public function render()
    {
        return view('livewire.accounts.commission', [
            'money' => fn ($v) => Bdt::money($v),
        ])->layoutData(['active' => 'commission', 'heading' => 'Commission', 'crumb' => 'Accounts / Commission']);
    }
}
