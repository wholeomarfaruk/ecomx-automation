<?php

namespace App\Livewire\Accounts\Profiles;

use App\Livewire\Accounts\Profiles\Concerns\HandlesVoucherModals;
use App\Actions\Profiles\Payroll;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Support\AccountsAccess;
use RuntimeException;
use App\Support\Bdt;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Accounts Panel › Employees › one month's payroll run (spec §9, not designed). */
#[Title('Payroll')]
#[Layout('layouts.accounts.app')]
class PayrollRunPage extends Component
{
    use HandlesVoucherModals;

    public string $month;

    /** The salary sheet while the run is a draft: item id → inputs + include + note. */
    public array $sheet = [];

    /** Sheet header: bonus note, note, due date. */
    public array $head = [];

    public function mount(?string $month = null): void
    {
        $this->month = $month ?: now()->format('Y-m');
        $this->loadSheet();
    }

    #[Computed]
    public function run(): ?PayrollRun
    {
        return PayrollRun::with(['items' => fn ($q) => $q->orderBy('id'), 'items.profile.roles', 'items.profile.advances' => fn ($q) => $q->where('balance', '>', 0)])
            ->whereDate('month', Carbon::createFromFormat('Y-m', $this->month)->startOfMonth()->toDateString())
            ->first();
    }

    protected function loadSheet(): void
    {
        unset($this->run);
        $run = $this->run;
        $this->sheet = [];
        if (! $run?->isDraft()) {
            return;
        }
        foreach ($run->items as $i) {
            $this->sheet[$i->id] = collect(PayrollItem::INPUTS)->mapWithKeys(fn ($k) => [$k => (float) $i->{$k}])->all()
                + ['include' => (bool) $i->include, 'note' => (string) $i->note];
        }
        $this->head = ['bonus_note' => (string) $run->bonus_note, 'note' => (string) $run->note, 'due_on' => $run->due_on?->toDateString()];
    }

    /** Active employees not on this month's sheet yet (joined later, salary set later…). */
    #[Computed]
    public function missing(): array
    {
        $run = $this->run;
        if (! $run?->isDraft()) {
            return [];
        }

        return \App\Models\MasterProfile::withRole('employee')->where('status', 'active')->whereNotIn('id', $run->items->pluck('master_profile_id'))
            ->orderBy('display_name')->pluck('display_name', 'id')->all();
    }

    public function saveSheet(bool $quiet = false): bool
    {
        AccountsAccess::authorize('create');
        $this->validate([
            'head.due_on' => 'nullable|date', 'head.bonus_note' => 'nullable|string|max:120', 'head.note' => 'nullable|string|max:1000',
            'sheet.*.note' => 'nullable|string|max:255',
        ], [], ['head.due_on' => 'due date']);

        try {
            $errors = app(Payroll::class)->saveSheet($this->run, $this->sheet, $this->head);
        } catch (RuntimeException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return false;
        }

        $this->resetErrorBag();
        foreach ($errors as $k => $m) {
            $this->addError($k, $m);
        }
        unset($this->run);
        if (! $quiet) {
            $this->dispatch('toast', type: $errors ? 'warning' : 'success', message: $errors ? 'Saved — fix the highlighted cells before posting.' : 'Salary sheet saved.');
        }

        return ! $errors;
    }

    public function postPayroll(): void
    {
        AccountsAccess::authorize('post');
        if (! $this->saveSheet(true)) {
            $this->dispatch('toast', type: 'warning', message: 'Fix the highlighted cells first.');

            return;
        }

        try {
            $run = app(Payroll::class)->post($this->run);
        } catch (RuntimeException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        }

        $this->loadSheet();
        $this->dispatch('toast', type: 'success', message: $run->month->format('F Y') . ' payroll posted — ' . Bdt::money($run->net) . ' salary payable.');
    }

    public ?string $revertReason = null;

    public bool $showRevert = false;

    public function revertToDraft(): void
    {
        AccountsAccess::authorize('approve');
        try {
            app(Payroll::class)->revertToDraft($this->run, $this->revertReason ?: null);
        } catch (RuntimeException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        }

        $this->showRevert = false;
        $this->revertReason = null;
        $this->loadSheet();
        $this->dispatch('toast', type: 'success', message: 'Back to draft — the journal is reversed and advance recoveries are given back.');
    }

    public function addEmployee(int $profileId): void
    {
        AccountsAccess::authorize('create');
        $run = $this->run;
        if (! $run?->isDraft() || ! array_key_exists($profileId, $this->missing)) {
            return;
        }
        $this->saveSheet(true);
        $profile = \App\Models\MasterProfile::with('roles')->findOrFail($profileId);
        if ((float) ($profile->roleSettings('employee')['salary'] ?? 0) <= 0) {
            $this->dispatch('toast', type: 'warning', message: "{$profile->display_name}-এর salary set করা নেই — 0 দিয়ে যোগ হলো, sheet-এ বসিয়ে দিন।");
        }
        app(Payroll::class)->addLine($run, $profile);
        app(Payroll::class)->refreshTotals($run);
        $this->loadSheet();
    }

    /** Allowances and overtime rate from each employee's last posted payroll. */
    public function copyLastMonth(): void
    {
        $run = $this->run;
        if (! $run?->isDraft()) {
            return;
        }
        $last = PayrollItem::whereHas('run', fn ($q) => $q->where('month', '<', $run->month->toDateString())->where('status', '!=', 'draft'))
            ->whereIn('master_profile_id', $run->items->pluck('master_profile_id'))->orderByDesc('payroll_run_id')->get()->unique('master_profile_id')->keyBy('master_profile_id');
        $n = 0;
        foreach ($run->items as $i) {
            if ($l = $last->get($i->master_profile_id)) {
                foreach (['house_rent', 'medical', 'conveyance', 'other_allowance', 'ot_rate'] as $k) {
                    $this->sheet[$i->id][$k] = (float) $l->{$k};
                }
                $n++;
            }
        }
        $this->dispatch('toast', type: $n ? 'success' : 'info', message: $n ? "{$n} employees: allowances copied from last month — Save to keep." : 'No earlier payroll to copy from.');
    }

    /** Edit one employee's line: [item_id, salary, bonus, note]. */
    public ?array $editing = null;

    /** Edit the whole run: [percent, note, due_on]. */
    public ?array $runEdit = null;

    public function editItem(int $id): void
    {
        $item = $this->run?->items->firstWhere('id', $id);
        if (! $item) {
            return;
        }
        $this->resetErrorBag();
        $this->editing = ['id' => $item->id, 'name' => $item->profile?->display_name, 'salary' => (float) $item->salary, 'bonus' => (float) $item->bonus,
            'deduction' => (float) $item->advance_deduction, 'paid' => (float) $item->paid_amount, 'note' => ''];
    }

    public function saveItem(): void
    {
        AccountsAccess::authorize('create');
        $this->validate([
            'editing.salary' => 'required|numeric|min:0', 'editing.bonus' => 'nullable|numeric|min:0', 'editing.note' => 'nullable|string|max:120',
        ], [], ['editing.salary' => 'salary', 'editing.bonus' => 'bonus']);

        try {
            $entry = app(Payroll::class)->updateItem(PayrollItem::findOrFail($this->editing['id']), (float) $this->editing['salary'], (float) ($this->editing['bonus'] ?: 0), $this->editing['note'] ?: null);
        } catch (RuntimeException $e) {
            $this->addError('editing.salary', $e->getMessage());

            return;
        }

        $this->editing = null;
        unset($this->run);
        $this->dispatch('toast', type: 'success', message: 'Payroll updated' . ($entry ? " — adjustment {$entry->entry_number} posted." : '.'));
    }

    public function editRun(): void
    {
        $run = $this->run;
        if (! $run) {
            return;
        }
        $this->resetErrorBag();
        $gross = (float) $run->gross;
        $this->runEdit = ['percent' => $gross > 0 ? round((float) $run->bonus / $gross * 100, 2) : 0, 'note' => (string) $run->bonus_note, 'due_on' => $run->due_on?->toDateString()];
    }

    /** Bonus % on every employee's salary (each line re-posted as an adjustment), note and due date. */
    public function saveRun(): void
    {
        AccountsAccess::authorize('create');
        $this->validate([
            'runEdit.percent' => 'required|numeric|min:0|max:500', 'runEdit.note' => 'nullable|string|max:120', 'runEdit.due_on' => 'nullable|date',
        ], [], ['runEdit.percent' => 'bonus %', 'runEdit.due_on' => 'due date']);

        $run = $this->run;
        $payroll = app(Payroll::class);
        $pct = (float) $this->runEdit['percent'];
        $failed = [];
        foreach ($run->items as $item) {
            $bonus = round((float) $item->salary * $pct / 100, 2);
            if (abs($bonus - (float) $item->bonus) < 0.01) {
                continue;
            }
            try {
                $payroll->updateItem($item, (float) $item->salary, $bonus, 'bonus ' . rtrim(rtrim(number_format($pct, 2), '0'), '.') . '%');
            } catch (RuntimeException $e) {
                $failed[] = ($item->profile?->display_name ?? 'Employee') . ': ' . $e->getMessage();
            }
        }
        $run->update(['bonus_note' => $this->runEdit['note'] ?: null, 'due_on' => $this->runEdit['due_on'] ?: $run->due_on]);

        $this->runEdit = null;
        unset($this->run);
        $this->dispatch('toast', type: $failed ? 'warning' : 'success', message: $failed ? 'Updated, except — ' . implode('; ', $failed) : 'Payroll updated.');
    }

    public function render()
    {
        return view('livewire.accounts.profiles.payroll-run', [
            'money' => fn ($v) => Bdt::money($v),
            'label' => Carbon::createFromFormat('Y-m', $this->month)->format('F Y'),
        ])->layoutData(['active' => 'employees']);
    }
}
