<?php

use Illuminate\Support\Facades\Route;

// Public website: translated content + RTL/LTR direction based on the visitor's
// chosen language (cookie-driven, see App\Http\Middleware\SetFrontendLocale),
// plus a staff-bypass for maintenance mode. This must run inside the `web`
// group (after StartSession) so auth()->check() actually reflects the
// visitor's session — the global middleware stack runs before sessions start.
Route::middleware([
    \App\Http\Middleware\SetFrontendLocale::class,
    \App\Http\Middleware\PreventPublicMaintenanceForStaff::class,
])->group(function () {
    Route::get('/', \App\Livewire\Website\Home\Home::class)->name('home');
});
