<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use App\Models\CustomerGroup;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerGroups extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $filterStatus = '';

    // create
    public bool   $createModal              = false;
    public string $newName                  = '';
    public string $newDescription           = '';
    public string $newDiscountType          = '';
    public string $newDiscountValue         = '0';
    public string $newMinimumOrderAmount    = '0';
    public string $newMinimumOrderQty       = '0';
    public bool   $newAllowCredit           = false;
    public bool   $newRewardPointsEnabled   = true;
    public bool   $newIsDefault             = false;
    public bool   $newIsActive              = true;

    // edit
    public bool   $editModal                = false;
    public ?int   $editingId                = null;
    public string $editName                 = '';
    public string $editDescription          = '';
    public string $editDiscountType         = '';
    public string $editDiscountValue        = '0';
    public string $editMinimumOrderAmount   = '0';
    public string $editMinimumOrderQty      = '0';
    public bool   $editAllowCredit          = false;
    public bool   $editRewardPointsEnabled  = true;
    public bool   $editIsDefault            = false;
    public bool   $editIsActive             = true;
    public bool   $editingWasDefault        = false;

    // add customers to group
    public bool   $addCustomersModal   = false;
    public ?int   $assigningGroupId    = null;
    public string $assigningGroupName  = '';
    public string $customerSearch      = '';
    public array  $selectedCustomerIds = [];

    public function updatingSearch(): void       { }
    public function updatingFilterStatus(): void { }
    public function updatingCustomerSearch(): void { $this->resetPage('candidatesPage'); }

    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            CustomerGroup::where('id', $id)->update(['sort_order' => $index + 1]);
        }
    }

    public function openCreateModal(): void
    {
        $this->reset([
            'newName', 'newDescription', 'newDiscountType', 'newDiscountValue',
            'newMinimumOrderAmount', 'newMinimumOrderQty', 'newAllowCredit',
            'newIsDefault',
        ]);
        $this->newDiscountValue      = '0';
        $this->newMinimumOrderAmount = '0';
        $this->newMinimumOrderQty    = '0';
        $this->newRewardPointsEnabled = true;
        $this->newIsActive            = true;
        $this->resetValidation();
        $this->createModal = true;
    }

    public function createGroup(): void
    {
        $this->validate([
            'newName'               => 'required|string|max:100|unique:customer_groups,name',
            'newDescription'        => 'nullable|string',
            'newDiscountType'       => 'nullable|in:percentage,fixed',
            'newDiscountValue'      => 'required|numeric|min:0',
            'newMinimumOrderAmount' => 'required|numeric|min:0',
            'newMinimumOrderQty'    => 'required|integer|min:0',
        ]);

        $maxOrder = CustomerGroup::max('sort_order') ?? 0;

        $group = CustomerGroup::create([
            'name'                  => $this->newName,
            'description'           => $this->newDescription ?: null,
            'discount_type'         => $this->newDiscountType ?: null,
            'discount_value'        => $this->newDiscountValue,
            'minimum_order_amount'  => $this->newMinimumOrderAmount,
            'minimum_order_qty'     => $this->newMinimumOrderQty,
            'allow_credit'          => $this->newAllowCredit,
            'reward_points_enabled' => $this->newRewardPointsEnabled,
            'is_default'            => false,
            'is_active'             => $this->newIsActive,
            'sort_order'            => $maxOrder + 1,
        ]);

        if ($this->newIsDefault) {
            $this->makeDefault($group);
        }

        activity('customers')
            ->causedBy(auth()->user())
            ->performedOn($group)
            ->withProperties(['name' => $group->name])
            ->event('created')
            ->log("Customer group \"{$group->name}\" was added");

        $this->createModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Customer group added successfully']);
    }

    public function editGroup(int $id): void
    {
        $group = CustomerGroup::findOrFail($id);

        $this->editingId               = $group->id;
        $this->editName                = $group->name;
        $this->editDescription         = $group->description ?? '';
        $this->editDiscountType        = $group->discount_type ?? '';
        $this->editDiscountValue       = (string) $group->discount_value;
        $this->editMinimumOrderAmount  = (string) $group->minimum_order_amount;
        $this->editMinimumOrderQty     = (string) $group->minimum_order_qty;
        $this->editAllowCredit         = $group->allow_credit;
        $this->editRewardPointsEnabled = $group->reward_points_enabled;
        $this->editIsDefault           = $group->is_default;
        $this->editIsActive            = $group->is_active;
        $this->editingWasDefault       = $group->is_default;
        $this->resetValidation();
        $this->editModal               = true;
    }

    public function updateGroup(): void
    {
        $group = CustomerGroup::findOrFail($this->editingId);

        $this->validate([
            'editName'               => 'required|string|max:100|unique:customer_groups,name,' . $group->id,
            'editDescription'        => 'nullable|string',
            'editDiscountType'       => 'nullable|in:percentage,fixed',
            'editDiscountValue'      => 'required|numeric|min:0',
            'editMinimumOrderAmount' => 'required|numeric|min:0',
            'editMinimumOrderQty'    => 'required|integer|min:0',
        ]);

        // A group can't be deactivated while it's the default — there must
        // always be exactly one active default for customers with no
        // explicit group to fall back to.
        if ($group->is_default && ! $this->editIsActive) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'The default group must stay active. Set another group as default first.']);
            return;
        }

        $group->update([
            'name'                  => $this->editName,
            'description'           => $this->editDescription ?: null,
            'discount_type'         => $this->editDiscountType ?: null,
            'discount_value'        => $this->editDiscountValue,
            'minimum_order_amount'  => $this->editMinimumOrderAmount,
            'minimum_order_qty'     => $this->editMinimumOrderQty,
            'allow_credit'          => $this->editAllowCredit,
            'reward_points_enabled' => $this->editRewardPointsEnabled,
            'is_active'             => $this->editIsActive,
        ]);

        if ($this->editIsDefault && ! $group->is_default) {
            $this->makeDefault($group);
        }

        activity('customers')
            ->causedBy(auth()->user())
            ->performedOn($group)
            ->event('updated')
            ->log("Customer group \"{$group->name}\" was updated");

        $this->editModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Customer group updated successfully']);
    }

    /**
     * Exactly one group may be default at a time — un-defaults every other
     * group before marking this one, in the same transaction.
     */
    protected function makeDefault(CustomerGroup $group): void
    {
        CustomerGroup::where('id', '!=', $group->id)->update(['is_default' => false]);
        $group->update(['is_default' => true]);
    }

    public function toggleActive(int $id): void
    {
        $group = CustomerGroup::findOrFail($id);

        if ($group->is_default && $group->is_active) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'The default group must stay active. Set another group as default first.']);
            return;
        }

        $group->update(['is_active' => ! $group->is_active]);

        $this->dispatch('toast', ['type' => 'success', 'message' => $group->is_active ? 'Group activated' : 'Group deactivated']);
    }

    public function deleteGroup(int $id): void
    {
        $group = CustomerGroup::findOrFail($id);

        if ($group->is_default) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Cannot delete the default customer group']);
            return;
        }

        if ($group->customers()->exists()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Cannot delete a group with customers assigned to it']);
            return;
        }

        $name = $group->name;
        $group->delete();

        activity('customers')
            ->causedBy(auth()->user())
            ->withProperties(['name' => $name])
            ->event('deleted')
            ->log("Customer group \"{$name}\" was deleted");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Customer group deleted']);
    }

    public function openAddCustomersModal(int $groupId): void
    {
        $group = CustomerGroup::findOrFail($groupId);

        $this->assigningGroupId   = $group->id;
        $this->assigningGroupName = $group->name;
        $this->customerSearch     = '';
        $this->selectedCustomerIds = [];
        $this->resetPage('candidatesPage');
        $this->addCustomersModal  = true;
    }

    public function toggleCandidate(int $customerId): void
    {
        if (in_array($customerId, $this->selectedCustomerIds, true)) {
            $this->selectedCustomerIds = array_values(array_diff($this->selectedCustomerIds, [$customerId]));
        } else {
            $this->selectedCustomerIds[] = $customerId;
        }
    }

    /**
     * Selects every candidate matching the current search — not just the
     * visible page — so "Select All" behaves like a real bulk action even
     * when there are more results than fit on one page.
     */
    public function selectAllCandidates(): void
    {
        $this->selectedCustomerIds = $this->candidateQuery()->pluck('id')->all();
    }

    public function clearCandidateSelection(): void
    {
        $this->selectedCustomerIds = [];
    }

    protected function candidateQuery()
    {
        return Customer::query()
            ->where(fn ($q) => $q->whereNull('customer_group_id')->orWhere('customer_group_id', '!=', $this->assigningGroupId))
            ->when($this->customerSearch, fn ($q) => $q->where(fn ($s) => $s
                ->where('full_name', 'like', "%{$this->customerSearch}%")
                ->orWhere('customer_code', 'like', "%{$this->customerSearch}%")
                ->orWhere('phone', 'like', "%{$this->customerSearch}%")
                ->orWhere('email', 'like', "%{$this->customerSearch}%")
            ));
    }

    public function assignSelectedCustomers(): void
    {
        if (empty($this->selectedCustomerIds)) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Select at least one customer']);
            return;
        }

        $group = CustomerGroup::findOrFail($this->assigningGroupId);

        $customers = Customer::whereIn('id', $this->selectedCustomerIds)->get();

        Customer::whereIn('id', $this->selectedCustomerIds)->update(['customer_group_id' => $group->id]);

        activity('customers')
            ->causedBy(auth()->user())
            ->performedOn($group)
            ->withProperties(['customer_ids' => $this->selectedCustomerIds, 'customer_names' => $customers->pluck('full_name')])
            ->event('updated')
            ->log(count($this->selectedCustomerIds) . " customer(s) added to group \"{$group->name}\"");

        $count = count($this->selectedCustomerIds);
        $this->selectedCustomerIds = [];
        $this->dispatch('toast', ['type' => 'success', 'message' => "{$count} customer(s) added to {$group->name}"]);
    }

    /**
     * Removes one customer from the group being managed in the modal —
     * falls back to no group (null), not deleted.
     */
    public function removeFromGroup(int $customerId): void
    {
        $customer = Customer::findOrFail($customerId);
        $customer->update(['customer_group_id' => null]);

        $this->dispatch('toast', ['type' => 'success', 'message' => "{$customer->full_name} removed from group"]);
    }

    public function render(): mixed
    {
        $groups = CustomerGroup::query()
            ->withCount('customers')
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->filterStatus === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->filterStatus === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $data = [
            'groups'      => $groups,
            'totalCount'  => CustomerGroup::count(),
            'activeCount' => CustomerGroup::where('is_active', true)->count(),
        ];

        if ($this->addCustomersModal && $this->assigningGroupId) {
            $data['groupMembers'] = Customer::where('customer_group_id', $this->assigningGroupId)
                ->orderBy('full_name')
                ->get();

            $data['candidates'] = $this->candidateQuery()
                ->with('customerGroup')
                ->orderBy('full_name')
                ->paginate(10, pageName: 'candidatesPage');
        }

        return view('livewire.admin.customers.customer-groups', $data)->layout('layouts.admin.admin');
    }
}
