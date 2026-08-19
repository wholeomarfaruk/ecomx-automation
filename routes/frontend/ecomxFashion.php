<?php

use Illuminate\Support\Facades\Route;

/**
 * ecomxFashion engine — storefront routes.
 *
 * Owns WHAT the ecomxFashion storefront does. Route names are stable
 * (ecomx-fashion.*) across every theme this engine has. ecomxFashion is the
 * active theme/engine, so its routes live at the site root (no URL prefix) —
 * route *names* still carry the ecomx-fashion. prefix so nothing that calls
 * route('ecomx-fashion.xxx') needs to change.
 *
 * Loaded once per request by EngineManager::loadActiveThemeRoute(), called
 * from routes/web.php — never require this file directly.
 */
Route::name('ecomx-fashion.')
    ->middleware([
        \App\Http\Middleware\SetFrontendLocale::class,
        \App\Http\Middleware\PreventPublicMaintenanceForStaff::class,
    ])
    ->group(function () {
        Route::get('/', \App\Livewire\EcomxFashion\Home::class)->name('home');
        Route::get('/shop', \App\Livewire\EcomxFashion\Shop::class)->name('shop');
        Route::get('/category/{slug?}', \App\Livewire\EcomxFashion\Category::class)->name('category');
        Route::get('/product/{slug?}', \App\Livewire\EcomxFashion\Product::class)->name('product');
        Route::get('/reviews', \App\Livewire\EcomxFashion\Reviews::class)->name('reviews');
        Route::get('/track', \App\Livewire\EcomxFashion\Track::class)->name('track')->middleware('block.scope:orders');
        Route::get('/checkout', \App\Livewire\EcomxFashion\Checkout::class)->name('checkout')->middleware('block.scope:checkout');
    });
