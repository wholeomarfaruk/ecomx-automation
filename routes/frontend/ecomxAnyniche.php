<?php

use Illuminate\Support\Facades\Route;

/**
 * ecomxAnyniche engine — generic storefront routes.
 *
 * Owns WHAT the ecomxAnyniche storefront does. Route names are stable
 * (ecomx-anyniche.*) across every theme this engine has.
 *
 * Loaded once per request by EngineManager::loadActiveThemeRoute(), called
 * from routes/web.php — never require this file directly. This engine only
 * takes effect once config('frontend-engine.active_engine') is set to
 * 'ecomxAnyniche' and its theme is activated.
 */
Route::name('ecomx-anyniche.')
    ->middleware([
        \App\Http\Middleware\SetFrontendLocale::class,
        \App\Http\Middleware\PreventPublicMaintenanceForStaff::class,
    ])
    ->group(function () {
        Route::get('/', \App\Livewire\EcomxAnyniche\Home::class)->name('home');
        Route::get('/shop', \App\Livewire\EcomxAnyniche\Shop::class)->name('shop');
        Route::get('/category/{slug?}', \App\Livewire\EcomxAnyniche\Category::class)->name('category');
        Route::get('/product/{slug?}', \App\Livewire\EcomxAnyniche\Product::class)->name('product');
        Route::get('/reviews', \App\Livewire\EcomxAnyniche\Reviews::class)->name('reviews');
        Route::get('/about', \App\Livewire\EcomxAnyniche\About::class)->name('about');
        Route::get('/privacy-policy', \App\Livewire\EcomxAnyniche\PrivacyPolicy::class)->name('privacy-policy');
        Route::get('/account', \App\Livewire\EcomxAnyniche\Account::class)->name('account');
        Route::get('/track', \App\Livewire\EcomxAnyniche\Track::class)->name('track')->middleware('block.scope:orders');
        Route::get('/track/{order}', \App\Livewire\EcomxAnyniche\TrackDetails::class)->name('track.show')->middleware('block.scope:orders');
        Route::get('/checkout', \App\Livewire\EcomxAnyniche\Checkout::class)->name('checkout')->middleware('block.scope:checkout');
    });
