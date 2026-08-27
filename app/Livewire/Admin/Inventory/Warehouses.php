<?php

namespace App\Livewire\Admin\Inventory;

use App\Models\Branch;
use App\Models\Warehouse;
use Livewire\Component;

class Warehouses extends Component
{
    // create/edit modal (shared)
    public bool $formModal = false;
    public ?int $editingId = null;
    public $formCode = '';
    public $formName = '';
    public $formBranchId = '';
    public $formIsDefault = false;

    public function openCreateModal(): void
    {
        $this->reset(['editingId', 'formCode', 'formName', 'formBranchId', 'formIsDefault']);
        $this->resetValidation();
        $this->formModal = true;
    }

    public function openEditModal(int $id): void
    {
        $warehouse = Warehouse::findOrFail($id);

        $this->editingId = $warehouse->id;
        $this->formCode = $warehouse->code;
        $this->formName = $warehouse->name;
        $this->formBranchId = (string) ($warehouse->branch_id ?? '');
        $this->formIsDefault = $warehouse->is_default;
        $this->resetValidation();
        $this->formModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'formCode' => 'required|string|max:50|unique:warehouses,code,' . $this->editingId,
            'formName' => 'required|string|max:150',
            'formBranchId' => 'nullable|integer|exists:branches,id',
        ]);

        if ($this->formIsDefault) {
            Warehouse::where('is_default', true)->update(['is_default' => false]);
        }

        $data = [
            'code' => $this->formCode,
            'name' => $this->formName,
            'branch_id' => $this->formBranchId ?: null,
            'is_default' => $this->formIsDefault,
        ];

        if ($this->editingId) {
            Warehouse::findOrFail($this->editingId)->update($data);
            $message = 'Warehouse updated';
        } else {
            Warehouse::create([...$data, 'status' => 'active']);
            $message = 'Warehouse created';
        }

        $this->formModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => $message]);
    }

    public function toggleStatus(int $id): void
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->update(['status' => $warehouse->status === 'active' ? 'inactive' : 'active']);
    }

    public function render(): mixed
    {
        return view('livewire.admin.inventory.warehouses', [
            'warehouses' => Warehouse::with('branch')->withCount('stocks')->orderByDesc('is_default')->orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(['id', 'name']),
        ])->layout('layouts.admin.admin');
    }
}
