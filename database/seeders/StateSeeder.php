<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StateSeeder extends Seeder
{
    public function run(): void
    {
        $countryId = Country::where('code', 'BD')->value('id');

        if (! $countryId) {
            return;
        }

        $states = [
            ['code' => 'barishal',    'name' => 'Barishal',    'local_name' => 'বরিশাল',   'sort_order' => 1],
            ['code' => 'chattogram',  'name' => 'Chattogram',  'local_name' => 'চট্টগ্রাম',  'sort_order' => 2],
            ['code' => 'dhaka',       'name' => 'Dhaka',       'local_name' => 'ঢাকা',     'sort_order' => 3],
            ['code' => 'khulna',      'name' => 'Khulna',      'local_name' => 'খুলনা',    'sort_order' => 4],
            ['code' => 'mymensingh',  'name' => 'Mymensingh',  'local_name' => 'ময়মনসিংহ', 'sort_order' => 5],
            ['code' => 'rajshahi',    'name' => 'Rajshahi',    'local_name' => 'রাজশাহী',   'sort_order' => 6],
            ['code' => 'rangpur',     'name' => 'Rangpur',     'local_name' => 'রংপুর',    'sort_order' => 7],
            ['code' => 'sylhet',      'name' => 'Sylhet',      'local_name' => 'সিলেট',    'sort_order' => 8],
        ];

        foreach ($states as $state) {
            DB::table('states')->updateOrInsert(
                ['country_id' => $countryId, 'name' => $state['name']],
                array_merge($state, [
                    'country_id' => $countryId,
                    'is_active'  => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ])
            );
        }
    }
}
