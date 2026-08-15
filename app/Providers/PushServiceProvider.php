<?php

namespace App\Providers;

use App\Push\PushManager;
use Illuminate\Support\ServiceProvider;

class PushServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PushManager::class, fn ($app) => new PushManager($app));
    }
}
