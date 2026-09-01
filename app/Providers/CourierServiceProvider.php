<?php

namespace App\Providers;

use App\Courier\CourierManager;
use Illuminate\Support\ServiceProvider;

class CourierServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/courier.php', 'courier');

        $this->app->singleton('courier.manager', fn ($app) => new CourierManager($app));
    }
}
