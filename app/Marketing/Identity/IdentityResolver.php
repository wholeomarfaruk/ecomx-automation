<?php

namespace App\Marketing\Identity;

use App\Marketing\Context\MarketingContext;

final class IdentityResolver
{
    public function resolve(
        MarketingContext $context,
    ): MarketingIdentity {
        $customer = $context->customer;
        $user = $context->user;

        return new MarketingIdentity(
            email: $this->firstValue(
                $this->get($customer, 'email'),
                $this->get($user, 'email'),
            ),

            phone: $this->firstValue(
                $this->get($customer, 'phone', 'alternative_phone'),
                $this->get($user, 'phone'),
            ),

            firstName: $this->firstValue(
                $this->get($customer, 'first_name'),
                $this->get($user, 'name'),
            ),

            lastName: $this->get($customer, 'last_name'),

            country: $this->get($user, 'country_code'),

            externalId: $this->resolveExternalId($customer, $user),

            deviceFingerprint: $context->deviceFingerprint,
        );
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

    private function resolveExternalId(
        mixed $customer,
        mixed $user,
    ): ?string {
        if ($customer?->id) {
            return 'customer_'.$customer->id;
        }

        if ($user?->id) {
            return 'user_'.$user->id;
        }

        return null;
    }
}
