<?php

namespace App\Livewire\Admin\Sales;

use App\Models\Branch;
use App\Models\PosRegister;
use Livewire\Component;
use Livewire\WithPagination;

class PosRegisters extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $filterBranch = '';
    public string $filterStatus = '';

    protected string $paginationTheme = 'tailwind';

    // create
    public bool   $createModal  = false;
    public string $newBranchId  = '';
    public string $newCode      = '';
    public string $newName      = '';

    // edit
    public bool   $editModal    = false;
    public ?int   $editingId    = null;
    public string $editBranchId = '';
    public string $editCode     = '';
    public string $editName     = '';
    public string $editStatus   = 'active';

    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingFilterBranch(): void { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }

    public function openCreateModal(): void
    {
        $this->reset(['newBranchId', 'newCode', 'newName']);
        $this->resetValidation();
        $this->createModal = true;
    }

    public function createRegister(): void
    {
        $this->validate([
            'newBranchId' => 'required|integer|exists:branches,id',
            'newCode'     => 'required|string|max:50',
            'newName'     => 'required|string|max:100',
        ]);

        if (PosRegister::where('branch_id', $this->newBranchId)->where('code', $this->newCode)->exists()) {
            $this->addError('newCode', 'This code is already used by another register in this branch.');
            return;
        }

        $register = PosRegister::create([
            'branch_id' => $this->newBranchId,
            'code'      => $this->newCode,
            'name'      => $this->newName,
            'status'    => 'active',
        ]);

        activity('sales')
            ->causedBy(auth()->user())
            ->performedOn($register)
            ->event('created')
            ->log("POS Register \"{$register->name}\" was added");

        $this->createModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Register added successfully']);
    }

    public function editRegister(int $id): void
    {
        $register = PosRegister::findOrFail($id);

        $this->editingId    = $register->id;
        $this->editBranchId = (string) $register->branch_id;
        $this->editCode     = $register->code;
        $this->editName     = $register->name;
        $this->editStatus   = $register->status;
        $this->resetValidation();
        $this->editModal    = true;
    }

    public function updateRegister(): void
    {
        $register = PosRegister::findOrFail($this->editingId);

        $this->validate([
            'editBranchId' => 'required|integer|exists:branches,id',
            'editCode'     => 'required|string|max:50',
            'editName'     => 'required|string|max:100',
            'editStatus'   => 'required|in:active,inactive',
        ]);

        if (PosRegister::where('branch_id', $this->editBranchId)->where('code', $this->editCode)->where('id', '!=', $register->id)->exists()) {
            $this->addError('editCode', 'This code is already used by another register in this branch.');
            return;
        }

        $register->update([
            'branch_id' => $this->editBranchId,
            'code'      => $this->editCode,
            'name'      => $this->editName,
            'status'    => $this->editStatus,
        ]);

        activity('sales')
            ->causedBy(auth()->user())
            ->performedOn($register)
            ->event('updated')
            ->log("POS Register \"{$register->name}\" was updated");

        $this->editModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Register updated successfully']);
    }

    public function toggleStatus(int $id): void
    {
        $register  = PosRegister::findOrFail($id);
        $newStatus = $register->status === 'active' ? 'inactive' : 'active';
        $register->update(['status' => $newStatus]);

        $this->dispatch('toast', ['type' => 'success', 'message' => "{$register->name} is now {$newStatus}"]);
    }

    public function deleteRegister(int $id): void
    {
        $register = PosRegister::findOrFail($id);

        if ($register->sessions()->exists()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Cannot delete a register with existing sessions']);
            return;
        }

        $name = $register->name;
        $register->delete();

        activity('sales')
            ->causedBy(auth()->user())
            ->withProperties(['name' => $name])
            ->event('deleted')
            ->log("POS Register \"{$name}\" was deleted");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Register deleted']);
    }

    public function render(): mixed
    {
        $registers = PosRegister::query()
            ->with('branch')
            ->when($this->search, fn ($q) => $q->where(fn ($s) => $s
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%")
            ))
            ->when($this->filterBranch !== '', fn ($q) => $q->where('branch_id', $this->filterBranch))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.admin.sales.pos-registers', [
            'registers'   => $registers,
            'branches'    => Branch::active()->orderBy('name')->get(['id', 'name']),
            'totalCount'  => PosRegister::count(),
            'activeCount' => PosRegister::where('status', 'active')->count(),
        ])->layout('layouts.admin.admin');
    }
}
