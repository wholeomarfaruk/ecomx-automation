<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Push Gateway
    |--------------------------------------------------------------------------
    */

    'default' => env('PUSH_DEFAULT_GATEWAY', 'web_push'),

    /*
    |--------------------------------------------------------------------------
    | Installed Gateways
    |--------------------------------------------------------------------------
    |
    | Every driver class registered here automatically appears in the admin
    | panel. Adding a new gateway is one line here plus a driver class
    | implementing PushGatewayInterface — no other admin code changes.
    |
    */

    'gateways' => [
        'web_push' => \App\Push\Drivers\WebPushDriver::class,
        'firebase' => \App\Push\Drivers\FirebaseDriver::class,
    ],

];
