<?php

namespace App\Actions\Profiles;

use App\Models\MasterProfile;
use App\Services\MasterProfileUuidGenerator;
use Illuminate\Support\Facades\DB;

/**
 * Brings existing parties under Master Profiles: customers and suppliers
 * that predate master_profile_id get a profile, loans get a lender profile
 * (matched by lender name), and every linked row gets its role. Safe to
 * re-run — only touches rows that aren't linked yet.
 */
class LinkPartiesToProfiles
{
    public function __construct(protected MasterProfileUuidGenerator $uuids) {}

    public function handle(): void
    {
        $this->linkSuppliers();
        $this->linkCustomers();
        $this->linkLoans();
        $this->syncRoles();
    }

    protected function linkSuppliers(): void
    {
        DB::table('suppliers')->whereNull('master_profile_id')->whereNull('deleted_at')->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $s) {
                    $id = $this->createProfile([
                        'type'           => $s->company_name ? 'organization' : 'individual',
                        'display_name'   => $s->company_name ?: $s->name,
                        'contact_person' => $s->company_name ? $s->name : null,
                        'phone'          => $s->phone,
                        'alt_phone'      => $s->alternative_phone,
                        'email'          => $s->email,
                        'address'        => $s->address,
                        'status'         => $s->status === 'inactive' ? 'inactive' : 'active',
                    ]);

                    DB::table('suppliers')->where('id', $s->id)->update(['master_profile_id' => $id]);
                }
            });
    }

    protected function linkCustomers(): void
    {
        DB::table('customers')->whereNull('master_profile_id')->whereNull('deleted_at')->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $c) {
                    $name = $c->full_name ?: trim("{$c->first_name} {$c->last_name}") ?: ($c->phone ?: 'Customer');

                    $id = $this->createProfile([
                        'type'         => 'individual',
                        'display_name' => $name,
                        'first_name'   => $c->first_name,
                        'last_name'    => $c->last_name,
                        'phone'        => $c->phone,
                        'alt_phone'    => $c->alternative_phone,
                        'email'        => $c->email,
                        'status'       => 'active',
                    ]);

                    DB::table('customers')->where('id', $c->id)->update(['master_profile_id' => $id]);
                }
            });
    }

    protected function linkLoans(): void
    {
        DB::table('loans')->whereNull('master_profile_id')->orderBy('id')->get()
            ->each(function ($loan) {
                $name = $loan->lender ?: $loan->name;

                $id = DB::table('master_profiles')->whereNull('deleted_at')->where('display_name', $name)->value('id')
                    ?? $this->createProfile(['type' => 'organization', 'display_name' => $name, 'status' => 'active']);

                DB::table('loans')->where('id', $loan->id)->update(['master_profile_id' => $id]);
            });
    }

    protected function syncRoles(): void
    {
        $now = now();

        foreach (['customer' => 'customers', 'supplier' => 'suppliers', 'lender' => 'loans'] as $role => $table) {
            $query = DB::table($table)->whereNotNull('master_profile_id')
                ->selectRaw("DISTINCT master_profile_id, ? as role, ? as created_at, ? as updated_at", [$role, $now, $now]);

            if ($table !== 'loans') {
                $query->whereNull('deleted_at');
            }

            DB::table('master_profile_roles')->insertOrIgnoreUsing(['master_profile_id', 'role', 'created_at', 'updated_at'], $query);
        }
    }

    protected function createProfile(array $attributes): int
    {
        $now = now();
        $id  = DB::table('master_profiles')->insertGetId($attributes + [
            'uuid'       => $this->uuids->generate(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('master_profiles')->where('id', $id)->update(['code' => MasterProfile::codeFor($id)]);

        return $id;
    }
}
