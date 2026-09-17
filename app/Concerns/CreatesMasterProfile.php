<?php

namespace App\Concerns;

use App\Enums\Profiles\MasterProfileType;
use App\Models\MasterProfile;
use App\Services\MasterProfileUuidGenerator;

/**
 * Every specialized party (Customer, Supplier, Lender, Investor, ...) is
 * backed by exactly one MasterProfile — the shared identity record. Any
 * model/action that creates or edits such a party should use this trait so
 * the master profile is always created up front and kept in sync afterward,
 * instead of each call site reinventing the same create/update calls.
 */
trait CreatesMasterProfile
{
    /**
     * Creates a new MasterProfile from the given attributes and returns it.
     * Callers create their specialized profile (e.g. Customer) referencing
     * $profile->id right after this.
     *
     * @param  array{display_name: string, type?: string, phone?: ?string, country_code?: ?string, email?: ?string, notes?: ?string}  $attributes
     */
    protected function createMasterProfileFor(array $attributes): MasterProfile
    {
        return MasterProfile::create([
            'uuid'         => app(MasterProfileUuidGenerator::class)->generate(),
            'type'         => $attributes['type'] ?? MasterProfileType::INDIVIDUAL->value,
            'display_name' => $attributes['display_name'],
            'first_name'   => $attributes['first_name'] ?? null,
            'last_name'    => $attributes['last_name'] ?? null,
            'country_code' => $attributes['country_code'] ?? null,
            'phone'        => $attributes['phone'] ?? null,
            'email'        => $attributes['email'] ?? null,
            'notes'        => $attributes['notes'] ?? null,
            'status'       => 'active',
            'created_by'   => auth()->id(),
        ]);
    }

    /**
     * Attaches an existing MasterProfile by id instead of creating a new
     * one — the "link to existing profile" path, for when the same
     * real-world party already has a profile (e.g. linking a new Supplier
     * role to a person who is already a Customer). Falls back to
     * createMasterProfileFor() when no id is given, so callers can use this
     * as their single entry point regardless of whether the admin picked an
     * existing profile or is creating a brand new one.
     *
     * @param  array{display_name: string, type?: string, phone?: ?string, country_code?: ?string, email?: ?string, notes?: ?string}  $attributes
     */
    protected function resolveMasterProfile(?int $existingId, array $attributes): MasterProfile
    {
        if ($existingId) {
            return MasterProfile::findOrFail($existingId);
        }

        return $this->createMasterProfileFor($attributes);
    }

    /**
     * Pushes the given attributes onto an existing MasterProfile, so
     * updating a Customer/Supplier/etc. keeps the shared identity record
     * current. Only keys present in $attributes are touched.
     *
     * @param  array{display_name?: string, phone?: ?string, country_code?: ?string, email?: ?string, notes?: ?string}  $attributes
     */
    protected function syncMasterProfile(?MasterProfile $profile, array $attributes): void
    {
        if (! $profile) {
            return;
        }

        $profile->update([
            ...$attributes,
            'updated_by' => auth()->id(),
        ]);
    }
}
