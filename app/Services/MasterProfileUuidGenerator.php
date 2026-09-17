<?php

namespace App\Services;

use App\Models\MasterProfile;

/**
 * master_profiles.uuid is a 10-digit numeric identifier (digits only, no
 * letters) rather than an RFC4122 UUID — generated here and checked against
 * the table for uniqueness before being handed out.
 */
class MasterProfileUuidGenerator
{
    protected const LENGTH = 10;

    public function generate(): string
    {
        do {
            $uuid = $this->randomDigits();
        } while (MasterProfile::where('uuid', $uuid)->exists());

        return $uuid;
    }

    protected function randomDigits(): string
    {
        // First digit 1-9 so the value never has a leading zero (stays a
        // true 10-digit number, not a 10-character zero-padded string).
        $first = (string) random_int(1, 9);
        $rest = (string) random_int(0, 999999999);

        return $first.str_pad($rest, self::LENGTH - 1, '0', STR_PAD_LEFT);
    }
}
