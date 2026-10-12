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

    public function mount(?string $month = null): void
    {
        $this->month = $month ?: now()->format('Y-m');
    }

    #[Computed]
    public function run(): ?PayrollRun
    {
        return PayrollRun::with(['items.profile.roles'])
            ->whereDate('month', Carbon::createFromFormat('Y-m', $this->month)->startOfMonth()->toDateString())
            ->first();
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
