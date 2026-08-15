<?php

namespace App\Livewire\Admin\Purchase;

use App\Models\Supplier;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Suppliers extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $filterStatus = '';

    protected string $paginationTheme = 'tailwind';

    // create
    public bool   $createModal          = false;
    public string $newCode              = '';
    public string $newName              = '';
    public string $newCompanyName       = '';
    public string $newEmail             = '';
    public string $newPhone             = '';
    public string $newAlternativePhone  = '';
    public string $newAddress           = '';
    public string $newNotes             = '';

    // edit
    public bool   $editModal            = false;
    public ?int   $editingId            = null;
    public string $editCode             = '';
    public string $editName             = '';
    public string $editCompanyName      = '';
    public string $editEmail            = '';
    public string $editPhone            = '';
    public string $editAlternativePhone = '';
    public string $editAddress          = '';
    public string $editStatus           = 'active';
    public string $editNotes            = '';

    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }

    public function openCreateModal(): void
    {
        $this->reset([
            'newCode', 'newName', 'newCompanyName', 'newEmail', 'newPhone',
            'newAlternativePhone', 'newAddress', 'newNotes',
        ]);
        $this->newCode = 'SUP-' . str_pad((string) (Supplier::withTrashed()->max('id') + 1), 4, '0', STR_PAD_LEFT);
        $this->resetValidation();
        $this->createModal = true;
    }

    public function createSupplier(): void
    {
        $this->validate([
            'newCode'             => 'required|string|max:100|unique:suppliers,code',
            'newName'             => 'required|string|max:150',
            'newCompanyName'      => 'nullable|string|max:150',
            'newEmail'            => 'nullable|email|max:150',
            'newPhone'            => 'nullable|string|max:20',
            'newAlternativePhone' => 'nullable|string|max:20',
            'newAddress'          => 'nullable|string',
        ]);

        $supplier = Supplier::create([
            'code'               => $this->newCode,
            'name'               => $this->newName,
            'company_name'       => $this->newCompanyName ?: null,
            'email'              => $this->newEmail ?: null,
            'phone'              => $this->newPhone ?: null,
            'alternative_phone'  => $this->newAlternativePhone ?: null,
            'address'            => $this->newAddress ?: null,
            'notes'              => $this->newNotes ?: null,
            'status'             => 'active',
        ]);

        activity('purchase')
            ->causedBy(auth()->user())
            ->performedOn($supplier)
            ->event('created')
            ->log("Supplier \"{$supplier->name}\" was added");

        $this->createModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Supplier added successfully']);
    }

    public function editSupplier(int $id): void
    {
        $supplier = Supplier::findOrFail($id);

        $this->editingId            = $supplier->id;
        $this->editCode             = $supplier->code;
        $this->editName             = $supplier->name;
        $this->editCompanyName      = $supplier->company_name ?? '';
        $this->editEmail            = $supplier->email ?? '';
        $this->editPhone            = $supplier->phone ?? '';
        $this->editAlternativePhone = $supplier->alternative_phone ?? '';
        $this->editAddress          = $supplier->address ?? '';
        $this->editStatus           = $supplier->status;
        $this->editNotes            = $supplier->notes ?? '';
        $this->resetValidation();
        $this->editModal            = true;
    }

    public function updateSupplier(): void
    {
        $supplier = Supplier::findOrFail($this->editingId);

        $this->validate([
            'editCode'             => 'required|string|max:100|unique:suppliers,code,' . $supplier->id,
            'editName'             => 'required|string|max:150',
            'editCompanyName'      => 'nullable|string|max:150',
            'editEmail'            => 'nullable|email|max:150',
            'editPhone'            => 'nullable|string|max:20',
            'editAlternativePhone' => 'nullable|string|max:20',
            'editAddress'          => 'nullable|string',
            'editStatus'           => 'required|in:active,inactive',
        ]);

        $supplier->update([
            'code'               => $this->editCode,
            'name'               => $this->editName,
            'company_name'       => $this->editCompanyName ?: null,
            'email'              => $this->editEmail ?: null,
            'phone'              => $this->editPhone ?: null,
            'alternative_phone'  => $this->editAlternativePhone ?: null,
            'address'            => $this->editAddress ?: null,
            'status'             => $this->editStatus,
            'notes'              => $this->editNotes ?: null,
        ]);

        activity('purchase')
            ->causedBy(auth()->user())
            ->performedOn($supplier)
            ->event('updated')
            ->log("Supplier \"{$supplier->name}\" was updated");

        $this->editModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Supplier updated successfully']);
    }

    public function toggleStatus(int $id): void
    {
        $supplier  = Supplier::findOrFail($id);
        $newStatus = $supplier->status === 'active' ? 'inactive' : 'active';
        $supplier->update(['status' => $newStatus]);

        $this->dispatch('toast', ['type' => 'success', 'message' => "{$supplier->name} is now {$newStatus}"]);
    }

    public function deleteSupplier(int $id): void
    {
        $supplier = Supplier::findOrFail($id);

        if ($supplier->invoices()->exists() || $supplier->purchaseOrders()->exists()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Cannot delete a supplier with existing invoices or purchase orders']);
            return;
        }

        $name = $supplier->name;
        $supplier->delete();

        activity('purchase')
            ->causedBy(auth()->user())
            ->withProperties(['name' => $name])
            ->event('deleted')
            ->log("Supplier \"{$name}\" was deleted");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Supplier deleted']);
    }

    public function render(): mixed
    {
        $suppliers = Supplier::query()
            ->when($this->search, fn($q) => $q->where(fn($s) => $s
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%")
                ->orWhere('company_name', 'like', "%{$this->search}%")
                ->orWhere('phone', 'like', "%{$this->search}%")
            ))
            ->when($this->filterStatus !== '', fn($q) => $q->where('status', $this->filterStatus))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.admin.purchase.suppliers', [
            'suppliers'    => $suppliers,
            'totalCount'   => Supplier::count(),
            'activeCount'  => Supplier::where('status', 'active')->count(),
            'dueTotal'     => Supplier::where('balance', '>', 0)->sum('balance'),
        ])->layout('layouts.admin.admin');
    }
}
