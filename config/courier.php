<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Courier
    |--------------------------------------------------------------------------
    |
    | Used only when no courier_accounts row is flagged is_default — normally
    | CourierManager resolves the default from the database instead.
    |
    */

    'default' => env('COURIER_DEFAULT', 'steadfast'),

    /*
    |--------------------------------------------------------------------------
    | Registered Courier Drivers
    |--------------------------------------------------------------------------
    |
    | key => driver class. Add a new courier by dropping its driver class
    | under app/Courier/Drivers, registering it here, and seeding a row in
    | the couriers table (see database/seeders/CourierSeeder.php) — no
    | other part of the system needs to change.
    |
    */

    'drivers' => [
        'steadfast' => \App\Courier\Drivers\SteadFastDriver::class,
        'pathao' => \App\Courier\Drivers\PathaoDriver::class,
        'redx' => \App\Courier\Drivers\RedXDriver::class,
        'paperfly' => \App\Courier\Drivers\PaperflyDriver::class,
        // 'sundarban' => \App\Courier\Drivers\SundarbanDriver::class,
        // 'sa_paribahan' => \App\Courier\Drivers\SAParibahanDriver::class,
    ],

];
