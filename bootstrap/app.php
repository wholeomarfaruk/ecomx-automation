<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // Admin-branded login page (Livewire component, no layout wrapper —
            // it's a standalone auth page, not part of the authenticated admin
            // shell). The form still POSTs to Fortify's real `login` route
            // (AuthenticatedSessionController); this component only owns the UI.
            // Must be registered before the authenticated admin group below.
            Route::middleware(['web', 'guest'])
                ->get('/admin/login', \App\Livewire\Admin\Auth\Login::class)
                ->name('admin.login');

            // 1. Admin Panel: accessible via /admin
            Route::middleware(['web', 'auth', 'panel:admin', 'block.scope:account_panel'])
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));

        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
         $middleware->alias([
            'panel' => \App\Http\Middleware\PanelMiddleware::class,
            'device.track' => \App\Http\Middleware\DeviceTracker::class,
            'block.scope' => \App\Http\Middleware\BlockScope::class,
        ]);

        $middleware->appendToGroup('web', \App\Http\Middleware\DeviceTracker::class);

        // Marketing tracking (session/attribution/PageView capture) reads
        // $request->attributes->get('device') set by DeviceTracker above, so
        // it must run after it. Both defer their DB writes to terminate().
        $middleware->appendToGroup('web', \App\Http\Middleware\MarketingTracker::class);

        // Full-site block enforcement — storefront only, never /admin (an
        // IP/device block on a shopper must not be able to lock out staff
        // sharing that network/browser). Must run after DeviceTracker, which
        // it depends on for $request->attributes->get('device').
        $middleware->web(append: [
            \App\Http\Middleware\EnforceBlocks::class,
        ]);

        // frontend_locale must stay plaintext so client-side JS can read/write it directly.
        $middleware->encryptCookies(except: ['frontend_locale']);

        // /admin/* redirects unauthenticated visitors to the admin-branded login
        // page (admin.login) instead of Fortify's generic /login.
        $middleware->redirectGuestsTo(fn ($request) => $request->is('admin/*')
            ? route('admin.login')
            : route('login'));

        // NOTE: the staff-bypass for maintenance mode is NOT registered here as a
        // global-middleware replacement — global middleware runs before the `web`
        // group's StartSession/cookie-decryption, so auth()->check() is always
        // false at that point regardless of a valid session cookie. The bypass is
        // applied per-route instead, see routes/web.php.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
