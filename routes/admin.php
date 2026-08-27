<?php

use App\Http\Controllers\Admin\CompanyPrintController;
use App\Http\Controllers\Admin\FileUploadController;
use App\Livewire\Admin\Frontend\Appearance as FrontendAppearance;
use App\Livewire\Admin\Frontend\Components as FrontendComponents;
use App\Livewire\Admin\Frontend\Menus as FrontendMenus;
use App\Livewire\Admin\Frontend\PageShow as FrontendPageShow;
use App\Livewire\Admin\Frontend\Pages as FrontendPages;
use App\Livewire\Admin\Frontend\Themes as FrontendThemes;
use App\Livewire\Admin\LandingPages\PageForm as LandingPageForm;
use App\Livewire\Admin\LandingPages\PageList as LandingPageList;
use App\Livewire\Admin\LandingPages\Settings as LandingPageSettings;
use App\Livewire\Admin\LandingPages\TemplateList as LandingPageTemplateList;
use App\Livewire\Admin\ThemeEngine\SectionConfigPage;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', \App\Livewire\Admin\Dashboard\Dashboard::class)->name('dashboard');

// Frontend Menu — registered pages of the active theme + per-page section settings.
// The {page} wildcard must stay registered after the literal routes below it,
// or it will shadow appearance/themes/menus/components. Likewise
// {page}/{section}/edit must stay above the bare {page} wildcard.
Route::get('/frontend', FrontendPages::class)->name('frontend.menu');
Route::get('/frontend/appearance', FrontendAppearance::class)->name('frontend.appearance');
Route::get('/frontend/themes', FrontendThemes::class)->name('frontend.themes');
Route::get('/frontend/menus', FrontendMenus::class)->name('frontend.menus');
Route::get('/frontend/components', FrontendComponents::class)->name('frontend.components');
Route::get('/frontend/{page}/{section}/edit', SectionConfigPage::class)->name('frontend.section.edit');
Route::get('/frontend/{page}', FrontendPageShow::class)->name('frontend.menu.show');

Route::prefix('landing-pages')->name('landingpages.')->group(function () {
    Route::get('/', LandingPageList::class)->name('pages');
    Route::get('/create', LandingPageForm::class)->name('pages.create');
    Route::get('/{id}/edit', LandingPageForm::class)->name('pages.edit');
    Route::get('/{id}/preview', [\App\Http\Controllers\Admin\LandingPagePreviewController::class, 'show'])->name('pages.preview');
    Route::get('/templates', LandingPageTemplateList::class)->name('templates');
    Route::get('/settings', LandingPageSettings::class)->name('settings');
});

//user managements
Route::get('/users', App\Livewire\Admin\Users\Users::class)->name('users');
Route::get('/users/devices', App\Livewire\Admin\Users\DeviceList::class)->name('users.devices');
Route::get('/users/devices/{id}', App\Livewire\Admin\Users\DeviceDetail::class)->name('users.devices.show');
Route::get('/users/details', App\Livewire\Admin\Users\UserDetail::class)->name('users.show');
Route::get('/users/blocks', App\Livewire\Admin\Users\BlockList::class)->name('users.blocks');
Route::get('/users/active', App\Livewire\Admin\Users\ActiveList::class)->name('users.active');

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
Route::get('/site-settings/branches',   App\Livewire\Admin\Settings\Branches::class)->name('settings.branches');
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
    Route::get('/invoices', App\Livewire\Admin\Purchase\SupplierInvoices::class)->name('invoices');
    Route::get('/orders', App\Livewire\Admin\Purchase\PurchaseOrders::class)->name('orders');
    Route::get('/orders/create', App\Livewire\Admin\Purchase\PurchaseOrderForm::class)->name('orders.create');
    Route::get('/orders/{id}/edit', App\Livewire\Admin\Purchase\PurchaseOrderForm::class)->name('orders.edit');
});

// Customers
Route::prefix('customers')->name('customers.')->group(function () {
    Route::get('/', App\Livewire\Admin\Customers\CustomerList::class)->name('index');
    Route::get('/carts', App\Livewire\Admin\Customers\Carts::class)->name('carts');
    Route::get('/carts/{id}', App\Livewire\Admin\Customers\CartDetail::class)->name('carts.show');
    Route::get('/combo', App\Livewire\Admin\Customers\ComboList::class)->name('combo');
    Route::get('/loved', App\Livewire\Admin\Customers\LovedProducts::class)->name('loved');
    Route::get('/groups', App\Livewire\Admin\Customers\CustomerGroups::class)->name('groups');

    Route::get('/reviews', App\Livewire\Admin\Customers\Reviews\ReviewList::class)->name('reviews.index');
    Route::get('/reviews/settings', App\Livewire\Admin\Customers\Reviews\ReviewSettings::class)->name('reviews.settings');
    Route::get('/reviews/{id}', App\Livewire\Admin\Customers\Reviews\ReviewDetail::class)->name('reviews.show');
});

// Sales
Route::prefix('sales')->name('sales.')->group(function () {
    Route::get('/orders', App\Livewire\Admin\Sales\Orders::class)->name('orders');
    Route::get('/orders/create', App\Livewire\Admin\Sales\OrderCreate::class)->name('orders.create');
    Route::get('/orders/{id}', App\Livewire\Admin\Sales\OrderDetail::class)->name('orders.show');

    Route::get('/coupons', App\Livewire\Admin\Sales\Coupons::class)->name('coupons');
    Route::get('/coupons/create', App\Livewire\Admin\Sales\CouponCreate::class)->name('coupons.create');
    Route::get('/coupons/usages', App\Livewire\Admin\Sales\CouponUsages::class)->name('coupons.usages');
    Route::get('/coupons/{id}', App\Livewire\Admin\Sales\CouponDetail::class)->name('coupons.show');

    Route::get('/offers', App\Livewire\Admin\Sales\Offers::class)->name('offers');
    Route::get('/offers/create', App\Livewire\Admin\Sales\OfferCreate::class)->name('offers.create');
    Route::get('/offers/{id}', App\Livewire\Admin\Sales\OfferDetail::class)->name('offers.show');

    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/registers', App\Livewire\Admin\Sales\PosRegisters::class)->name('registers');
        Route::get('/sessions', App\Livewire\Admin\Sales\PosSessions::class)->name('sessions');
        Route::get('/sessions/open', App\Livewire\Admin\Sales\PosSessionDetail::class)->name('sessions.open');
        Route::get('/sessions/{id}', App\Livewire\Admin\Sales\PosSessionDetail::class)->name('sessions.show');
        Route::get('/screen', App\Livewire\Admin\Sales\PosScreen::class)->name('screen');
    });
});

Route::prefix('marketing')->name('marketing.')->group(function () {
    Route::get('/', App\Livewire\Admin\Marketing\Dashboard::class)->name('dashboard');

    Route::get('/journeys/visitors', App\Livewire\Admin\Marketing\JourneyExplorer::class)->name('journeys.visitors')->defaults('mode', 'visitors');
    Route::get('/journeys/customers', App\Livewire\Admin\Marketing\JourneyExplorer::class)->name('journeys.customers')->defaults('mode', 'customers');
    Route::get('/journeys/anonymous', App\Livewire\Admin\Marketing\JourneyExplorer::class)->name('journeys.anonymous')->defaults('mode', 'anonymous');
    Route::get('/journeys/detail', App\Livewire\Admin\Marketing\JourneyDetail::class)->name('journeys.detail');

    Route::get('/campaigns', App\Livewire\Admin\Marketing\Campaigns::class)->name('campaigns.index');
    Route::get('/campaigns/meta', App\Livewire\Admin\Marketing\Campaigns::class)->name('campaigns.meta')->defaults('platform', 'meta');
    Route::get('/campaigns/google', App\Livewire\Admin\Marketing\Campaigns::class)->name('campaigns.google')->defaults('platform', 'google');
    Route::get('/campaigns/tiktok', App\Livewire\Admin\Marketing\Campaigns::class)->name('campaigns.tiktok')->defaults('platform', 'tiktok');
    Route::get('/campaigns/other', App\Livewire\Admin\Marketing\Campaigns::class)->name('campaigns.other')->defaults('platform', 'other');

    Route::get('/sources', App\Livewire\Admin\Marketing\SourceAnalytics::class)->name('sources.index');

    Route::get('/products', App\Livewire\Admin\Marketing\ProductPerformance::class)->name('products.performance');
    Route::get('/products/journeys', App\Livewire\Admin\Marketing\ProductJourneys::class)->name('products.journeys');
    Route::get('/customers', App\Livewire\Admin\Marketing\CustomerTracking::class)->name('customers.tracking');
    Route::get('/customers/returning', App\Livewire\Admin\Marketing\ReturningCustomers::class)->name('customers.returning');

    Route::get('/audience/devices', App\Livewire\Admin\Marketing\AudienceDevices::class)->name('audience.devices');
    Route::get('/audience/ip', App\Livewire\Admin\Marketing\AudienceIp::class)->name('audience.ip');

    Route::get('/events', App\Livewire\Admin\Marketing\EventExplorer::class)->name('events.index');

    Route::get('/attribution', App\Livewire\Admin\Marketing\Attribution::class)->name('attribution.index');

    Route::get('/reports', App\Livewire\Admin\Marketing\Reports::class)->name('reports.index');
    Route::get('/settings', App\Livewire\Admin\Marketing\Settings::class)->name('settings.index');
});

Route::prefix('inventory')->name('inventory.')->group(function () {
    Route::get('/', App\Livewire\Admin\Inventory\StockList::class)->name('stock');
    Route::get('/stock-in', App\Livewire\Admin\Inventory\StockIn::class)->name('stock-in');
    Route::get('/batches', App\Livewire\Admin\Inventory\Batches::class)->name('batches');
    Route::get('/movements', App\Livewire\Admin\Inventory\MovementList::class)->name('movements');
    Route::get('/warehouses', App\Livewire\Admin\Inventory\Warehouses::class)->name('warehouses');
    Route::get('/settings', App\Livewire\Admin\Inventory\InventorySettings::class)->name('settings');
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
