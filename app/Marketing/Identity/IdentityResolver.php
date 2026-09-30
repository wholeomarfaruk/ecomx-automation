<?php

namespace App\Marketing\Identity;

use App\Marketing\Context\MarketingContext;
use App\Marketing\Contracts\EventContract;
use App\Marketing\Events\Purchase;
use App\Models\Country;
use App\Models\DeliveryAddress;
use App\Models\Order;
use App\Support\PhoneNumber;

final class IdentityResolver
{
    private const ADDRESS_RELATIONS = ['country', 'state', 'city.state'];

    public function resolve(
        MarketingContext $context,
        ?EventContract $event = null,
    ): MarketingIdentity {
        $customer = $context->customer;
        $user = $context->user;

        $address = $this->resolveAddress($customer, $event);

        [$firstName, $lastName] = $this->resolveName($customer, $user);

        // zip stays null: checkout collects no postcode, and
        // delivery_addresses.zip_code_id has no backing model/data.
        return new MarketingIdentity(
            email: $this->firstValue(
                $this->get($customer, 'email'),
                $this->get($user, 'email'),
            ),

            phone: $this->resolvePhone($customer, $user),

            firstName: $firstName,
            lastName: $lastName,

            gender: $this->get($customer, 'gender'),
            dateOfBirth: $this->get($customer, 'date_of_birth')?->format('Y-m-d'),

            city: $address?->city?->name,
            state: $address?->state?->name ?? $address?->city?->state?->name,

            country: $this->resolveCountry($address, $customer, $user),

            externalIds: $this->resolveExternalIds($customer, $user, $context->deviceFingerprint),

            deviceFingerprint: $context->deviceFingerprint,
        );
    }

    /**
     * A purchase ships to one specific address, so that one wins over the
     * customer's saved default. Otherwise the default shipping address,
     * then the most recent one.
     */
    private function resolveAddress(
        mixed $customer,
        ?EventContract $event,
    ): ?DeliveryAddress {
        if ($event instanceof Purchase && is_numeric($event->orderId)) {
            $addressId = Order::whereKey($event->orderId)->value('shipping_address_id');

            $address = $addressId
                ? DeliveryAddress::with(self::ADDRESS_RELATIONS)->find($addressId)
                : null;

            if ($address) {
                return $address;
            }
        }

        $customerId = $this->get($customer, 'id');

        if (! $customerId) {
            return null;
        }

        return DeliveryAddress::with(self::ADDRESS_RELATIONS)
            ->where('customer_id', $customerId)
            ->where('is_active', true)
            ->orderByDesc('is_default_shipping')
            ->latest('id')
            ->first();
    }

    /** @return array{0: ?string, 1: ?string} */
    private function resolveName(
        mixed $customer,
        mixed $user,
    ): array {
        $firstName = $this->get($customer, 'first_name');
        $lastName = $this->get($customer, 'last_name');

        if ($firstName || $lastName) {
            return [$firstName, $lastName];
        }

        // Only a single display name to go on (Customer.full_name /
        // User.name) — split it the way checkout does: first word, then
        // the rest.
        $fullName = $this->firstValue(
            $this->get($customer, 'full_name'),
            $this->get($user, 'name'),
        );

        if (! $fullName) {
            return [null, null];
        }

        return array_pad(explode(' ', preg_replace('/\s+/', ' ', trim($fullName)), 2), 2, null);
    }

    private function resolvePhone(
        mixed $customer,
        mixed $user,
    ): ?string {
        $phone = $this->firstValue(
            $this->get($customer, 'phone', 'alternative_phone'),
            $this->get($user, 'phone'),
        );

        if (! $phone) {
            return null;
        }

        $normalized = PhoneNumber::normalize((string) $phone);

        if ($normalized['phone'] === '') {
            return null;
        }

        // Customer has no country_code column of its own — only User does.
        $countryCode = $this->firstValue(
            $this->get($customer, 'user.country_code'),
            $this->get($user, 'country_code'),
        );

        return PhoneNumber::display($normalized['phone'], $countryCode ?: $normalized['country_code']);
    }

    /**
     * Meta: "Always include your customers' countries even if all of your
     * country codes are from the same country." So an anonymous visitor
     * still gets the store's home country, picked the same way as
     * PhoneNumber's default dialing code.
     */
    private function resolveCountry(
        ?DeliveryAddress $address,
        mixed $customer,
        mixed $user,
    ): ?string {
        if ($code = $address?->country?->code) {
            return $code;
        }

        // users.country_code is a dialing code (+880), not an ISO code.
        $phoneCode = $this->firstValue(
            $this->get($customer, 'user.country_code'),
            $this->get($user, 'country_code'),
        );

        if ($phoneCode && $code = Country::where('phone_code', $phoneCode)->value('code')) {
            return $code;
        }

        return Country::query()
            ->active()
            ->where('is_register_allowed', true)
            ->orderBy('sort_order')
            ->value('code');
    }

    /** @return string[] */
    private function resolveExternalIds(
        mixed $customer,
        mixed $user,
        ?string $deviceFingerprint,
    ): array {
        $ids = [];

        if ($customerId = $this->get($customer, 'id')) {
            $ids[] = 'customer_'.$customerId;
        } elseif ($userId = $this->get($user, 'id')) {
            $ids[] = 'user_'.$userId;
        }

        if ($deviceFingerprint) {
            $ids[] = 'device_'.$deviceFingerprint;
        }

        return $ids;
    }

    private function firstValue(
        mixed ...$values,
    ): mixed {
        foreach ($values as $value) {
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function get(
        mixed $source,
        string ...$fields,
    ): mixed {
        if (! $source) {
            return null;
        }

        foreach ($fields as $field) {
            $value = data_get($source, $field);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
