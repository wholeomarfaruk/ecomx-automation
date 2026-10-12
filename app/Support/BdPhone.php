<?php

namespace App\Support;

/**
 * Bangladeshi mobile numbers in one canonical shape: 01XXXXXXXXX.
 * Accepts +8801…, 8801…, 01…, 1… with spaces/dashes.
 */
class BdPhone
{
    public static function normalize(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '880')) {
            $digits = substr($digits, 2);
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '1')) {
            $digits = '0' . $digits;
        }

        return $digits;
    }

    public static function isValid(?string $phone): bool
    {
        return (bool) preg_match('/^01[3-9]\d{8}$/', (string) static::normalize($phone));
    }

    /** Every stored spelling of the same number, for lookups against legacy rows. */
    public static function variants(?string $phone): array
    {
        $n = static::normalize($phone);

        return $n ? array_values(array_unique([$n, '+88' . $n, '88' . $n, substr($n, 1)])) : [];
    }
}
