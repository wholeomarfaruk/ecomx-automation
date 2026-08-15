# Laravel Starter Kit — Senior Architect Review

**Reviewed by:** Senior Laravel SaaS Architect (Claude Sonnet 4.6)
**Date:** 2026-06-30
**Project:** Laravel Livewire Starter Kit
**Stack:** Laravel 12 · Livewire v3 · Flux · TailwindCSS v4 · Spatie Permission · Jetstream · Fortify · Sanctum

---

## 1. Overall Score: 4.2 / 10

This is an honest score. The project is a **clean, well-organized bootstrapped shell** built on a good foundation (Laravel 12, Livewire v3, Spatie, Jetstream). But it is **nowhere near a production-ready SaaS starter kit**. It is currently a starter *template*, not a starter *platform*. The gap between what exists and what a universal SaaS foundation needs is significant. This review will tell you exactly what that gap is and how to close it.

---

## 2. Strengths

These are genuine, not padded:

- **Modern stack selection is correct.** Laravel 12 + Livewire v3 + Flux + TailwindCSS v4 + Vite is the right foundation for 2025–2027.
- **Multi-panel architecture is clever.** The `Panel` model + `PanelMiddleware` + `panel_user` pivot is an elegant way to segregate admin, manager, customer, and tenant panels. This is a standout design decision.
- **Spatie permission integration is wired properly.** Seeders, role hierarchy, and permission namespacing (`user.*`, `role.*`, `panel.*`) are well thought out.
- **Chunked file upload system.** FilePond + chunked upload controller + `File`/`FileItem` model split is a solid, production-grade pattern for handling large file uploads.
- **CI/CD is configured out of the box.** GitHub Actions with lint + matrix test (PHP 8.4/8.5) is more than most starter kits ship with.
- **Composer scripts are developer-friendly.** `composer setup`, `composer dev`, `composer test` are the right DX shortcuts.
- **27 tests are present.** They're all Jetstream defaults, but the infrastructure is there.
- **Authentication is complete.** 2FA, email verification, password reset, Sanctum API tokens, all wired.

---

## 3. Weaknesses

These are architectural weaknesses, not style issues:

### 3.1 No Application Architecture Layer
There are no Services, Repositories, DTOs, Contracts, Observers, Value Objects, or Form Requests. **All business logic lives inside Livewire components.** This does not scale. The moment you add a second module, you will start duplicating validation logic, query logic, and mutation logic across components. This is the single biggest architectural flaw.

### 3.2 No Modular Structure
Everything is flat inside `app/`. There is no module system (`nwidart/laravel-modules` or a custom implementation). When this kit grows to include Billing, CRM, HRM, Inventory etc., the flat structure becomes unmaintainable. A `Modules/` or `Domain/` folder structure is not optional for a universal SaaS starter.

### 3.3 No Tenant Awareness
There is zero multi-tenancy scaffolding. No `tenant_id` columns, no tenant-scoped queries, no tenant middleware, no tenant configuration switching. For a kit targeting ERP, CRM, HRM, and SaaS platforms, this is a critical omission.

### 3.4 No System/App Settings
There is no settings system. No database-driven config, no `settings` table, no typed settings classes, no `app_settings` or `system_settings` infrastructure. Every SaaS needs this on day one.

### 3.5 No Audit / Activity Log
No `spatie/laravel-activitylog`. No `activity_log` table. No login history. No event sourcing foundation. Every SaaS that handles business data needs an audit trail.

### 3.6 No API Infrastructure
One route (`GET /api/user`) does not constitute an API layer. No API versioning, no API resources/transformers, no API response envelope, no API error format, no webhook system, no rate-limiting configured per endpoint.

### 3.7 Business Logic in Components
`Users.php`, `RoleCreate.php`, `RoleEdit.php` all contain direct Eloquent queries and business mutations. This violates separation of concerns and makes testing, reuse, and refactoring very difficult.

### 3.8 No Soft Deletes Anywhere
Not a single model uses `SoftDeletes`. No trash/restore pattern. For any SaaS handling business data, hard-deleting records is unacceptable.

### 3.9 No UUID Support
All models use auto-increment integer IDs. For a multi-tenant SaaS, exposing sequential integers in URLs and API responses is a security risk and makes cross-tenant ID collision a real problem.

### 3.10 No Background Job Infrastructure
No custom jobs, no queued notifications, no observers, no scheduled commands beyond the default `inspire`. The queue system is configured but empty.

### 3.11 No Policies
Authorization is handled only through Spatie permission checks in middleware. No Laravel Policies are registered. Policies are the correct Laravel-native pattern for model-level authorization and should not be skipped.

### 3.12 Duplicate Frontend Libraries
Both **Swiper** and **Splide** are included. They do the same job. Pick one. jQuery is also included alongside Alpine.js — these philosophies conflict, and jQuery should be dropped from a Livewire-first project.

### 3.13 No Form Request Classes
All validation is inline in Livewire components using `$this->validate()`. There are no `app/Http/Requests/` classes for any feature. This makes validation logic unshared and untestable in isolation.

### 3.14 No Database Indexes
Foreign keys (`file_id`, `panel_id`, `user_id`) in migrations have no explicit indexes declared. At scale this causes full table scans on every join.

---

## 4. Missing Features

Grouped by priority:

### Critical Missing (Day-1 SaaS needs)
- Settings system (system + tenant + user level)
- Activity / audit log
- Notification center (database + email + SMS)
- Soft deletes + trash + restore
- UUID primary keys or ULID
- Service layer (no business logic in components)
- Form request classes
- Laravel Policies
- Multi-tenancy scaffolding (even optional)
- API versioning (`/api/v1/`)
- API resources (JSON transformers)
- Global exception handler with structured error responses
- Custom 404 / 500 / 403 error pages

### High Priority Missing
- Subscription / billing (Laravel Cashier or Paddle)
- Email template system (database-driven)
- Import / Export (Excel, CSV)
- PDF generation (Laravel DomPDF or Browsershot)
- Global search infrastructure
- Feature flags
- Webhooks (inbound + outbound)
- Countries / currencies / timezones / languages seeder tables
- Telescope or Debugbar (dev environment only)
- Laravel Horizon (queue monitoring)
- Health check endpoint

### Medium Priority Missing
- Dynamic settings (key-value config table)
- Tags / categories / labels (polymorphic)
- Comments / notes (polymorphic)
- Attachments (polymorphic, beyond current File system)
- Custom fields system
- Announcement / broadcast system
- Media optimizer (image resizing, WebP conversion)
- Backup system (spatie/laravel-backup)
- Log viewer UI
- Failed jobs UI
- Cron dashboard
- Rate limiting per API route

### Lower Priority Missing
- AI-ready service layer
- Workflow / automation engine
- Plugin/module marketplace
- WebSocket / Reverb integration
- PWA manifest
- Low-code form builder
- Dynamic approval flows

---

## 5. Recommended Improvements

### 5.1 Introduce a Service + Repository Layer Immediately

```
app/
  Services/
    User/UserService.php
    Role/RoleService.php
    File/FileService.php
  Repositories/
    Contracts/UserRepositoryInterface.php
    Eloquent/UserRepository.php
  DTOs/
    User/CreateUserDTO.php
    User/UpdateUserDTO.php
```

Every Livewire component should call a Service. Services call Repositories. No Eloquent in components.

### 5.2 Introduce a Module Structure

```
app/
  Modules/
    Auth/
    Users/
    Permissions/
    Files/
    Settings/
    Notifications/
    AuditLog/
```

Or use `nwidart/laravel-modules` for fully isolated module packages.

### 5.3 Add a Settings System

```php
// Typed settings using spatie/laravel-settings or a custom pattern
class GeneralSettings extends Settings {
    public string $site_name;
    public string $timezone;
    public string $default_currency;
    public string $date_format;
}
```

### 5.4 Add Soft Deletes to Every Model

Every model that stores user-facing data must have `SoftDeletes`. Add a `DeletedBy` observer pattern for audit purposes.

### 5.5 Switch to ULIDs

Replace integer primary keys with ULIDs (`$table->ulid('id')->primary()`). ULIDs are URL-safe, time-sortable, and don't expose sequence.

### 5.6 Add Activity Logging

```bash
composer require spatie/laravel-activitylog
```

Log all CRUD operations automatically via model observers. Store `causer_id`, `subject_type`, `subject_id`, `event`, `properties`, `ip`, `user_agent`.

### 5.7 Build a Proper API Layer

```
routes/
  api/
    v1/
      auth.php
      users.php
      settings.php

app/Http/Resources/
  UserResource.php
  RoleResource.php

app/Http/Requests/
  User/CreateUserRequest.php
  User/UpdateUserRequest.php
```

### 5.8 Register All Policies

```php
class UserPolicy {
    public function view(User $auth, User $target): bool {}
    public function update(User $auth, User $target): bool {}
    public function delete(User $auth, User $target): bool {}
}
```

Policies + Spatie permissions used together (policies call `$user->can()` which checks Spatie).

### 5.9 Add Database Indexes

```php
$table->index(['user_id', 'created_at']);
$table->index(['type', 'created_at']);
$table->index(['tenant_id', 'deleted_at']); // for tenant scoping
```

### 5.10 Drop jQuery and One Carousel Library

Remove `jquery` and pick either `swiper` or `splide`. The mixed philosophy creates an inconsistent developer experience.

---

## 6. Nice-to-Have Features

- AI service abstraction layer (pluggable LLM provider)
- Drag-and-drop menu builder
- Dynamic form builder (JSON-schema driven)
- Kanban board component
- Timeline component
- Real-time notifications via Reverb/WebSockets
- Advanced report builder
- PWA manifest + service worker
- Plugin/module marketplace scaffolding
- QR code generator
- Short URL generator (useful in many SaaS contexts)
- Markdown editor component
- Image cropper component
- Color picker component
- Multi-step wizard component

---

## 7. Must-Have Features Before v1.0

These are non-negotiable for a "universal SaaS foundation" label:

| # | Feature | Why |
|---|---|---|
| 1 | Service layer | Without it, every module will degrade into spaghetti |
| 2 | Settings system | Every SaaS needs configurable options |
| 3 | Activity + audit log | Required for compliance in any business app |
| 4 | Soft deletes everywhere | You cannot hard-delete business data |
| 5 | ULIDs / UUIDs | Sequential IDs expose business intelligence and create tenant risks |
| 6 | Multi-tenancy scaffold | Even optional, it must be architecturally prepared |
| 7 | Notification center | DB + email + queue-driven |
| 8 | Email template system | Every SaaS sends transactional emails |
| 9 | Import / Export | Every business module needs it |
| 10 | API versioning + resources | Any SaaS will need a mobile app or integration |
| 11 | Laravel Horizon | Queue visibility is a production requirement |
| 12 | Spatie Backup | Data loss is catastrophic |
| 13 | Health check endpoint | Required for any production deployment |
| 14 | Form Request classes | Validation must be separated from component logic |
| 15 | Custom error pages | User-facing 404/500/403 with your UI |
| 16 | Policies registered | Proper authorization layer |
| 17 | Countries/currencies seeder | Every SaaS has localization on day one |

---

## 8. Suggested Folder Structure Improvements

```
app/
├── Actions/               # Single-purpose action classes (keep, expand)
├── Console/
│   └── Commands/          # Custom Artisan commands
├── DTOs/                  # Data Transfer Objects
│   └── User/
│       ├── CreateUserDTO.php
│       └── UpdateUserDTO.php
├── Enums/                 # Expand significantly
│   ├── Status.php
│   ├── Currency.php
│   ├── UserStatus.php
│   └── File/
├── Events/                # Domain events
│   └── User/
│       └── UserCreated.php
├── Exceptions/            # Custom exceptions + Handler
│   ├── Handler.php
│   └── BusinessException.php
├── Helpers/               # Global helpers (keep)
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   └── Api/
│   │       └── V1/        # Versioned API controllers
│   ├── Middleware/
│   ├── Requests/          # Form Request classes (ADD NOW)
│   │   ├── User/
│   │   └── Role/
│   └── Resources/         # API Resource transformers (ADD NOW)
│       ├── UserResource.php
│       └── RoleResource.php
├── Jobs/                  # Queued jobs
│   └── User/
│       └── SendWelcomeEmail.php
├── Listeners/             # Event listeners
├── Livewire/              # Keep, but thin — delegate to Services
├── Models/                # Expand significantly
│   ├── User.php
│   ├── Panel.php
│   ├── Setting.php
│   ├── Notification.php
│   ├── ActivityLog.php
│   └── File.php
├── Modules/               # Domain modules (new top-level concept)
│   ├── Settings/
│   │   ├── Models/
│   │   ├── Services/
│   │   └── Livewire/
│   ├── Notifications/
│   ├── AuditLog/
│   └── Media/
├── Notifications/         # Laravel notification classes
├── Observers/             # Model observers
│   └── UserObserver.php
├── Policies/              # Authorization policies (ADD NOW)
│   ├── UserPolicy.php
│   └── RolePolicy.php
├── Providers/             # Service providers (expand)
│   ├── AppServiceProvider.php
│   ├── AuthServiceProvider.php   # ADD for policies
│   ├── EventServiceProvider.php  # ADD for events
│   └── RouteServiceProvider.php  # ADD for route config
├── Repositories/          # Data access layer (ADD NOW)
│   ├── Contracts/
│   │   └── UserRepositoryInterface.php
│   └── Eloquent/
│       └── UserRepository.php
├── Services/              # Business logic layer (ADD NOW)
│   ├── User/
│   │   └── UserService.php
│   ├── Role/
│   │   └── RoleService.php
│   ├── File/
│   │   └── FileService.php
│   └── Settings/
│       └── SettingsService.php
├── Support/               # Supporting classes
│   ├── Traits/
│   │   ├── HasUlid.php
│   │   ├── HasSoftDeleteBy.php
│   │   └── HasTenant.php
│   └── ValueObjects/
└── View/
    └── Components/

database/
├── factories/             # Expand to all models
├── migrations/
└── seeders/
    ├── DatabaseSeeder.php
    ├── CountrySeeder.php    # ADD
    ├── CurrencySeeder.php   # ADD
    ├── TimezoneSeeder.php   # ADD
    ├── LanguageSeeder.php   # ADD
    └── SettingsSeeder.php   # ADD

routes/
├── web.php
├── api.php
├── admin.php
├── console.php
└── api/
    └── v1/               # ADD versioned API routes

Modules/                  # Optional top-level module directory
├── Billing/
├── CRM/
├── HRM/
└── Inventory/
```

---

## 9. Suggested New Modules

These should ship as part of the starter kit core:

| Module | Tables | Priority |
|---|---|---|
| **Settings** | `settings` (key/value + group + type) | Critical |
| **Activity Log** | `activity_log` | Critical |
| **Notifications** | `notifications` + `notification_templates` | Critical |
| **Countries** | `countries`, `states`, `cities` | High |
| **Currencies** | `currencies` | High |
| **Languages** | `languages` | High |
| **Email Templates** | `email_templates` | High |
| **Announcements** | `announcements` | Medium |
| **Tags** | `tags`, `taggables` (polymorphic) | Medium |
| **Categories** | `categories` (nested set) | Medium |
| **Custom Fields** | `custom_fields`, `custom_field_values` | Medium |
| **Addresses** | `addresses` (polymorphic) | Medium |
| **Comments/Notes** | `comments` (polymorphic) | Medium |
| **Attachments** | Extend existing File to be polymorphic | Medium |
| **Login History** | `login_histories` | Medium |
| **API Webhooks** | `webhooks`, `webhook_logs` | Medium |
| **Feature Flags** | `feature_flags` | Medium |
| **Tasks** | `tasks`, `task_comments` | Low |
| **Templates** | `templates` (email/SMS/PDF) | Low |

---

## 10. Suggested New UI Components

These are missing from the current 29 Blade components:

| Component | Why |
|---|---|
| `<x-data-table>` | Every SaaS module needs a standardized table with sort/filter/pagination |
| `<x-slide-over>` | Side panel pattern used in modern SaaS UIs |
| `<x-stats-card>` | Dashboard metric widget |
| `<x-skeleton>` | Loading skeleton for data-heavy pages |
| `<x-empty-state>` | Consistent zero-data UX |
| `<x-badge>` | Status chips and labels |
| `<x-avatar>` | User avatar with fallback initials |
| `<x-rich-editor>` | TipTap or Quill rich text integration |
| `<x-date-range-picker>` | Flatpickr dual-date range |
| `<x-select-search>` | Searchable select / combobox |
| `<x-color-picker>` | Used in labels, categories, statuses |
| `<x-tag-input>` | Comma-separated tag entry |
| `<x-progress-bar>` | Usage indicators, upload progress |
| `<x-timeline>` | Activity feed rendering |
| `<x-chart-bar>` / `<x-chart-line>` | Chart.js wrappers |
| `<x-confirm-action>` | Replaces inline SweetAlert calls |
| `<x-permission-gate>` | Blade directive wrapper for `@can` |
| `<x-copy-to-clipboard>` | API key display, etc. |
| `<x-kbd>` | Keyboard shortcut display |
| `<x-breadcrumb>` | Navigation breadcrumb component |

---

## 11. Suggested New Developer Tools

| Tool | Package / Approach |
|---|---|
| **Laravel Telescope** | Dev-only request/query/job inspector |
| **Laravel Horizon** | Queue monitoring dashboard |
| **Laravel Pulse** | Production performance monitoring (Laravel 11+) |
| **Spatie Backup** | Automated database + file backups |
| **Spatie Activity Log** | Audit trail for all model events |
| **Spatie Settings** | Typed settings classes |
| **Spatie Query Builder** | API filtering, sorting, includes |
| **Laravel IDE Helper** | PHPDoc generation for Eloquent models |
| **PHPStan / Larastan** | Static analysis (level 5+) |
| **Pest PHP** | Replace PHPUnit with Pest for cleaner tests |
| **make:module command** | Custom Artisan scaffold for new modules |
| **make:service command** | Artisan stub for Service classes |
| **make:dto command** | Artisan stub for DTO classes |
| **Debugbar** | Dev-only query/performance overlay |

---

## 12. Suggested Performance Improvements

| Area | Current State | Recommended Fix |
|---|---|---|
| **N+1 Queries** | No eager loading patterns enforced | Add `$with` defaults on models; enforce with Telescope in dev |
| **Database Indexes** | No FK indexes declared | Add index on every FK column + common filter columns |
| **Redis** | Configured but not actively used | Move session, cache, queue to Redis in `.env.example` |
| **Query Caching** | Not used | Cache permission/role lookups (Spatie does this, but configure TTL) |
| **Model Caching** | Not used | Add `remember()` pattern or Redis cache for frequently-read entities |
| **Image Optimization** | Raw upload storage | Add image resizing + WebP conversion job on upload |
| **Chunk Processing** | Not present | Add `cursor()` and `chunk()` patterns to any bulk operations |
| **Asset Optimization** | Vite build only | Add image lazy loading, component-level JS splitting |
| **Livewire Polling** | Not checked but risk | Avoid any `wire:poll` in favor of Livewire events or Echo |
| **Pagination** | `WithPagination` used | Add cursor pagination option for large datasets |
| **Queue Workers** | Not configured | Add queue worker config to deployment docs + Horizon |
| **Background Jobs** | Empty | Move file processing, emails, exports to jobs |

---

## 13. Suggested Security Improvements

| Area | Current State | Gap |
|---|---|---|
| **Rate Limiting** | Login + 2FA rate limited via Fortify | API routes have no rate limiting; add `throttle:api` and custom throttles per endpoint |
| **ULID / UUID** | Integer IDs exposed | Enumerable integer IDs allow scraping; switch to ULIDs |
| **SQL Injection** | Eloquent used (safe) | Enforce no raw queries; add PHPStan rule |
| **XSS** | Blade escapes by default | Audit all `{!! !!}` usages; add CSP headers |
| **CSRF** | Laravel default CSRF | Confirm SPA/API routes exclude CSRF correctly |
| **File Upload Security** | Type based on extension | Extension-based type detection can be spoofed; validate MIME type server-side |
| **Signed URLs** | Not implemented | Add signed URLs for file downloads |
| **Password Policy** | 12-char+ in production | Good, but add breach detection (haveibeenpwned) |
| **2FA Enforcement** | Optional | Allow per-role forced 2FA (superadmin must have 2FA) |
| **Session Security** | Default | Set `SameSite=Strict`, short absolute timeout |
| **Secrets** | `.env.example` only | Add secret rotation reminder and Vault/AWS SSM mention in docs |
| **Audit Trail** | Not present | No way to detect or investigate a breach |
| **Content Security Policy** | Not set | Add CSP middleware for XSS hardening |
| **API Token Scopes** | Jetstream basic scopes | Expand token scopes to align with Spatie permissions |
| **Mass Assignment** | `$fillable` used | Good. Ensure `$guarded = []` is never used |
| **Authorization** | Spatie only | Add Policies for all models for defense-in-depth |

---

## 14. Long-Term Roadmap

### Phase 1 — Foundation Hardening (v0.5, before v1.0)
1. Introduce Service + Repository layer
2. Add Form Request classes for all mutations
3. Register Policies for all models
4. Add soft deletes to all models
5. Switch to ULIDs
6. Add Settings module (Spatie Settings)
7. Add Activity Log (Spatie Activity Log)
8. Add database indexes on all FKs
9. Drop jQuery + one carousel library
10. Add custom error pages (404, 403, 500)

### Phase 2 — Core SaaS Modules (v0.8)
11. Countries / Currencies / Languages / Timezones seeders
12. Notification center (DB + email + queue)
13. Email template system
14. Import / Export (Maatwebsite Excel, PDF)
15. Announcement system
16. Tags / Categories (polymorphic)
17. Login history
18. Health check endpoint
19. API v1 with resources
20. Webhook system

### Phase 3 — Developer Experience (v1.0)
21. Laravel Horizon integration
22. Laravel Telescope (dev only)
23. Laravel Pulse
24. Spatie Backup
25. Custom `make:service`, `make:dto`, `make:module` commands
26. Pest PHP migration
27. PHPStan / Larastan at level 5
28. IDE helper generation
29. Comprehensive documentation
30. CLI-driven module scaffolding

### Phase 4 — Advanced Platform Features (v1.5)
31. Multi-tenancy (Spatie Multitenancy or stancl/tenancy)
32. Feature flags (Pennant)
33. Laravel Reverb (WebSockets)
34. PWA manifest
35. Subscription / billing (Cashier or Paddle)
36. Plugin system architecture
37. AI service abstraction layer
38. Workflow engine (rule-based automation)
39. Dynamic form builder
40. Low-code module generator

### Phase 5 — Enterprise & Future (v2.0+)
41. Module marketplace
42. Event sourcing option (Spatie EventSourcing)
43. API-first / headless mode
44. Microservice extraction patterns
45. SSO / SAML / LDAP support
46. Advanced reporting engine
47. AI-powered features (auto-categorization, smart search)
48. Mobile API layer (full REST + documentation)
49. Open API / Swagger documentation auto-generation

---

## 15. Final Verdict

**This is a well-intentioned, clean starting point — but it is not a SaaS starter kit yet. It is a Laravel Jetstream install with a custom file uploader and a panel-based access system.**

The strongest thing about it is the **multi-panel architecture** — that is a genuine, non-obvious design decision that will pay off when you add manager panels, tenant panels, and customer portals. Do not change that.

The weakest thing is that **the entire application layer (Services, Repositories, DTOs, Policies, Events, Observers, Jobs) is absent.** Without these, every feature you add will deposit business logic directly into Livewire components, and within 3–4 modules the codebase will become impossible to maintain or test.

**Before calling this v1.0**, you need at minimum: a service layer, soft deletes, ULIDs, a settings module, an audit log, form request classes, proper API versioning, and the core reference data (countries, currencies, timezones). Everything else on the roadmap is additive.

The good news: the foundation is modern, the stack choices are correct, and the panel system is a smart differentiator. With the architectural additions described above, this could genuinely become a world-class SaaS starter kit in 3–4 months of focused work.

| Metric | Status |
|---|---|
| **Current state** | A developer template |
| **Target state** | A production SaaS platform foundation |
| **Gap** | Large but closeable with a clear roadmap |
| **Overall Score** | 4.2 / 10 |
| **Score after Phase 1** | ~6.5 / 10 (estimated) |
| **Score after Phase 3 (v1.0)** | ~8.5 / 10 (estimated) |

---

*Review completed against codebase state as of 2026-06-30.*
*Reviewed by Claude Sonnet 4.6 — Senior Laravel SaaS Architect role.*
