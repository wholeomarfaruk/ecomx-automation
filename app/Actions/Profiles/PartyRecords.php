<?php

namespace App\Actions\Profiles;

use App\Models\Customer;
use App\Models\MasterProfile;
use App\Models\Supplier;

/**
 * A profile's Customer / Supplier row — the record the rest of the app
 * (orders, POS, purchases, receivable/payable subledgers) works with.
 * Created on demand when a profile gets that role from the Accounts Panel.
 */
class PartyRecords
{
    public function customerFor(MasterProfile $profile): Customer
    {
        return $profile->customer()->first() ?? Customer::create([
            'master_profile_id' => $profile->id,
            'customer_code'     => 'CUS-' . str_pad((string) (Customer::withTrashed()->max('id') + 1), 5, '0', STR_PAD_LEFT),
            'first_name'        => $profile->first_name,
            'last_name'         => $profile->last_name,
            'full_name'         => $profile->display_name,
            'phone'             => $profile->phone,
            'alternative_phone' => $profile->alt_phone,
            'email'             => $profile->email,
            'status'            => 'active',
        ]);
    }

    public function supplierFor(MasterProfile $profile): Supplier
    {
        return $profile->supplier()->first() ?? Supplier::create([
            'master_profile_id' => $profile->id,
            'code'              => 'SUP-' . str_pad((string) (Supplier::withTrashed()->max('id') + 1), 4, '0', STR_PAD_LEFT),
            'name'              => $profile->isBusiness() ? ($profile->contact_person ?: $profile->display_name) : $profile->display_name,
            'company_name'      => $profile->isBusiness() ? $profile->display_name : null,
            'email'             => $profile->email,
            'phone'             => $profile->phone,
            'alternative_phone' => $profile->alt_phone,
            'address'           => $profile->address,
            'status'            => 'active',
        ]);
    }

    /** Push identity changes from the profile form onto the linked rows. */
    public function sync(MasterProfile $profile): void
    {
        $profile->customers()->update([
            'full_name'         => $profile->display_name,
            'phone'             => $profile->phone,
            'alternative_phone' => $profile->alt_phone,
            'email'             => $profile->email,
        ]);

        $profile->suppliers()->update([
            'company_name'      => $profile->isBusiness() ? $profile->display_name : null,
            'name'              => $profile->isBusiness() ? ($profile->contact_person ?: $profile->display_name) : $profile->display_name,
            'phone'             => $profile->phone,
            'alternative_phone' => $profile->alt_phone,
            'email'             => $profile->email,
            'address'           => $profile->address,
        ]);
    }
}
