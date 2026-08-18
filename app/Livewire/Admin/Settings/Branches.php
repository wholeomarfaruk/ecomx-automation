<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Branch;
use App\Models\Country;
use App\Models\State;
use Livewire\Component;
use Livewire\WithPagination;

class Branches extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $filterStatus = '';

    protected string $paginationTheme = 'tailwind';

    // create
    public bool   $createModal    = false;
    public string $newCode        = '';
    public string $newName        = '';
    public string $newPhone       = '';
    public string $newEmail       = '';
    public string $newAddress     = '';
    public string $newCountryId   = '';
    public string $newStateId     = '';
    public bool   $newIsDefault   = false;

    // edit
    public bool   $editModal      = false;
    public ?int   $editingId      = null;
    public string $editCode       = '';
    public string $editName       = '';
    public string $editPhone      = '';
    public string $editEmail      = '';
    public string $editAddress    = '';
    public string $editCountryId  = '';
    public string $editStateId    = '';
    public string $editStatus     = 'active';
    public bool   $editIsDefault  = false;

    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }

    public function openCreateModal(): void
    {
        $this->reset(['newCode', 'newName', 'newPhone', 'newEmail', 'newAddress', 'newCountryId', 'newStateId', 'newIsDefault']);
        $this->newCode = 'BR-' . str_pad((string) (Branch::max('id') + 1), 3, '0', STR_PAD_LEFT);
        $this->resetValidation();
        $this->createModal = true;
    }

    public function createBranch(): void
    {
        $this->validate([
            'newCode'      => 'required|string|max:50|unique:branches,code',
            'newName'      => 'required|string|max:150',
            'newPhone'     => 'nullable|string|max:20',
            'newEmail'     => 'nullable|email|max:255',
            'newAddress'   => 'nullable|string',
            'newCountryId' => 'nullable|integer|exists:countries,id',
            'newStateId'   => 'nullable|integer|exists:states,id',
        ]);

        if ($this->newIsDefault) {
            Branch::where('is_default', true)->update(['is_default' => false]);
        }

        $branch = Branch::create([
            'code'       => $this->newCode,
            'name'       => $this->newName,
            'phone'      => $this->newPhone ?: null,
            'email'      => $this->newEmail ?: null,
            'address'    => $this->newAddress ?: null,
            'country_id' => $this->newCountryId ?: null,
            'state_id'   => $this->newStateId ?: null,
            'status'     => 'active',
            'is_default' => $this->newIsDefault,
        ]);

        activity('settings')
            ->causedBy(auth()->user())
            ->performedOn($branch)
            ->event('created')
            ->log("Branch \"{$branch->name}\" was added");

        $this->createModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Branch added successfully']);
    }

    public function editBranch(int $id): void
    {
        $branch = Branch::findOrFail($id);

        $this->editingId     = $branch->id;
        $this->editCode      = $branch->code;
        $this->editName      = $branch->name;
        $this->editPhone     = $branch->phone ?? '';
        $this->editEmail     = $branch->email ?? '';
        $this->editAddress   = $branch->address ?? '';
        $this->editCountryId = (string) ($branch->country_id ?? '');
        $this->editStateId   = (string) ($branch->state_id ?? '');
        $this->editStatus    = $branch->status;
        $this->editIsDefault = $branch->is_default;
        $this->resetValidation();
        $this->editModal     = true;
    }

    public function updateBranch(): void
    {
        $branch = Branch::findOrFail($this->editingId);

        $this->validate([
            'editCode'      => 'required|string|max:50|unique:branches,code,' . $branch->id,
            'editName'      => 'required|string|max:150',
            'editPhone'     => 'nullable|string|max:20',
            'editEmail'     => 'nullable|email|max:255',
            'editAddress'   => 'nullable|string',
            'editCountryId' => 'nullable|integer|exists:countries,id',
            'editStateId'   => 'nullable|integer|exists:states,id',
            'editStatus'    => 'required|in:active,inactive',
        ]);

        if ($this->editIsDefault && ! $branch->is_default) {
            Branch::where('is_default', true)->update(['is_default' => false]);
        }

        $branch->update([
            'code'       => $this->editCode,
            'name'       => $this->editName,
            'phone'      => $this->editPhone ?: null,
            'email'      => $this->editEmail ?: null,
            'address'    => $this->editAddress ?: null,
            'country_id' => $this->editCountryId ?: null,
            'state_id'   => $this->editStateId ?: null,
            'status'     => $this->editStatus,
            'is_default' => $this->editIsDefault,
        ]);

        activity('settings')
            ->causedBy(auth()->user())
            ->performedOn($branch)
            ->event('updated')
            ->log("Branch \"{$branch->name}\" was updated");

        $this->editModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Branch updated successfully']);
    }

    public function toggleStatus(int $id): void
    {
        $branch    = Branch::findOrFail($id);
        $newStatus = $branch->status === 'active' ? 'inactive' : 'active';
        $branch->update(['status' => $newStatus]);

        $this->dispatch('toast', ['type' => 'success', 'message' => "{$branch->name} is now {$newStatus}"]);
    }

    public function deleteBranch(int $id): void
    {
        $branch = Branch::findOrFail($id);

        if ($branch->registers()->exists()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Cannot delete a branch with existing POS registers']);
            return;
        }

        $name = $branch->name;
        $branch->delete();

        activity('settings')
            ->causedBy(auth()->user())
            ->withProperties(['name' => $name])
            ->event('deleted')
            ->log("Branch \"{$name}\" was deleted");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Branch deleted']);
    }

    public function render(): mixed
    {
        $branches = Branch::query()
            ->withCount('registers')
            ->when($this->search, fn ($q) => $q->where(fn ($s) => $s
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%")
            ))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.admin.settings.branches', [
            'branches'    => $branches,
            'countries'   => Country::orderBy('name')->get(['id', 'name']),
            'states'      => State::orderBy('name')->get(['id', 'name', 'country_id']),
            'totalCount'  => Branch::count(),
            'activeCount' => Branch::where('status', 'active')->count(),
        ])->layout('layouts.admin.admin');
    }
}
