<?php

use Illuminate\Support\Facades\Route;

// Accounts Panel — loaded from bootstrap/app.php under /accounts, name
// prefix "accounts.", behind panel:accounts + module:accounts.
Route::redirect('/', '/accounts/dashboard');
Route::get('/dashboard', App\Livewire\Accounts\Dashboard::class)->name('dashboard');
