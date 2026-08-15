<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default SMS Gateway
    |--------------------------------------------------------------------------
    |
    | The driver key used when no active gateway is configured in the
    | sms_gateway_configs table yet. Must match a key in "gateways" below.
    |
    */

    'default' => env('SMS_DEFAULT_GATEWAY', 'twilio'),

    /*
    |--------------------------------------------------------------------------
    | Installed Gateways
    |--------------------------------------------------------------------------
    |
    | Every driver class registered here automatically appears in the admin
    | panel's "Installed Gateways" list. Adding a new gateway is just one
    | line here plus a driver class implementing SmsGatewayInterface —
    | no other admin code changes are required.
    |
    */

    'gateways' => [
        'twilio' => \App\Sms\Drivers\TwilioDriver::class,
        'alpha_sms' => \App\Sms\Drivers\AlphaSmsDriver::class,
        'bulksmsbd' => \App\Sms\Drivers\BulkSmsBdDriver::class,
    ],

];
