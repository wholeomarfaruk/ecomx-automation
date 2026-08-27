<?php

use App\Livewire\LandingPageEngine\LandingPageRenderer;
use Illuminate\Support\Facades\Route;

/**
 * Landing Page Engine — public routes.
 *
 * Deliberately NOT inside routes/frontend/ecomxFashion.php or any other
 * theme/engine file. The Landing Page Engine is its own system, independent
 * of whichever storefront theme happens to be active (see
 * docs/landing-page-engine/00-overview.md) — it must keep working even if
 * the active engine changes, so it is loaded directly from routes/web.php,
 * bypassing App\FrontendEngine\EngineManager entirely.
 *
 * Uses the same generic, non-theme-specific middleware the active engine
 * also happens to use (SetFrontendLocale, PreventPublicMaintenanceForStaff)
 * — reused because they're genuinely reusable public-site concerns with no
 * ecomx-fashion-specific behavior, not because this file depends on that
 * engine. DeviceTracker/MarketingTracker/EnforceBlocks are already global
 * `web`-group middleware (see bootstrap/app.php) and apply automatically.
 *
 * A single route target (LandingPageRenderer, a Livewire full-page
 * component) catches the slug, resolves the page's template, and
 * dynamically @livewire()s that template's root component — see that
 * class's docblock for the full mechanism.
 */
Route::middleware([
    'web',
    \App\Http\Middleware\SetFrontendLocale::class,
    \App\Http\Middleware\PreventPublicMaintenanceForStaff::class,
])->group(function () {
    Route::get('/lp/{slug}', LandingPageRenderer::class)->name('landingpage.show');
});
