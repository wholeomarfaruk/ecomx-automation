<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Support\DeviceActivity;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerList extends Component
{
    use WithPagination;

    public string $search              = '';
    public string $filterStatus        = '';
    public string $filterCustomerGroup = '';
    public string $filterOnline        = '';

    protected string $paginationTheme = 'tailwind';

    // create
    public bool   $createModal        = false;
    public string $newCode            = '';
    public string $newFirstName       = '';
    public string $newLastName        = '';
    public string $newEmail           = '';
    public string $newPhone           = '';
    public string $newGender          = '';
    public string $newDateOfBirth     = '';
    public string $newCustomerGroupId = '';

    // edit
    public bool   $editModal            = false;
    public ?int   $editingId            = null;
    public string $editCode             = '';
    public string $editFirstName        = '';
    public string $editLastName         = '';
    public string $editEmail            = '';
    public string $editPhone            = '';
    public string $editGender           = '';
    public string $editDateOfBirth      = '';
    public string $editCustomerGroupId  = '';
    public string $editStatus           = 'active';

    // bulk selection
    public array   $selected      = [];
    public bool    $selectPage     = false;
    public bool    $bulkGroupModal = false;
    public string  $bulkGroupId    = '';

    public function updatingSearch(): void              { $this->resetPage(); $this->clearSelection(); }
    public function updatingFilterStatus(): void        { $this->resetPage(); $this->clearSelection(); }
    public function updatingFilterCustomerGroup(): void { $this->resetPage(); $this->clearSelection(); }
    public function updatingFilterOnline(): void        { $this->resetPage(); $this->clearSelection(); }
    public function updatedSelectPage(bool $value): void
    {
        $ids = $this->currentPageIds();
        $this->selected = $value
            ? array_values(array_unique([...$this->selected, ...$ids]))
            : array_values(array_diff($this->selected, $ids));
    }

    protected function currentPageIds(): array
    {
        return $this->buildQuery()
            ->forPage($this->getPage(), 15)
            ->pluck('id')
            ->all();
    }

    protected function buildQuery()
    {
        $onlineSince = DeviceActivity::threshold();

        return Customer::query()
            ->when($this->search, fn ($q) => $q->where(fn ($s) => $s
                ->where('full_name', 'like', "%{$this->search}%")
                ->orWhere('customer_code', 'like', "%{$this->search}%")
                ->orWhere('phone', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
            ))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterCustomerGroup !== '', fn ($q) => $q->where('customer_group_id', $this->filterCustomerGroup))
            ->when($this->filterOnline === 'online', fn ($q) => $q->whereHas('devices', fn ($d) => $d->where('last_active_at', '>=', $onlineSince)))
            ->when($this->filterOnline === 'offline', fn ($q) => $q->whereDoesntHave('devices', fn ($d) => $d->where('last_active_at', '>=', $onlineSince)))
            ->orderByDesc('id');
    }

    public function clearSelection(): void
    {
        $this->selected    = [];
        $this->selectPage  = false;
    }

    public function openBulkGroupModal(): void
    {
        if (empty($this->selected)) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Select at least one customer first']);
            return;
        }

        $this->bulkGroupId    = '';
        $this->bulkGroupModal = true;
    }

    public function assignBulkGroup(): void
    {
        if (empty($this->selected)) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Select at least one customer first']);
            return;
        }

        $groupId = $this->bulkGroupId ?: null;
        $group   = $groupId ? CustomerGroup::find($groupId) : null;

        $customers = Customer::whereIn('id', $this->selected)->get();

        Customer::whereIn('id', $this->selected)->update(['customer_group_id' => $groupId]);

        activity('customers')
            ->causedBy(auth()->user())
            ->withProperties([
                'customer_ids'   => $this->selected,
                'customer_names' => $customers->pluck('full_name'),
                'group'          => $group?->name ?? 'None',
            ])
            ->event('updated')
            ->log(count($this->selected) . ' customer(s) ' . ($group ? "assigned to group \"{$group->name}\"" : 'removed from their group'));

        $count = count($this->selected);
        $this->bulkGroupModal = false;
        $this->clearSelection();
        $this->dispatch('toast', ['type' => 'success', 'message' => $group ? "{$count} customer(s) added to {$group->name}" : "{$count} customer(s) removed from their group"]);
    }

    public function openCreateModal(): void
    {
        $this->reset([
            'newFirstName', 'newLastName', 'newEmail', 'newPhone',
            'newGender', 'newDateOfBirth', 'newCustomerGroupId',
        ]);
        $this->newCode = 'CUS-' . str_pad((string) (Customer::withTrashed()->max('id') + 1), 5, '0', STR_PAD_LEFT);
        $this->resetValidation();
        $this->createModal = true;
    }

    public function createCustomer(): void
    {
        $this->validate([
            'newCode'            => 'required|string|max:100|unique:customers,customer_code',
            'newFirstName'       => 'required|string|max:150',
            'newLastName'        => 'nullable|string|max:150',
            'newEmail'           => 'nullable|email|max:150',
            'newPhone'           => 'required|string|max:20',
            'newGender'          => 'nullable|string|max:50',
            'newDateOfBirth'     => 'nullable|date',
            'newCustomerGroupId' => 'nullable|integer|exists:customer_groups,id',
        ]);

        $fullName = trim($this->newFirstName . ' ' . $this->newLastName);

        $customer = Customer::create([
            'customer_code'      => $this->newCode,
            'first_name'         => $this->newFirstName,
            'last_name'          => $this->newLastName ?: null,
            'full_name'          => $fullName,
            'email'              => $this->newEmail ?: null,
            'phone'              => $this->newPhone,
            'gender'             => $this->newGender ?: null,
            'date_of_birth'      => $this->newDateOfBirth ?: null,
            'customer_group_id'  => $this->newCustomerGroupId ?: null,
            'status'             => 'active',
        ]);

        activity('customers')
            ->causedBy(auth()->user())
            ->performedOn($customer)
            ->event('created')
            ->log("Customer \"{$customer->full_name}\" was added");

        $this->createModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Customer added successfully']);
    }

    public function editCustomer(int $id): void
    {
        $customer = Customer::findOrFail($id);

        $this->editingId           = $customer->id;
        $this->editCode            = $customer->customer_code;
        $this->editFirstName       = $customer->first_name;
        $this->editLastName        = $customer->last_name ?? '';
        $this->editEmail           = $customer->email ?? '';
        $this->editPhone           = $customer->phone;
        $this->editGender          = $customer->gender ?? '';
        $this->editDateOfBirth     = $customer->date_of_birth?->format('Y-m-d') ?? '';
        $this->editCustomerGroupId = (string) ($customer->customer_group_id ?? '');
        $this->editStatus          = $customer->status;
        $this->resetValidation();
        $this->editModal           = true;
    }

    public function updateCustomer(): void
    {
        $customer = Customer::findOrFail($this->editingId);

        $this->validate([
            'editCode'            => 'required|string|max:100|unique:customers,customer_code,' . $customer->id,
            'editFirstName'       => 'required|string|max:150',
            'editLastName'        => 'nullable|string|max:150',
            'editEmail'           => 'nullable|email|max:150',
            'editPhone'           => 'required|string|max:20',
            'editGender'          => 'nullable|string|max:50',
            'editDateOfBirth'     => 'nullable|date',
            'editCustomerGroupId' => 'nullable|integer|exists:customer_groups,id',
            'editStatus'          => 'required|in:active,inactive,blocked',
        ]);

        $fullName = trim($this->editFirstName . ' ' . $this->editLastName);

        $customer->update([
            'customer_code'      => $this->editCode,
            'first_name'         => $this->editFirstName,
            'last_name'          => $this->editLastName ?: null,
            'full_name'          => $fullName,
            'email'              => $this->editEmail ?: null,
            'phone'              => $this->editPhone,
            'gender'             => $this->editGender ?: null,
            'date_of_birth'      => $this->editDateOfBirth ?: null,
            'customer_group_id'  => $this->editCustomerGroupId ?: null,
            'status'             => $this->editStatus,
        ]);

        activity('customers')
            ->causedBy(auth()->user())
            ->performedOn($customer)
            ->event('updated')
            ->log("Customer \"{$customer->full_name}\" was updated");

        $this->editModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Customer updated successfully']);
    }

    public function toggleStatus(int $id): void
    {
        $customer  = Customer::findOrFail($id);
        $newStatus = $customer->status === 'active' ? 'inactive' : 'active';
        $customer->update(['status' => $newStatus]);

        $this->dispatch('toast', ['type' => 'success', 'message' => "{$customer->full_name} is now {$newStatus}"]);
    }

    public function deleteCustomer(int $id): void
    {
        $customer = Customer::findOrFail($id);

        if ($customer->carts()->exists() || $customer->combos()->exists()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Cannot delete a customer with existing carts or combos']);
            return;
        }

        $name = $customer->full_name;
        $customer->delete();

        activity('customers')
            ->causedBy(auth()->user())
            ->withProperties(['name' => $name])
            ->event('deleted')
            ->log("Customer \"{$name}\" was deleted");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Customer deleted']);
    }

    public function render(): mixed
    {
        $onlineSince = DeviceActivity::threshold();

        $customers = $this->buildQuery()
            ->with('customerGroup')
            ->withMax('devices', 'last_active_at')
            ->paginate(15);

        return view('livewire.admin.customers.customer-list', [
            'customers'      => $customers,
            'customerGroups' => CustomerGroup::orderBy('name')->get(['id', 'name']),
            'totalCount'     => Customer::count(),
            'activeCount'    => Customer::where('status', 'active')->count(),
            'onlineCount'    => Customer::whereHas('devices', fn($d) => $d->where('last_active_at', '>=', $onlineSince))->count(),
            'newThisMonth'   => Customer::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
        ])->layout('layouts.admin.admin');
    }
}
