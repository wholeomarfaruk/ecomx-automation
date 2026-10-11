<?php

use Illuminate\Support\Facades\Route;

// Accounts Panel — loaded from bootstrap/app.php under /accounts, name
// prefix "accounts.", behind panel:accounts + module:accounts.
Route::get('/', App\Livewire\Accounts\Dashboard::class)->name('dashboard');
