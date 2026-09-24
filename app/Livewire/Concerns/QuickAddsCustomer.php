<?php

namespace App\Livewire\Concerns;

use App\Concerns\CreatesMasterProfile;
use App\Models\Customer;
use App\Models\DeliveryAddress;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\DB;

/**
 * "+ Add customer" modal for admin order screens (order create, order
 * edit): name, phone and delivery address only. Creates the MasterProfile +
 * Customer (same as Customers → Add Customer) plus a default DeliveryAddress
 * in one transaction, then hands both to customerQuickAdded() so the host
 * component can select them. The host's view renders the modal via
 * @include('livewire.admin.sales.partials.quick-add-customer-modal').
 */
trait QuickAddsCustomer
{
    use CreatesMasterProfile;

    public bool   $newCustomerModal   = false;
    public string $newCustomerName    = '';
    public string $newCustomerPhone   = '';
    public string $newCustomerAddress = '';

    /** Called after the customer + address are created — select them on the host's form. */
    abstract protected function customerQuickAdded(Customer $customer, DeliveryAddress $address): void;

    public function openNewCustomerModal(): void
    {
        $this->reset(['newCustomerName', 'newCustomerPhone', 'newCustomerAddress']);
        $this->resetValidation();
        $this->newCustomerModal = true;
    }

    public function createNewCustomer(): void
    {
        $this->validate([
            'newCustomerName'    => 'required|string|max:255',
            'newCustomerPhone'   => 'required|string|max:20',
            'newCustomerAddress' => 'required|string|max:1000',
        ], [], [
            'newCustomerName'    => 'name',
            'newCustomerPhone'   => 'phone',
            'newCustomerAddress' => 'delivery address',
        ]);

        $name = trim($this->newCustomerName);
        $phone = PhoneNumber::national($this->newCustomerPhone);

        if (Customer::where('phone', $phone)->exists()) {
            $this->addError('newCustomerPhone', 'A customer with this phone number already exists — search for them instead.');

            return;
        }

        [$firstName, $lastName] = array_pad(explode(' ', $name, 2), 2, null);

        [$customer, $address] = DB::transaction(function () use ($name, $phone, $firstName, $lastName) {
            $masterProfile = $this->createMasterProfileFor([
                'display_name' => $name,
                'first_name'   => $firstName,
                'last_name'    => $lastName,
                'phone'        => $phone,
            ]);

            $customer = Customer::create([
                'master_profile_id' => $masterProfile->id,
                'customer_code'     => 'CUS-' . str_pad((string) (Customer::withTrashed()->max('id') + 1), 5, '0', STR_PAD_LEFT),
                'first_name'        => $firstName,
                'last_name'         => $lastName,
                'full_name'         => $name,
                'phone'             => $phone,
                'status'            => 'active',
            ]);

            $address = DeliveryAddress::create([
                'customer_id'         => $customer->id,
                'address_type'        => 'Home',
                'name'                => $name,
                'phone'               => $phone,
                'full_address'        => trim($this->newCustomerAddress),
                'is_default_billing'  => true,
                'is_default_shipping' => true,
                'is_active'           => true,
            ]);

            return [$customer, $address];
        });

        activity('customers')
            ->causedBy(auth()->user())
            ->performedOn($customer)
            ->event('created')
            ->log("Customer \"{$customer->full_name}\" was added from an order");

        $this->customerQuickAdded($customer, $address);

        $this->newCustomerModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Customer added and selected']);
    }
}
