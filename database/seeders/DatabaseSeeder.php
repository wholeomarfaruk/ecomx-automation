<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $this->call([
            PermissionSeeder::class,
            PanelSeeder::class,
            AssignPermissionSeeder::class,
            SettingsSeeder::class,
            LanguageSeeder::class,
            CountrySeeder::class,
            StateSeeder::class,
            CitySeeder::class,
            PoliceStationSeeder::class,
            CurrencySeeder::class,
            GenderSeeder::class,
            CustomerGroupSeeder::class,
            SmsTemplateSeeder::class,
            EmailTemplateSeeder::class,
            NotificationEventSeeder::class,
        ]);
        User::create([
            'name' => 'superadmin',
            'email' => 'superadmin@gmail.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $user = User::find(1);
        $user->assignRole('superadmin');
        $user->panels()->attach(1);
    }
}
