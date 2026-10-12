<?php

namespace App\Livewire\Accounts;

use App\Enums\Accounts\JournalEntryStatus;
use App\Models\FixedAsset;
use App\Models\FixedAssetDepreciationEntry;
use App\Models\JournalEntry;
use App\Models\MasterProfile;
use App\Services\Accounts\FixedAssets as AssetBook;
use App\Services\Accounts\Vouchers;
use App\Services\Profiles\LedgerAccounts;
use App\Support\AccountsAccess;
use App\Support\Bdt;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use RuntimeException;

/**
 * Accounts Panel › Fixed Assets — design: FixedAssets.dc.html.
 * Asset register with category chips, a detail panel (book value by year,
 * history), monthly depreciation run (draft), repair (draft expense),
 * sell / dispose (draft gain/loss journal) and date reminders.
 */
#[Title('Fixed Assets')]
#[Layout('layouts.accounts.app')]
class FixedAssets extends Component
{
    #[Url(except: 'All')]
    public string $cat = 'All';

    #[Url(except: false)]
    public bool $disposed = false;

    #[Url(as: 'asset', except: 0)]
    public int $sel = 0;

    public bool $showForm = false;
    public ?int $editId = null;
    public array $form = [];

    public bool $showRepair = false;
    public array $repair = [];

    public bool $showDispose = false;
    public array $dispose = [];

    public function mount(): void
    {
        AccountsAccess::authorize('view');
    }

    #[On('voucher-saved')]
    public function refreshData(): void
    {
        unset($this->assets);
    }

    #[Computed]
    public function assets(): Collection
    {
        return FixedAsset::with(['assignee:id,display_name', 'disposal'])->orderByRaw("CASE WHEN status = 'disposed' THEN 1 ELSE 0 END")->orderBy('code')->orderBy('id')->get();
    }

    #[Computed]
    public function rows(): Collection
    {
        return $this->assets->filter(fn ($a) => ($this->cat === 'All' || ($a->category ?: 'Other') === $this->cat) && ($this->disposed || $a->status !== 'disposed'))->values();
    }

    #[Computed]
    public function current(): ?FixedAsset
    {
        return $this->assets->firstWhere('id', $this->sel) ?? $this->rows->first() ?? $this->assets->first();
    }

    #[Computed]
    public function categories(): array
    {
        $used = $this->assets->map(fn ($a) => $a->category ?: 'Other')->unique();

        return ['All', ...collect(AssetBook::CATEGORIES)->filter(fn ($c) => $used->contains($c) || in_array($c, ['Machinery', 'Furniture', 'Vehicle', 'Electronics'], true))->values()->all()];
    }

    #[Computed]
    public function summary(): array
    {
        $active = $this->assets->where('status', 'active');
        $month = CarbonImmutable::today();
        $book = app(AssetBook::class);
        $last = FixedAssetDepreciationEntry::max('period');
        $run = $book->runFor($month);

        return [
            'cost' => $active->sum(fn ($a) => (float) $a->cost), 'count' => $active->count(),
            'accum' => $active->sum(fn ($a) => (float) $a->accumulated_depreciation),
            'upto' => $last ? 'Up to ' . CarbonImmutable::parse($last)->endOfMonth()->format('d M Y') : 'No run posted yet',
            'book' => $active->sum(fn ($a) => $a->bookValue()),
            'month' => array_sum($book->due($month)) ?: ($run ? (float) $run->amount : 0),
            'monthNote' => $month->format('F') . ' · ' . ($run ? strtolower($run->status->label()) . ' ' . $run->ref() : 'not run yet'),
            'run' => $run,
        ];
    }

    /** Reminders: in repair, dated reminders and warranty ending within 60 days. */
    #[Computed]
    public function reminders(): array
    {
        $out = [];
        $today = CarbonImmutable::today();

        foreach ($this->assets->where('status', 'active') as $a) {
            if ($a->in_repair_since) {
                $cost = (float) JournalEntry::where('source_type', FixedAsset::class)->where('source_id', $a->id)->where('purpose', 'like', 'repair%')
                    ->where('status', JournalEntryStatus::POSTED->value)->whereDate('entry_date', '>=', $a->in_repair_since)->sum('amount');
                $out[] = ['tone' => 'red', 'sort' => 0, 'title' => "{$a->name} in repair since " . $a->in_repair_since->format('d M'),
                    'sub' => $cost > 0 ? 'Repair cost ' . Bdt::money($cost) . ' posted to Repairs' : 'No repair cost posted yet', 'id' => $a->id];
            }
            foreach ((array) $a->reminders as $r) {
                if (empty($r['date']) || empty($r['label'])) {
                    continue;
                }
                $d = CarbonImmutable::parse($r['date']);
                $out[] = ['tone' => $d->lt($today) ? 'red' : ($d->lte($today->addDays(30)) ? 'amber' : 'gray'), 'sort' => $d->timestamp,
                    'title' => "{$r['label']} · " . $d->format('d M Y') . ($d->lt($today) ? ' (overdue)' : ''),
                    'sub' => $a->name . ($a->assignee ? ' · assigned to ' . $a->assignee->display_name : ''), 'id' => $a->id];
            }
            if ($a->warranty_until && $a->warranty_until->between($today, $today->addDays(60))) {
                $out[] = ['tone' => 'amber', 'sort' => $a->warranty_until->timestamp, 'title' => "Warranty ends {$a->warranty_until->format('d M Y')}", 'sub' => $a->name, 'id' => $a->id];
            }
        }

        return collect($out)->sortBy('sort')->values()->all();
    }

    /** History for the selected asset: purchase, depreciation, repairs, disposal. */
    public function history(FixedAsset $a): array
    {
        $h = [['text' => 'Purchased for ' . Bdt::money($a->cost), 'meta' => $a->purchase_date->format('M Y') . ($a->vendor ? " · {$a->vendor}" : '')]];

        $dep = FixedAssetDepreciationEntry::where('fixed_asset_id', $a->id)->selectRaw('COUNT(*) as n, SUM(amount) as total, MAX(period) as last')->first();
        if ($dep && $dep->n) {
            $h[] = ['text' => 'Depreciation posted up to ' . CarbonImmutable::parse($dep->last)->format('M Y') . ' · ' . Bdt::money($dep->total), 'meta' => "Monthly journal · {$dep->n} months"];
        }

        foreach (JournalEntry::where('source_type', FixedAsset::class)->where('source_id', $a->id)->where(fn ($q) => $q->where('purpose', 'like', 'repair%')->orWhere('purpose', 'like', 'disposal%'))
            ->whereNotIn('status', [JournalEntryStatus::VOID->value, JournalEntryStatus::REJECTED->value])->orderBy('entry_date')->get() as $e) {
            $h[] = ['text' => (str_starts_with((string) $e->purpose, 'repair') ? 'Repair · ' : 'Disposal · ') . Bdt::money($e->amount) . ($e->status === JournalEntryStatus::POSTED ? '' : ' (' . strtolower($e->status->label()) . ')'),
                'meta' => $e->entry_date->format('d M Y') . ' · ' . $e->ref()];
        }

        if ($a->assignee || $a->location) {
            $h[] = ['text' => $a->assignee ? 'Assigned to ' . $a->assignee->display_name : 'Kept at ' . $a->location, 'meta' => 'Location'];
        }

        return $h;
    }

    public function setCat(string $c): void
    {
        $this->cat = in_array($c, $this->categories, true) ? $c : 'All';
    }

    public function select(int $id): void
    {
        $this->sel = $id;
    }

    /* ---------------- depreciation ---------------- */

    public function runDepreciation(): void
    {
        AccountsAccess::authorize('create');
        try {
            $entry = app(AssetBook::class)->run(CarbonImmutable::today());
            $this->dispatch('toast', type: 'success', message: "{$entry->ref()} created — post it from Posting Pending to update book values.");
        } catch (RuntimeException $e) {
            $this->dispatch('toast', type: 'warning', message: $e->getMessage());
        }
        unset($this->summary);
    }

    /* ---------------- add / edit ---------------- */

    #[Computed]
    public function cashAccounts(): array
    {
        $v = app(Vouchers::class);

        return collect(app(LedgerAccounts::class)->cashAccounts())->map(fn ($a) => $a + ['balance' => $v->postedBalance($a['id'])])->all();
    }

    #[Computed]
    public function people(): array
    {
        return MasterProfile::withRole('employee')->orderBy('display_name')->pluck('display_name', 'id')->all();
    }

    public function openAdd(): void
    {
        $this->editId = null;
        $this->resetErrorBag();
        $this->form = [
            'name' => '', 'category' => 'Machinery', 'cost' => null, 'purchase_date' => today()->toDateString(), 'life_years' => 5, 'salvage' => 0,
            'location' => '', 'assigned_profile_id' => null, 'vendor' => '', 'warranty_until' => null,
            'reminders' => [['label' => '', 'date' => null]],
            'mode' => 'pay', 'account_id' => $this->cashAccounts[0]['id'] ?? null, 'voucher_ref' => '', 'accumulated' => 0,
        ];
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $a = FixedAsset::findOrFail($id);
        $this->editId = $a->id;
        $this->resetErrorBag();
        $this->form = [
            'name' => $a->name, 'category' => $a->category ?: 'Other', 'cost' => (float) $a->cost, 'purchase_date' => $a->purchase_date->toDateString(),
            'life_years' => round($a->useful_life_months / 12, 1), 'salvage' => (float) $a->salvage_value,
            'location' => $a->location, 'assigned_profile_id' => $a->assigned_profile_id, 'vendor' => $a->vendor,
            'warranty_until' => $a->warranty_until?->toDateString(), 'reminders' => $a->reminders ?: [['label' => '', 'date' => null]],
        ];
        $this->showForm = true;
    }

    public function addReminder(): void
    {
        $this->form['reminders'][] = ['label' => '', 'date' => null];
    }

    public function removeReminder(int $i): void
    {
        unset($this->form['reminders'][$i]);
        $this->form['reminders'] = array_values($this->form['reminders']);
    }

    public function saveAsset(): void
    {
        AccountsAccess::authorize('create');
        $new = ! $this->editId;
        $this->validate([
            'form.name' => 'required|string|max:150', 'form.category' => 'required|in:' . implode(',', AssetBook::CATEGORIES),
            'form.cost' => 'required|numeric|min:1', 'form.purchase_date' => 'required|date', 'form.life_years' => 'required|numeric|min:0.5|max:50',
            'form.salvage' => 'nullable|numeric|min:0|lt:form.cost', 'form.location' => 'nullable|string|max:150',
            'form.assigned_profile_id' => 'nullable|integer|exists:master_profiles,id', 'form.vendor' => 'nullable|string|max:150',
            'form.warranty_until' => 'nullable|date', 'form.reminders.*.label' => 'nullable|string|max:80', 'form.reminders.*.date' => 'nullable|date',
            'form.mode' => $new ? 'required|in:pay,link,none' : 'nullable',
            'form.account_id' => $new && ($this->form['mode'] ?? '') === 'pay' ? 'required|integer|exists:accounts,id' : 'nullable',
            'form.voucher_ref' => $new && ($this->form['mode'] ?? '') === 'link' ? 'required|string' : 'nullable',
            'form.accumulated' => 'nullable|numeric|min:0|lte:form.cost',
        ], [], ['form.name' => 'name', 'form.cost' => 'cost', 'form.life_years' => 'life', 'form.account_id' => 'paid from', 'form.voucher_ref' => 'voucher']);

        $f = $this->form;
        $data = [
            'name' => $f['name'], 'category' => $f['category'], 'cost' => round((float) $f['cost'], 2), 'purchase_date' => $f['purchase_date'],
            'useful_life_months' => max(1, (int) round((float) $f['life_years'] * 12)), 'salvage_value' => (float) ($f['salvage'] ?: 0),
            'location' => $f['location'] ?: null, 'assigned_profile_id' => $f['assigned_profile_id'] ?: null, 'vendor' => $f['vendor'] ?: null,
            'warranty_until' => $f['warranty_until'] ?: null,
            'reminders' => array_values(array_filter($f['reminders'] ?? [], fn ($r) => ! empty($r['label']) && ! empty($r['date']))) ?: null,
        ];

        if (! $new) {
            FixedAsset::whereKey($this->editId)->first()->update($data);
            $this->showForm = false;
            unset($this->assets);
            $this->dispatch('toast', type: 'success', message: 'Asset updated.');

            return;
        }

        $ledgers = app(LedgerAccounts::class);
        $fixed = $ledgers->require(AssetBook::FIXED);
        $entryId = null;
        $message = 'Asset added.';

        if ($f['mode'] === 'link') {
            $linked = JournalEntry::where('voucher_ref', trim($f['voucher_ref']))->orWhere('entry_number', trim($f['voucher_ref']))->first();
            if (! $linked) {
                $this->addError('form.voucher_ref', 'No voucher with that ref.');

                return;
            }
            $entryId = $linked->id;
        }

        $asset = FixedAsset::create($data + [
            'code' => AssetBook::nextCode(), 'category_account_id' => $fixed, 'method' => 'straight_line',
            'accumulated_depreciation' => $f['mode'] === 'none' ? (float) ($f['accumulated'] ?: 0) : 0,
            'status' => 'active', 'journal_entry_id' => $entryId,
        ]);

        if ($f['mode'] === 'pay') {
            $entry = app(Vouchers::class)->draft([
                'type' => 'payment', 'transaction_type' => \App\Enums\Accounts\TransactionType::ASSET_PURCHASE->value, 'date' => $f['purchase_date'],
                'description' => "Fixed asset purchased — {$asset->name}", 'party_label' => $asset->vendor, 'money_account_id' => (int) $f['account_id'],
                'source_type' => FixedAsset::class, 'source_id' => $asset->id, 'purpose' => 'asset_purchase',
                'lines' => [['account_id' => $fixed, 'debit' => $asset->cost], ['account_id' => (int) $f['account_id'], 'credit' => $asset->cost]],
            ]);
            $asset->update(['journal_entry_id' => $entry->id]);
            $message = "Asset {$asset->code} added · purchase voucher {$entry->ref()} waiting in Posting Pending.";
        }

        $this->showForm = false;
        $this->sel = $asset->id;
        unset($this->assets);
        $this->dispatch('toast', type: 'success', message: $message);
    }

    /* ---------------- repair ---------------- */

    public function openRepair(int $id): void
    {
        $this->resetErrorBag();
        $this->repair = ['id' => $id, 'amount' => null, 'account_id' => $this->cashAccounts[0]['id'] ?? null, 'date' => today()->toDateString(), 'note' => '', 'still' => true];
        $this->showRepair = true;
    }

    public function saveRepair(): void
    {
        AccountsAccess::authorize('create');
        $this->validate([
            'repair.amount' => 'required|numeric|min:1', 'repair.account_id' => 'required|integer|exists:accounts,id',
            'repair.date' => 'required|date', 'repair.note' => 'nullable|string|max:150',
        ], [], ['repair.amount' => 'amount', 'repair.account_id' => 'paid from']);

        $a = FixedAsset::findOrFail($this->repair['id']);
        $entry = app(AssetBook::class)->repair($a, round((float) $this->repair['amount'], 2), (int) $this->repair['account_id'], $this->repair['date'], $this->repair['note'] ?: null, (bool) $this->repair['still']);

        $this->showRepair = false;
        unset($this->assets);
        $this->dispatch('toast', type: 'success', message: "{$entry->ref()} created in Repairs — waiting in Posting Pending.");
    }

    public function backInUse(int $id): void
    {
        AccountsAccess::authorize('create');
        FixedAsset::whereKey($id)->update(['in_repair_since' => null]);
        unset($this->assets);
    }

    /* ---------------- dispose ---------------- */

    public function openDispose(int $id): void
    {
        $this->resetErrorBag();
        $this->dispose = ['id' => $id, 'proceeds' => 0, 'account_id' => $this->cashAccounts[0]['id'] ?? null, 'date' => today()->toDateString()];
        $this->showDispose = true;
    }

    public function saveDispose(): void
    {
        AccountsAccess::authorize('create');
        $this->validate([
            'dispose.proceeds' => 'required|numeric|min:0', 'dispose.date' => 'required|date',
            'dispose.account_id' => (float) ($this->dispose['proceeds'] ?? 0) > 0 ? 'required|integer|exists:accounts,id' : 'nullable',
        ], [], ['dispose.proceeds' => 'sale price', 'dispose.account_id' => 'received in']);

        $a = FixedAsset::findOrFail($this->dispose['id']);
        $pending = JournalEntry::where('source_type', FixedAsset::class)->where('source_id', $a->id)->where('purpose', 'like', 'disposal%')
            ->whereIn('status', [JournalEntryStatus::DRAFT->value, JournalEntryStatus::SCHEDULED->value])->first();
        if ($pending) {
            $this->addError('dispose.proceeds', "{$pending->ref()} is already waiting for this asset.");

            return;
        }

        $entry = app(AssetBook::class)->dispose($a, round((float) $this->dispose['proceeds'], 2), $this->dispose['account_id'] ? (int) $this->dispose['account_id'] : null, $this->dispose['date']);

        $this->showDispose = false;
        unset($this->assets);
        $this->dispatch('toast', type: 'success', message: "{$entry->ref()} created — the asset leaves the register when it posts.");
    }

    public function render()
    {
        return view('livewire.accounts.fixed-assets', [
            'money' => fn ($v) => Bdt::money($v),
        ])->layoutData(['active' => 'assets', 'heading' => 'Fixed Assets', 'crumb' => 'Accounts / Fixed Assets']);
    }
}
