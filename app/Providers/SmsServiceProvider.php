<?php

namespace App\Providers;

use App\Sms\SmsManager;
use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('sms.manager', fn ($app) => new SmsManager($app));
    }
}
