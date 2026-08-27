<?php

use Illuminate\Support\Facades\Route;

// Loads the active engine's storefront route file (see App\FrontendEngine\EngineManager).
// Must run before any other route registration so engine routes are available
// for the whole request; never builds a path from request input.
\App\FrontendEngine\EngineManager::loadActiveThemeRoute();

// Landing Page Engine — deliberately separate from the theme/engine system
// above (see routes/landingpage.php docblock and docs/landing-page-engine/).
require __DIR__.'/landingpage.php';

// Legacy site — superseded by the ecomxFashion theme/engine, which now owns
// the site root (/, /shop, /product/{slug}, …, loaded above via
// EngineManager::loadActiveThemeRoute()). Kept reachable at /legacy rather
// than deleted; not linked from anywhere active.
Route::middleware([
    \App\Http\Middleware\SetFrontendLocale::class,
    \App\Http\Middleware\PreventPublicMaintenanceForStaff::class,
])->prefix('legacy')->name('legacy.')->group(function () {
    Route::get('/', \App\Livewire\Website\Home\Home::class)->name('home');
});

// Client-side device signals (screen size/density, timezone) that DeviceTracker
// can't read from headers alone — fired async from the layout, never blocks render.
Route::post('/device-signal', [\App\Http\Controllers\DeviceSignalController::class, 'store'])->name('device-signal');
