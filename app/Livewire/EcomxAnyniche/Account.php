<?php

namespace App\Livewire\EcomxAnyniche;

use App\Models\City;
use App\Models\Country;
use App\Models\Customer;
use App\Models\DeliveryAddress;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Customer account page — profile + address book. Visual design ported
 * from juwel-trade-corporation's storefront.account (jtc-account,
 * jtc-account-card, jtc-account-form, jtc-address-card classes). Address
 * fields/validation mirror {@see Checkout}'s address form (free-text
 * address + coarse Dhaka/outside-Dhaka area, since this codebase has no
 * location picker), so a saved address behaves identically whether it was
 * added here or during checkout.
 */
#[Layout('ecomx-anyniche.layouts.ecomx_anyniche')]
class Account extends Component
{
    public string $name = '';
    public string $email = '';
    public string $phone = '';

    public bool $showAddressForm = false;
    public ?int $editingAddressId = null;
    public string $addressName = '';
    public string $addressPhone = '';
    public string $addressLine = '';
    public string $addressType = '';
    public string $deliveryArea = 'dhaka';
    public bool $setAsDefault = false;

    public string $currentPassword = '';
    public string $newPassword = '';
    public string $newPassword_confirmation = '';

    public function mount(): void
    {
        $customer = $this->customer();

        if (! $customer) {
            return;
        }

        $this->name = $customer->full_name
            ?: trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
        $this->email = $customer->email ?? '';
        $this->phone = $customer->phone ?? '';
    }

    protected function customer(): ?Customer
    {
        return auth()->check() ? auth()->user()->customer : null;
    }

    public function updateProfile(): void
    {
        $customer = $this->customer();

        if (! $customer) {
            return;
        }

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('customers', 'email')->ignore($customer->id),
                Rule::unique('users', 'email')->ignore(auth()->id()),
            ],
            'phone' => ['required', 'regex:/^0\d{10}$/'],
        ], [
            'phone.regex' => 'Enter an 11-digit phone number starting with 0.',
        ]);

        [$firstName, $lastName] = array_pad(explode(' ', trim($this->name), 2), 2, null);

        $normalizedPhone = PhoneNumber::national($this->phone);

        $customer->update([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => $this->name,
            'email' => $this->email,
            'phone' => $normalizedPhone,
        ]);

        // Fortify authenticates by users.email (config('fortify.username')) —
        // must stay in sync with the customer's email or a changed email here
        // would silently lock them out of logging in with their new password.
        auth()->user()->update([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $normalizedPhone,
        ]);

        $this->dispatch('toast', message: 'Profile updated successfully.');
    }

    public function addAddress(): void
    {
        $this->resetAddressForm();
        $this->showAddressForm = true;
    }

    public function editAddress(int $id): void
    {
        $customer = $this->customer();
        $address = $customer?->deliveryAddresses()->find($id);

        if (! $address) {
            return;
        }

        $this->editingAddressId = $address->id;
        $this->addressName = $address->name ?? '';
        $this->addressPhone = $address->phone ?? '';
        $this->addressLine = $address->full_address ?? '';
        $this->addressType = $address->address_type ?? '';
        $this->deliveryArea = $address->city?->name && str_starts_with($address->city->name, 'Dhaka') ? 'dhaka' : 'other';
        $this->setAsDefault = (bool) $address->is_default_shipping;
        $this->showAddressForm = true;
    }

    public function saveAddress(): void
    {
        $customer = $this->customer();

        if (! $customer) {
            return;
        }

        $this->validate([
            'addressName' => ['required', 'string', 'max:255'],
            'addressPhone' => ['required', 'regex:/^0\d{10}$/'],
            'addressLine' => ['required', 'string', 'max:500'],
            'addressType' => ['nullable', 'string', 'max:50'],
        ], [
            'addressPhone.regex' => 'Enter an 11-digit phone number starting with 0.',
        ]);

        $country = Country::whereRaw('LOWER(name) = ?', ['bangladesh'])->first();

        $city = $this->deliveryArea === 'dhaka'
            ? City::where('name', 'like', 'Dhaka%')
                ->when($country, fn ($q) => $q->whereHas('state', fn ($s) => $s->where('country_id', $country->id)))
                ->first()
            : null;

        $state = $city?->state;

        $isFirstAddress = ! $customer->deliveryAddresses()->exists();
        $makeDefault = $isFirstAddress || $this->setAsDefault;

        $data = [
            'customer_id' => $customer->id,
            'address_type' => trim($this->addressType) ?: ($isFirstAddress ? 'Home' : null),
            'name' => $this->addressName,
            'phone' => PhoneNumber::national($this->addressPhone),
            'country_id' => $country?->id,
            'state_id' => $state?->id,
            'city_id' => $city?->id,
            'full_address' => $this->addressLine,
            'is_active' => true,
        ];

        if ($this->editingAddressId) {
            $address = $customer->deliveryAddresses()->find($this->editingAddressId);

            if (! $address) {
                $this->addError('addressName', 'That address is no longer available — please add a new one.');

                return;
            }

            if ($makeDefault) {
                $this->promoteToDefault($customer, $address, $data);
            } else {
                $address->update($data);
            }
        } else {
            if ($makeDefault) {
                $customer->deliveryAddresses()->update(['is_default_shipping' => false, 'is_default_billing' => false]);
            }

            $customer->deliveryAddresses()->create($data + [
                'is_default_shipping' => $makeDefault,
                'is_default_billing' => $makeDefault,
            ]);
        }

        $this->resetAddressForm();
        $this->showAddressForm = false;
        $this->dispatch('toast', message: 'Address saved successfully.');
    }

    public function deleteAddress(int $id): void
    {
        $this->customer()?->deliveryAddresses()->whereKey($id)->delete();
        $this->dispatch('toast', message: 'Address removed.');
    }

    public function makePrimary(int $id): void
    {
        $customer = $this->customer();

        if (! $customer) {
            return;
        }

        $address = $customer->deliveryAddresses()->find($id);

        if (! $address) {
            return;
        }

        $this->promoteToDefault($customer, $address, []);
    }

    private function promoteToDefault(Customer $customer, DeliveryAddress $address, array $extra): void
    {
        $customer->deliveryAddresses()->update(['is_default_shipping' => false, 'is_default_billing' => false]);

        $address->update($extra + ['is_default_shipping' => true, 'is_default_billing' => true]);
    }

    /**
     * False for an account still on the random unusable password Checkout
     * assigns at auto-registration (see password_set_at migration) — the
     * form then skips asking for a "current password" nobody could know.
     */
    public function hasPassword(): bool
    {
        return auth()->user()?->password_set_at !== null;
    }

    public function updatePassword(): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $rules = [
            'newPassword' => ['required', 'confirmed', Password::min(8)],
        ];

        if ($this->hasPassword()) {
            $rules['currentPassword'] = ['required', 'current_password'];
        }

        $this->validate($rules);

        $user->forceFill([
            'password' => Hash::make($this->newPassword),
            'password_set_at' => now(),
        ])->save();

        $this->reset('currentPassword', 'newPassword', 'newPassword_confirmation');

        $this->dispatch('toast', message: 'Password updated successfully.');
    }

    public function cancelAddressForm(): void
    {
        $this->resetAddressForm();
        $this->showAddressForm = false;
    }

    protected function resetAddressForm(): void
    {
        $this->editingAddressId = null;
        $this->addressName = '';
        $this->addressPhone = '';
        $this->addressLine = '';
        $this->addressType = '';
        $this->deliveryArea = 'dhaka';
        $this->setAsDefault = false;
        $this->resetValidation();
    }

    public function render()
    {
        $customer = $this->customer();

        return view('ecomx-anyniche.livewire.account', [
            'addresses' => $customer
                ? $customer->deliveryAddresses()->orderByDesc('is_default_shipping')->get()
                : collect(),
        ]);
    }
}
