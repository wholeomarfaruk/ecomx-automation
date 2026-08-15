<?php

use App\Http\Controllers\Admin\CompanyPrintController;
use App\Http\Controllers\Admin\FileUploadController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', \App\Livewire\Admin\Dashboard\Dashboard::class)->name('dashboard');

//user managements
Route::get('/users', App\Livewire\Admin\Users\Users::class)->name('users');

// Profile and Settings
Route::get('/profile', App\Livewire\Admin\Profile\Profile::class)->name('profile');
Route::get('/settings', App\Livewire\Admin\Settings\Settings::class)->name('settings');

//permissions
Route::get('/permissions/roles', App\Livewire\Admin\Permissions\RoleList::class)->name('roles.list');
Route::get('/permissions/role/create', App\Livewire\Admin\Permissions\RoleCreate::class)->name('roles.create');
Route::get('/permissions/role/edit/{id}', App\Livewire\Admin\Permissions\RoleCreate::class)->name('roles.edit');
Route::get('/permissions/panels', App\Livewire\Admin\Permissions\PanelList::class)->name('permissions.panels');

// Site Settings
Route::get('/site-settings', App\Livewire\Admin\SiteSettings\SiteSettings::class)->name('site-settings');
Route::get('/site-settings/countries',  App\Livewire\Admin\Settings\Countries::class)->name('settings.countries');
Route::get('/site-settings/states',     App\Livewire\Admin\Settings\States::class)->name('settings.states');
Route::get('/site-settings/cities',     App\Livewire\Admin\Settings\Cities::class)->name('settings.cities');
Route::get('/site-settings/genders',    App\Livewire\Admin\Settings\Genders::class)->name('settings.genders');
Route::get('/site-settings/currencies', App\Livewire\Admin\Settings\Currencies::class)->name('settings.currencies');
Route::get('/site-settings/languages', App\Livewire\Admin\Localization\LanguageList::class)->name('settings.languages');
Route::get('/site-settings/languages/create', App\Livewire\Admin\Localization\LanguageForm::class)->name('settings.languages.create');
Route::get('/site-settings/languages/{id}/edit', App\Livewire\Admin\Localization\LanguageForm::class)->name('settings.languages.edit');
Route::get('/site-settings/company/print', CompanyPrintController::class)->name('company.print');

// Catalog
Route::prefix('catalog')->name('catalog.')->group(function () {
    Route::get('/categories', App\Livewire\Admin\Catalog\Categories::class)->name('categories');
    Route::get('/brands', App\Livewire\Admin\Catalog\Brands::class)->name('brands');
    Route::get('/products', App\Livewire\Admin\Catalog\Products::class)->name('products');
    Route::get('/products/{id}/edit', App\Livewire\Admin\Catalog\ProductEdit::class)->name('products.edit');
    Route::get('/attributes', App\Livewire\Admin\Catalog\Attributes::class)->name('attributes');
});

// Purchase
Route::prefix('purchase')->name('purchase.')->group(function () {
    Route::get('/suppliers', App\Livewire\Admin\Purchase\Suppliers::class)->name('suppliers');
    Route::get('/suppliers/{supplierId}/ledger', App\Livewire\Admin\Purchase\SupplierLedger::class)->name('suppliers.ledger');
    Route::get('/orders', App\Livewire\Admin\Purchase\PurchaseOrders::class)->name('orders');
});

// Advance
Route::get('/settings/advance/developer-tools', App\Livewire\Admin\DeveloperTools\DeveloperTools::class)->name('settings.advance.developer-tools');
Route::get('/settings/advance/system-health', App\Livewire\Admin\Advance\SystemHealth::class)->name('settings.advance.system-health');
Route::get('/settings/advance/license-configuration', App\Livewire\Admin\Advance\LicenseConfiguration::class)->name('settings.advance.license-configuration');

Route::prefix('settings/advance/sms-configuration')->name('settings.advance.sms-configuration.')->group(function () {
    Route::get('/', App\Livewire\Admin\Sms\Dashboard::class)->name('dashboard');
    Route::get('/configuration', App\Livewire\Admin\Sms\GatewayConfiguration::class)->name('configuration');
    Route::get('/templates', App\Livewire\Admin\Sms\Templates::class)->name('templates');
    Route::get('/logs', App\Livewire\Admin\Sms\MessageLogs::class)->name('logs');
    Route::get('/settings', App\Livewire\Admin\Sms\Settings::class)->name('settings');
    Route::get('/advanced', App\Livewire\Admin\Sms\Advanced::class)->name('advanced');
});

Route::prefix('settings/advance/email-configuration')->name('settings.advance.email-configuration.')->group(function () {
    Route::get('/', App\Livewire\Admin\Email\Providers::class)->name('providers');
    Route::get('/settings', App\Livewire\Admin\Email\Settings::class)->name('settings');
    Route::get('/templates', App\Livewire\Admin\Email\Templates::class)->name('templates');
    Route::get('/testing', App\Livewire\Admin\Email\Testing::class)->name('testing');
    Route::get('/logs', App\Livewire\Admin\Email\Logs::class)->name('logs');
});

Route::prefix('settings/advance/notification-configuration')->name('settings.advance.notification-configuration.')->group(function () {
    Route::get('/', App\Livewire\Admin\Notifications\Dashboard::class)->name('dashboard');
    Route::get('/channels', App\Livewire\Admin\Notifications\Channels::class)->name('channels');
    Route::get('/events', App\Livewire\Admin\Notifications\Events::class)->name('events');
    Route::get('/templates', App\Livewire\Admin\Notifications\Templates::class)->name('templates');
    Route::get('/queue', App\Livewire\Admin\Notifications\Queue::class)->name('queue');
    Route::get('/logs', App\Livewire\Admin\Notifications\Logs::class)->name('logs');
});
Route::post('/push/subscribe', App\Http\Controllers\Admin\PushSubscriptionController::class)->name('push.subscribe');

// Activity Log
Route::get('/activity-log', App\Livewire\Admin\ActivityLog\ActivityLog::class)->name('activity-log');

//uploads
Route::get('/uploads', App\Livewire\Admin\File\Uploads::class)->name('uploads');
Route::post('/upload', [FileUploadController::class, 'storeAdmin']);
Route::delete('/upload/revert', [FileUploadController::class, 'revertAdmin']);
