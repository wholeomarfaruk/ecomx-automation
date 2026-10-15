# Accounts Dashboard — Livewire 3 + Tailwind + Alpine

## Files
```
app/Livewire/Accounts/Dashboard.php                     ← component (sample data + actions)
resources/views/livewire/accounts/dashboard.blade.php   ← page content
resources/views/partials/accounts-menu.blade.php        ← sidebar "Accounts" group
```

## 1. Route
```php
// routes/web.php
use App\Livewire\Accounts\Dashboard as AccountsDashboard;

Route::middleware(['auth'])->prefix('admin/accounts')->name('admin.accounts.')->group(function () {
    Route::get('/', AccountsDashboard::class)->name('dashboard');
});
```
The component uses your default Livewire layout (`config/livewire.php` → `layout`).
To use a different one, add `#[Layout('layouts.admin')]` above the class.

## 2. Sidebar
Master Profile is its own group, above Accounts (profiles are shared by Accounts, Purchase and Sales):
```blade
@include('partials.master-profile-menu', ['profileCount' => 234])
@include('partials.accounts-menu', ['pendingCount' => 5])
```
Master Profile links go to one list page filtered by role: `route('admin.profiles.index', ['role' => 'supplier'])`.
Rename the route names to match yours. Missing routes link to `#`.

## 3. Fonts (in your layout `<head>`)
```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
```

## 4. Tailwind
- Make sure `resources/views/**/*.blade.php` is in your Tailwind content paths (v3) / `@source` (v4).
- The checkboxes and date inputs look best with `@tailwindcss/forms`. They still work without it.

## 5. Replace sample data
Each `#[Computed]` method in `Dashboard.php` returns sample arrays. Swap in real queries and keep the same keys.
Use `[$start, $end] = $this->range;` to apply the date filter (Today / Yesterday / This week (Sat–Fri) / This month / This year / Custom).
Money is formatted with BD grouping by `$this->money()` → `৳17,75,250`.

`postVoucher()` / `postSelected()` have `TODO` lines where your `Voucher::post()` logic goes.
