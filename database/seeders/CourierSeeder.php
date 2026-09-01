<?php

namespace Database\Seeders;

use App\Courier\Drivers\PaperflyDriver;
use App\Courier\Drivers\PathaoDriver;
use App\Courier\Drivers\RedXDriver;
use App\Courier\Drivers\SteadFastDriver;
use App\Models\Courier;
use Illuminate\Database\Seeder;

/**
 * Master courier registry — the initial six-courier roadmap from the
 * architecture plan. SteadFast, Pathao, RedX, and Paperfly ship with
 * working drivers (Phase 1/2/3/4), all verified against live merchant/
 * sandbox accounts; the rest are seeded inactive so they show up in the
 * admin list as "coming soon" placeholders and just need a driver class
 * wired into config/courier.php + is_active flipped on when their turn comes.
 * Activating a courier here only unlocks it in the UI — an admin still has
 * to add a real account with credentials under Accounts before it can
 * actually book shipments; credentials are never seeded/committed to
 * source control.
 */
class CourierSeeder extends Seeder
{
    public function run(): void
    {
        $steadFastCapabilities = (new SteadFastDriver(['api_key' => '', 'secret_key' => '']))->capabilities();
        $pathaoCapabilities = (new PathaoDriver(['client_id' => '', 'client_secret' => '', 'username' => '', 'password' => '']))->capabilities();
        $redXCapabilities = (new RedXDriver(['token' => '']))->capabilities();
        $paperflyCapabilities = (new PaperflyDriver(['username' => '', 'password' => '', 'paperfly_key' => '', 'store_name' => '']))->capabilities();

        $couriers = [
            [
                'name' => 'SteadFast Courier',
                'slug' => 'steadfast',
                'driver_key' => 'steadfast',
                'type' => 'api',
                'description' => 'SteadFast Courier Limited — nationwide courier & COD service.',
                'capabilities' => $steadFastCapabilities,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Pathao Courier',
                'slug' => 'pathao',
                'driver_key' => 'pathao',
                'type' => 'api',
                'description' => 'Pathao Courier & Parcel.',
                'capabilities' => $pathaoCapabilities,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'RedX',
                'slug' => 'redx',
                'driver_key' => 'redx',
                'type' => 'api',
                'description' => 'RedX parcel delivery — supports exchange parcels.',
                'capabilities' => $redXCapabilities,
                'is_active' => false,
                'sort_order' => 3,
            ],
            [
                'name' => 'Paperfly',
                'slug' => 'paperfly',
                'driver_key' => 'paperfly',
                'type' => 'api',
                'description' => 'Paperfly logistics — supports exchange parcels.',
                'capabilities' => $paperflyCapabilities,
                'is_active' => true,
                'sort_order' => 4,
            ],
            ['name' => 'Sundarban Courier', 'slug' => 'sundarban', 'driver_key' => 'sundarban', 'type' => 'manual', 'description' => 'Sundarban Courier Service.', 'capabilities' => [], 'is_active' => false, 'sort_order' => 5],
            ['name' => 'SA Paribahan', 'slug' => 'sa_paribahan', 'driver_key' => 'sa_paribahan', 'type' => 'manual', 'description' => 'SA Paribahan.', 'capabilities' => [], 'is_active' => false, 'sort_order' => 6],
        ];

        foreach ($couriers as $courier) {
            Courier::updateOrCreate(['slug' => $courier['slug']], $courier);
        }
    }
}
