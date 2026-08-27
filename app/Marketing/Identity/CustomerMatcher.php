<?php

namespace App\Marketing\Identity;

use App\Models\Customer;

final class CustomerMatcher
{
    public function match(
        ?string $email = null,
        ?string $phone = null,
    ): ?Customer {
        if ($email) {
            $customer = $this->findByEmail($email);

            if ($customer) {
                return $customer;
            }
        }

        if ($phone) {
            $customer = $this->findByPhone($phone);

            if ($customer) {
                return $customer;
            }
        }

        return null;
    }

    private function findByEmail(
        string $email,
    ): ?Customer {
        return Customer::query()
            ->where('email', $this->normalizeEmail($email))
            ->first();
    }

    private function findByPhone(
        string $phone,
    ): ?Customer {
        return Customer::query()
            ->where('phone', $this->normalizePhone($phone))
            ->first();
    }

    private function normalizeEmail(
        string $email,
    ): string {
        return strtolower(trim($email));
    }

    private function normalizePhone(
        string $phone,
    ): string {
        return preg_replace('/[^\d+]/', '', $phone);
    }
}
