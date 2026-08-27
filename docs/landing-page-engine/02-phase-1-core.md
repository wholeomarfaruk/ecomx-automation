# Phase 1 — Core (Pages / Templates / Settings)

**Status: implemented this session.**

> **Path correction**: custom ("source": "custom") template packages live
> under `storage/app/public/landingpage-templates/{key}/`, **not**
> `storage/landingpage-templates/{key}/` as earlier in this doc.
> `storage/app/public` is web-reachable via the `storage:link` symlink
> (`public/storage`), matching every other public asset area (`uploads/`,
> `frontend/`) — this lets a custom template's own assets (`preview_image`,
> etc.) resolve to a real browser URL via
> `LandingPageTemplate::previewImageUrl()`. System templates are unchanged,
> still at `resources/landingpage-templates/{key}/` (never web-reachable —
> they're template source code, not public assets). Every mention of bare
> `storage/landingpage-templates/...` below is historical narrative from
> when the engine was first built; the three real path references in code
> (`LandingPageTemplate::basePath()`, `TemplateDiscoveryService::roots()`,
> `AppServiceProvider::boot()`'s `loadViewsFrom()`) all use the corrected
> `storage/app/public/landingpage-templates` path. **Known gap**:
> `storage/app/public/.gitignore` currently ignores everything except
> itself, so custom template packages placed there are not tracked by git
> — not yet fixed, flagged in `07-open-questions-and-decisions.md`.

## Goal

Give admins a way to register template packages, create/edit/duplicate/
publish landing pages from those templates, and serve them on real public
URLs — without yet building a visual drag-drop builder or any ecommerce
components. This is the minimum end-to-end slice: filesystem template →
DB-registered template → DB page instance → public render.

## Data model changes

### `landing_page_templates`

Registers the filesystem 4-file template packages
(`resources/landingpage-templates/*`, `storage/landingpage-templates/*`)
into a queryable DB index.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `key` | string, unique | matches the template's folder name |
| `name` | string | |
| `category` | string | free-form, e.g. `food-grocery`, `fashion`, `general` |
| `description` | text, nullable | |
| `preview_image` | string, nullable | filename relative to the template folder |
| `version` | string | semver, author-maintained |
| `status` | enum `active`\|`deprecated` | |
| `source` | enum `system`\|`custom` | stamped by discovery — never hand-set |
| `capabilities` | json, nullable | free-form flags (`products`, `packages`, `bkash_checkout`, ...) |
| timestamps | | |

**Explicitly no `author`/`author_url` columns** — that metadata already
lives in each template's own `config.json`; duplicating it into the DB row
is just another place for it to drift. Anything that needs to show author
info reads `config.json` directly at render time.

**Explicitly no `base_path` column** — the folder path is always derivable
from `source` + `key` (`system` → `resources/landingpage-templates/{key}`,
`custom` → `storage/landingpage-templates/{key}`). Storing it would be
redundant, derivable state that could silently go stale if a folder is ever
renamed outside of discovery.

Not hand-edited: this table is a mirror of what's on disk, refreshed by
`TemplateDiscoveryService` (see below).

### `landing_pages`

The actual DB source of truth for a landing page's metadata and content.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `name` | string | internal-only name |
| `title` | string | public/SEO title |
| `slug` | string, unique | |
| `landing_page_template_id` | FK → `landing_page_templates` | |
| `content` | json | the page's actual content — copied from the template's `content.json` on creation, then edited independently |
| `status` | enum `draft`\|`published`\|`unpublished` | |
| `seo` | json, nullable | `meta_title`, `meta_description`, `meta_image`, `canonical`, `og_*` |
| `header_mode` | enum `global`\|`custom`\|`none`, default `none` | |
| `footer_mode` | enum `global`\|`custom`\|`none`, default `none` | |
| `published_at` | datetime, nullable | |
| `created_by` | FK → `users`, nullable | |
| timestamps + `deleted_at` | | SoftDeletes, matching `Product`/`Category` precedent |

Indexes: unique `slug`, index on `status`.

**Why `content` is a single JSON column and not a normalized `sections`
table**: the template packages already store `content.json` as a flat
object (not literally the `{"sections": [...]}` array shape from the
original spec example — see the Note below), and Phase 2's builder will
edit this same column without needing a schema migration when it arrives.
Normalizing sections into rows is deferred until the builder actually needs
per-section identity (drag-reorder, hide/show, undo/redo) — see
`03-phase-2-builder.md`.

> **Note on content shape**: the spec's example (`{"version":1,"sections":[{"type":"hero","id":"hero_1",...}]}`)
> is the target shape for **builder-created** pages in Phase 2+. The
> existing 4 template packages predate that shape — their `content.json` is
> a flat `{"hero": {...}, "features": {...}, ...}` object keyed by section
> name, driven by `schema.json`'s field registry. Phase 1 renders pages
> using this existing flat shape as-is (no migration of the 4 templates).
> Phase 2 introduces the `sections` array shape for *new* templates built
> with the visual builder; both shapes can coexist since rendering is
> template-specific (`template.blade.php` decides what `$data` shape it
> expects).

## New/reused classes

### Models

- `App\Models\LandingPageTemplate` — no SoftDeletes (fully re-derivable
  from disk via discovery). `casts: capabilities => array`. `basePath()`
  accessor computes the folder path from `source`+`key` on the fly (never
  stored). Further accessors build the 4 file paths
  (`configPath()`, `schemaPath()`, `contentPath()`, `bladePath()`) on top
  of `basePath()`. `configData()` reads+decodes `config.json` on demand for
  `author`/`author_url` display.
- `App\Models\LandingPage` — SoftDeletes. `casts: content => array, seo =>
  array, published_at => datetime`. `belongsTo(LandingPageTemplate::class)`.
  `scopePublished()`. Slug generation follows the existing inline
  `Str::slug()`-on-name-change convention (no new trait) — see audit
  finding #19.

### Services

- `App\LandingPageEngine\TemplateDiscoveryService` — scans
  `resources/landingpage-templates/*` and `storage/landingpage-templates/*`,
  validates each folder has all 4 required files and a valid `key` regex
  (`^[a-z][a-z0-9]*(-[a-z0-9]+)*$`), upserts `LandingPageTemplate` rows by
  `key`, stamps `source` from which root it was found in, skips + logs
  invalid folders (never throws). Exposed via Artisan command
  `landingpage:discover-templates` and safe to re-run on demand (cheap
  glob + upsert, no queue needed).
- `App\LandingPageEngine\LandingPageRenderer` — given a `LandingPage`,
  resolves the template's `template.blade.php` absolute path (via
  `LandingPageTemplate::bladePath()`) and renders it with `View::file()`,
  passing `$data` built by a shallow recursive merge of the template's
  `content.json` defaults with the page's own `content` (so a page created
  before a template update doesn't break on missing keys). This is why
  `view_file` was dropped from `config.json` earlier — the blade path is
  always resolvable from the folder, never a hand-typed dotted string that
  can drift.

### Admin (Livewire, `app/Livewire/Admin/LandingPages/`)

Following the audited `admin.{module}.{resource}[.{action}]` convention and
`->layout('layouts.admin.admin')` / `$this->dispatch('toast', [...])`
patterns:

- `PageList` — `admin.landingpages.pages` (`/admin/landing-pages`). Table
  with search/status filter. Row actions: Edit, Duplicate, Publish/
  Unpublish, Delete (soft), Preview.
- `PageForm` — `admin.landingpages.pages.create` +
  `admin.landingpages.pages.edit`, shared component via
  `mount(?int $id = null)`. Fields: name/title/slug (auto-slug on name
  change), template picker (`LandingPageTemplate::active()`), SEO fields,
  header/footer mode, and a **flat form-per-schema-key editor** — loops the
  chosen template's `schema.json` sections/fields exactly the way
  `SectionConfigPage` loops `SectionSchema::fieldsFor()`. Reuses
  `WithMediaPicker`/`<x-media-picker-field>` for any schema field declaring
  `"type": "media"` (a small, backward-compatible addition to the schema
  type vocabulary — existing schemas only use `text`/`textarea` today).
- `TemplateList` — `admin.landingpages.templates`
  (`/admin/landing-pages/templates`). Read-only gallery list (name/
  category/preview/version/source badge/status), a "Rescan" button
  triggering `TemplateDiscoveryService`.
- `Settings` — `admin.landingpages.settings`
  (`/admin/landing-pages/settings`), backed by the existing `App\Models\Setting`
  model (`group: 'landingpage'`) — not a new settings table. Fields:
  default template, default SEO fallback, default header/footer mode.

### Sidebar

`resources/views/layouts/admin/partials/sidebar.blade.php` — "Frontend"
renamed to "Frontend Engine"; "LandingPage Engine" menu added with All
Pages / Templates / Settings items, wired to the routes above (previously
placeholder `href="#"` links from earlier in this session).

## Public route + rendering

- Registered in its own standalone `routes/landingpage.php`, `require`d
  directly from `routes/web.php` — **deliberately not** inside
  `routes/frontend/ecomxFashion.php` or any other theme/engine file. The
  Landing Page Engine is architecturally independent of whichever
  storefront theme/engine happens to be active (per `00-overview.md`'s
  separation rule) and must keep working even if the active engine
  changes, so it bypasses `App\FrontendEngine\EngineManager` entirely.
  `DeviceTracker`/`MarketingTracker`/`EnforceBlocks` are global `web`-group
  middleware (`bootstrap/app.php`) and apply automatically regardless of
  where a route is registered, so attribution capture (`landing_path`)
  still works with zero extra tracking code. `SetFrontendLocale`/
  `PreventPublicMaintenanceForStaff` are attached explicitly in
  `routes/landingpage.php` — reused because they're generic, non-theme-
  specific public-site concerns (locale switching, maintenance-mode staff
  bypass), the same way `Product`/`CartManager`/etc. are reused elsewhere,
  not because of any dependency on the ecomx-fashion engine.
- Route: `Route::get('/lp/{slug}', LandingPageShow::class)->name('landingpage.show')`.
  `/lp/` prefix chosen to avoid collision with existing fixed routes
  (`/shop`, `/category/{slug}`, `/product/{slug}`). Whether to move to a
  bare `/{slug}` later is an open decision — see
  `07-open-questions-and-decisions.md`.
- `App\Livewire\LandingPageEngine\LandingPageShow` is the **single** public
  entry point for every landing page, regardless of template. It
  deliberately does **not** use Livewire's `#[Layout(...)]` attribute —
  Livewire wraps a `#[Layout]`-attributed component's output in that
  layout unconditionally on every render (verified against
  `vendor/livewire/livewire/src/Features/SupportPageComponents`), with no
  per-request escape hatch. But a landing page's rendering strategy
  depends on `LandingPageTemplate::renderMode()`, which is only knowable
  *after* the slug lookup — a class-level attribute can't branch on that.
  So the branch lives in `App\LandingPageEngine\LandingPageRenderer::renderView()`
  instead — the single source of truth for both the public component and
  admin preview (`App\Http\Controllers\Admin\LandingPagePreviewController`,
  so preview always matches what a real visitor sees):
  - **`fragment`** mode (`config.json` `"render_mode": "fragment"` — e.g.
    `basic-promo`): the template's `template.blade.php` renders content
    only (no `<html>`/`<head>`/`<body>` of its own), and may optionally
    `@push('head')`/`@push('footer')` extra markup. `renderView()` returns
    `resources/views/landingpage-engine/fragment-wrapper.blade.php`, which
    `@include`s the template via the `landingpage-template::` view
    namespace (a real Blade `@include`, not a pre-rendered string —
    required for `@push`/`@stack` to reach across into the layout) and
    `@extends` `resources/views/layouts/landingpage/landingpage.blade.php`
    — that layout owns `<html>`/`<head>`/`<body>`, title/meta description/
    image (passed as plain view variables), `<x-marketing.gtm />`/
    `<x-marketing.gtm-noscript />`, `@livewireStyles`/`@livewireScripts`,
    `@stack('head')`/`@stack('footer')` targets, and
    `<x-device-fingerprint-script />` (the device-fingerprint bootstrap
    script, extracted this session into a reusable global Blade component
    so both this layout and the real storefront's `ecomx_fashion.blade.php`
    share one source of truth).
  - **`standalone`** mode (the default — `season-fresh-mango`,
    `seldom-zaynah-eid`, `seldom-zaynah-eid-v2`): `template.blade.php`
    renders a complete `<html>` document itself, exactly as before —
    `renderView()` returns `View::file($template->bladePath(), ...)`
    directly, with no layout wrapping.
  - Both branches return a real `Illuminate\View\View`, never a raw
    `Response` — Livewire's full-page dispatch calls `->with()` on
    whatever `render()` returns, which only `View` exposes (a raw
    `Response` throws `"Method Illuminate\Http\Response::with does not
    exist"`, discovered while implementing this). Both views happen to
    already produce one complete `<html>...</html>` root element each,
    satisfying Livewire's non-`#[Layout]` root-element check. Any Livewire
    child components a template embeds via `@livewire(...)` would be
    unaffected by this — each mounts and re-renders normally as its own
    independent Livewire island; only the single outer full-page
    component needed this resolution.
  - `LandingPageShow::mount()` 404s for anonymous visitors on draft/
    unpublished pages (`LandingPage::published()->where('slug', $slug)->firstOrFail()`).
    `LandingPagePreviewController` uses plain `findOrFail()` instead
    (auth-gated by `panel:admin`, so drafts are viewable there).
- **Consequence**: Phase 1's `header_mode`/`footer_mode` (on the
  `landing_pages` table) still default to `none` and remain unused by any
  of the 4 registered templates — even `basic-promo`'s new `fragment`
  layout is a landing-page-engine-owned shell (GTM/device-fingerprint/
  Livewire scripts), not the *storefront's* global header/footer
  partials. Wiring `header_mode: global` to actually include
  `ecomx-fashion.partials.header`/`footer` conditionally is still deferred
  — doing so today would reintroduce exactly the coupling to the
  ecomx-fashion engine this session removed everywhere else. If/when
  that's wanted, it should be resolved generically (e.g. asking the active
  `EngineManager` engine for its header/footer partials) rather than
  hard-coding `ecomx-fashion.partials.*` paths into the Landing Page
  Engine. Logged in `07-open-questions-and-decisions.md`.
- Preview: `admin.landingpages.pages.preview`, gated by the existing
  `panel:admin` middleware, renders draft content regardless of `status`.
  Satisfies "don't expose unpublished pages publicly" without signed URLs
  yet (deferred to `06-phase-5-polish.md`).

## Per-template Livewire components

### Mandatory root component (locked in after Phase 1 shipped)

Every template package must ship exactly one **root Livewire component** —
`{StudlyTemplateKey}.php` (e.g. `BasicPromo.php` for `basic-promo`,
`SeasonFreshMango.php` for `season-fresh-mango`) sitting alongside
`config.json`/`schema.json`/`content.json`/`template.blade.php` in the
template's own folder. This is enforced at discovery time:
`TemplateDiscoveryService` treats a missing root component file exactly
like a missing required JSON file — the template is skipped (logged,
never registered/updated in the DB) until the file exists.

**Why**: `template.blade.php` is no longer directly renderable on its own
— it only ever renders as the root component's own `render()` view. This
makes every landing page's outer shell a real, independently addressable
Livewire component (its own `wire:id`), architecturally consistent with
whatever per-block child components a template may itself embed via
`@livewire(...)` (see "Optional child components" below).

**Where it lives & how it's found**: same folder as the other 4 files, not
`app/Livewire/` — `storage/`/`resources/landingpage-templates/` are
outside Composer's PSR-4 autoload map, so `TemplateComponentRegistrar`
(run from `AppServiceProvider::boot()`) `require_once`s it directly and
registers it with `Livewire::component()` under a fixed alias,
`landingpage.{template-key}.root`. FQCN convention:
`App\Livewire\LandingPageEngine\{StudlyTemplateKey}\{StudlyTemplateKey}`
— under the same `App\Livewire\LandingPageEngine` namespace root as
`LandingPageShow`, the public entry point.

**Hard constraint: the root component's view must resolve to exactly one
root HTML element.** This is a standard Livewire requirement for *any*
component (not landing-page-specific), but it bit hard here: `basic-promo`'s
original `template.blade.php` was a flat sequence of sibling `<style>`,
`<section>`×N, `<footer>` tags with no wrapping element — valid as a
document fragment, but not as a single component's view. In
`APP_DEBUG=true`, Livewire's `SupportMultipleRootElementDetection` hook
parses the mounted HTML with `DOMDocument::loadHTML()` and looks for
`<body>`'s child elements to count roots; for HTML that never had a
`<body>` to begin with (a raw fragment, not a document), that lookup
returns `null`, and indexing into it throws `"Attempt to read property
childNodes on null"` — surfaced as a fatal 500 on any real HTTP request
through `admin.landingpages.pages.preview` (Laravel's error handler treats
a `Warning` as fatal there, unlike the more lenient `php artisan tinker`
CLI context, which is why this didn't show up during initial tinker-only
testing). **Fix**: wrap the entire body of `template.blade.php` — `<style>`
through the closing `<footer>` — in one `<div>...</div>`. Every future
template's root-component view must do the same; `ProductOrderForm.blade.php`
(a child component) already did this correctly (`<div class="product-block">`)
because that constraint was already known for child components — it just
hadn't been carried over to the root component's own view until this bug
surfaced.

**Mount contract**: `mount(int $landingPageId)` — only an int, not the
merged `$data`/`$errors`/`$selected_products` directly. Livewire hydrates/
dehydrates a component's public properties across requests, and a raw
`stdClass` (what `LandingPageRenderer::mergedContentData()` returns) has
no built-in Livewire (de)hydration support the way a plain int does. The
root component re-derives its own view data inside its own `render()` via
`LandingPageRenderer::viewData($landingPage)` — the same method that
already built that shape for direct `View::file()` rendering.

**How it's mounted for real requests**:
`LandingPageRenderer::renderView()` calls `Livewire::mount('landingpage.{key}.root',
['landingPageId' => $landingPage->id])`, which returns a raw HTML string
(not a `View` — Livewire's `mount()` already renders the component fully,
`wire:snapshot`/`wire:effects` attributes included). That string is echoed
into one of two thin wrapper views depending on `render_mode`:
- **`fragment`**: `resources/views/landingpage-engine/fragment-wrapper.blade.php`
  (`@extends` `layouts/landingpage/landingpage.blade.php`).
- **`standalone`**: `resources/views/landingpage-engine/standalone-wrapper.blade.php`
  (no layout — the root component's own view, `template.blade.php`,
  already renders a complete document).

Both wrappers exist only so `LandingPageShow::render()` (a full-page
Livewire component itself) can return a real `Illuminate\View\View` —
Livewire's full-page dispatch calls `->with()` on whatever `render()`
returns, which only `View` exposes (a raw `Response` throws `"Method
Illuminate\Http\Response::with does not exist"`, discovered while
implementing this).

**Trade-off accepted**: mounting the root component via `Livewire::mount()`
renders it in its own isolated Blade pass, separate from the layout's own
pass — so a template can no longer `@push('head')`/`@push('footer')` extra
markup into the layout's `@stack()` targets (that mechanism was tried
first and reverted once this constraint was found). `basic-promo`'s shared
webfont link now lives directly in `layouts/landingpage/landingpage.blade.php`
instead. A future per-template "extra head content" mechanism, if needed,
would have to be a property the root component exposes to the wrapper
view explicitly (e.g. returned alongside the mount HTML), not `@push`/`@stack`.

### Optional child components

A template may additionally ship any number of other `*.php` files as
child components — mounted from inside `template.blade.php` via
`@livewire('landingpage.{key}.{component-slug}', [...])`, one instance per
repeatable block. These use a *different* FQCN convention,
`App\LandingPageEngine\Templates\{StudlyTemplateKey}\{ClassName}`, kept
deliberately distinct from the root component's
`App\Livewire\LandingPageEngine\*` namespace so "the one mandatory root"
and "however many optional children" are never ambiguous at a glance.
`TemplateComponentRegistrar` still auto-discovers and registers any such
file if one exists — the mechanism is intact even though `basic-promo`
currently has none (see below).

**`basic-promo`'s product cards were reverted from a child component back
to plain HTML.** They originally used a `ProductOrderForm` child
component (one Livewire island per product, with a live variant selector
and a `wire:click`-driven Add to Cart button calling
`CartManager::addToCart()` directly). Per an explicit instruction to
"remove form, use direct form not component," that component was deleted
— each product card in `template.blade.php`'s `@foreach` loop is now a
plain server-rendered `<form>` (product/variant/quantity fields, a submit
button, `action="#"` placeholder) with **no submission wiring** — it does
not currently add anything to the cart. Reconnecting it to
`CartManager`/checkout (a real form `action` posting to a route, or
restoring the Livewire child component) is unfinished work, not yet
scoped to a phase.

### Known gap, not fixed in Phase 1

`season-fresh-mango`, `seldom-zaynah-eid`, and `seldom-zaynah-eid-v2` do
not yet have a root component (`SeasonFreshMango.php`/
`SeldomZaynahEid.php`/`SeldomZaynahEidV2.php`) — as of the mandatory-root
rule landing, **discovery now skips all three** (logged, not registered/
refreshed in `landing_page_templates`; any pre-existing DB rows for them
are stale and untouched until a template author adds the missing file).
Each also still has its original vanilla-JS order form referencing
undefined routes (`cart.landing.order`, `cart.order.purchase`) from before
this engine existed. Both gaps — the missing root component and the
broken order form — are real Phase 3/4 scope (a large refactor of each
template's ~2000-line client-side cart/discount/delivery logic), not
attempted in Phase 1. `basic-promo` (this session's own custom template)
remains the only fully working, discoverable, end-to-end template.
Logged in `07-open-questions-and-decisions.md`.

## Explicit non-goals for Phase 1

- No drag-drop visual builder (Phase 2).
- No ecommerce components beyond what the 4 existing templates already
  hardcode in their own blade files (Phase 3) — Phase 1 only lets admins
  edit each template's existing schema-defined text/image fields.
- No revisions/undo-redo (Phase 2) — autosave, if added, is client-side
  draft-state only in Phase 1.
- No combo/bundle logic (Phase 3).
- No coupon wiring (Phase 4 — and a pre-existing checkout gap regardless).

## Dependencies on earlier phases

None — this is the first phase.
