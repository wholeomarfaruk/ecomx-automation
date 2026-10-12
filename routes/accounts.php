<?php

use App\Http\Controllers\Accounts\ProfileDocumentController;
use App\Livewire\Accounts\Profiles;
use Illuminate\Support\Facades\Route;

// Accounts Panel — loaded from bootstrap/app.php under /accounts, name
// prefix "accounts.", behind panel:accounts + module:accounts.
Route::redirect('/', '/accounts/dashboard');
Route::get('/dashboard', App\Livewire\Accounts\Dashboard::class)->name('dashboard');
Route::get('/settings', App\Livewire\Accounts\Settings::class)->name('settings');
Route::get('/posting', App\Livewire\Accounts\PostingPending::class)->name('posting');
Route::get('/transactions', App\Livewire\Accounts\Transactions::class)->name('transactions');
Route::get('/cash-bank', App\Livewire\Accounts\CashBank::class)->name('cash-bank');
Route::get('/receivables', App\Livewire\Accounts\Receivables::class)->name('receivables');
Route::get('/payables', App\Livewire\Accounts\Payables::class)->name('payables');
Route::get('/loans', App\Livewire\Accounts\LoansEmi::class)->name('loans');
Route::get('/expenses', App\Livewire\Accounts\Expenses::class)->name('expenses');
Route::get('/vouchers/{entry}/print', [App\Http\Controllers\Accounts\VoucherController::class, 'print'])->name('vouchers.print');
Route::get('/vouchers/attachments/{attachment}', [App\Http\Controllers\Accounts\VoucherController::class, 'attachment'])->name('vouchers.attachment');

// Master Profiles — one profile per person/business, with roles.
Route::prefix('profiles')->name('profiles.')->group(function () {
    Route::get('/', Profiles\ProfileIndex::class)->name('index');
    Route::get('/customers', Profiles\Customers::class)->name('customers');
    Route::get('/suppliers', Profiles\Suppliers::class)->name('suppliers');
    Route::get('/lenders', Profiles\Lenders::class)->name('lenders');
    Route::get('/owners', Profiles\Owners::class)->name('owners');
    Route::get('/employees', Profiles\Employees::class)->name('employees');
    Route::get('/payroll/{month?}', Profiles\PayrollRunPage::class)->name('payroll')->where('month', '\d{4}-\d{2}');
    Route::get('/create', Profiles\ProfileForm::class)->name('create');
    Route::get('/merge', Profiles\MergeDuplicates::class)->name('merge');
    Route::get('/import', Profiles\ImportProfiles::class)->name('import');
    Route::get('/documents/{document}', ProfileDocumentController::class)->name('documents.show');
    Route::get('/{profile}/edit', Profiles\ProfileForm::class)->name('edit');
    Route::get('/{profile}/statement', Profiles\ProfileStatement::class)->name('statement');
    Route::get('/{profile}', Profiles\ProfileShow::class)->name('show');
});
