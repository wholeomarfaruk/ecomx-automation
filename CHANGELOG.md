# Changelog

All notable changes to this project will be documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

---

## [1.0.3] — 2026-07-01

### Added
- `CHANGELOG.md` — project changelog following Keep a Changelog format

---

## [1.0.2] — 2026-07-01

### Added
- `APP_VERSION` key in `config/app.php` and `.env.example`
- Full "Add New User" modal with all profile fields (gender, phone, country code, address, bio)
- Panel assignment toggle in user create modal
- Phone code picker Alpine.js component (`_phone-code-picker.blade.php`) with live search and country flag
- Activity logging for Users module — created, updated (only changed fields), deleted, role changed, panel assigned/removed, avatar changed, email/phone verification toggled, password reset
- Activity logging for Uploads module — file uploaded, file deleted
- Sidebar logo section using `site_logo_white`, `site_logo_symbol`, and `site_name` from system settings
- Sidebar collapse toggle button with `localStorage` state persistence

### Changed
- Gender and country code fields in create/edit user modals now use system DB tables (`genders`, `countries`)
- Country code dropdown deduplicated by `phone_code` via `->unique('phone_code')->values()`
- `UserService::create()` now stores `gender`, `phone`, `country_code`, `address`, `bio`
- Removed Laravel Policies (`UserPolicy`, `RolePolicy`) — access control is fully DB-driven via `user->panels()` relationship
- Sidebar partial cleaned up — removed all commented-out menu sections and inline HTML comments

### Fixed
- `++880` double-plus in phone code picker — `phone_code` in DB already includes `+` prefix, removed manual prepend
- Phone number display in user view modal no longer manually prepends `+`

---

## [1.0.1] — 2026-07-01

### Added
- Activity Log module with Spatie Laravel Activitylog
- `AuthActivityListener` — tracks login, logout, failed login, password reset events
- `ActivityLog` model with `ip_address` and `user_agent` auto-fill via `AppServiceProvider`
- Permissions system — Roles (list, create, edit) and Panels management Livewire components
- Countries and Genders settings modules (`app/Livewire/Admin/Settings/`)
- System Settings module
- `Panel` model and `PanelMiddleware`
- `RoleService` for role/permission management
- `PanelSeeder`, `PermissionSeeder`
- Migrations: panels table columns, activity log tracking columns
- Custom `LoginResponse` redirecting to admin dashboard

### Changed
- Admin layout and sidebar navigation structure
- `AppServiceProvider` registers activity log auto-fill and auth event listeners
- Routes updated for permissions and activity log

---

## [1.0.0] — 2026-07-01

### Added
- Initial Laravel Starter Kit
- Laravel 11 + Livewire v3 base setup
- Spatie Permission (roles & permissions)
- Admin panel with sidebar navigation
- User management module
- Profile module
- Country and Gender seeders
- Fortify authentication with custom login response
