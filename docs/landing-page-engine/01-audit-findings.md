# Repository Audit — Findings (Permanent Reference)

This is the full repository audit performed before designing the Landing
Page Engine. It is preserved verbatim (lightly reformatted) as a permanent
reference so future sessions don't need to re-audit the codebase. Every
claim below is grounded in an actual file path found in this repository at
audit time.

---

## Frontend Engine (existing)

### 1. Page/CMS model for dynamic frontend pages

**No such model exists.** `app/Models/` has no `Page`, `CmsPage`, `Section`,
or `ThemeSection` Eloquent model. "Pages" in this codebase are a
**declarative, code-level concept**, not DB rows:

- `App\Support\EcomxFashion\PageRegistry` reads page metadata
  (label/icon/route/sections) from `config('ecomx-fashion.pages')` —
  `config/ecomx-fashion.php:34-90` (`home`, `shop`, `category`, etc., each a
  fixed array with a `sections` list).
- There is no generic "create a new page" capability — pages are hard-coded
  entries in `config/ecomx-fashion.php` + a real Livewire component + a real
  route. Adding a page today requires code changes.

### 2. Routing for dynamic frontend pages

**No catch-all/slug route exists. All frontend routes are fixed, per-feature
routes**, loaded through an "Engine" indirection:

- `routes/web.php:1-9` calls `\App\FrontendEngine\EngineManager::loadActiveThemeRoute()`
  before anything else, which requires the currently active engine's route
  file.
- `App\FrontendEngine\EngineManager::engines()`
  (`app/FrontendEngine/EngineManager.php:44-54`) discovers "engines" by
  globbing `routes/frontend/*.php` — currently only
  `routes/frontend/ecomxFashion.php` exists.
- `routes/frontend/ecomxFashion.php` registers fixed routes: `/`, `/shop`,
  `/category/{slug?}`, `/product/{slug?}`, `/reviews`, `/track`, `/checkout`
  — each bound 1:1 to a specific Livewire component. Route names carry a
  stable `ecomx-fashion.` prefix.
- There is no `Route::get('/{slug}', ...)` catch-all anywhere. A Landing
  Page Engine needing dynamic slugs needs a **new route registration**,
  most naturally added inside `routes/frontend/ecomxFashion.php` (the
  currently active engine).
- Legacy site is kept reachable at `/legacy` (`routes/web.php:14-19`) but is
  superseded and not linked anywhere active.

### 3. ThemeEngine / SectionConfig system

**Yes — a real, mature system already exists**, essentially a mini
page-builder for *fixed* pages (configuring sections within existing pages,
not creating new ones):

- **Theme manifest** (`resources/ecomx-fashion/theme.json`): declares
  `manifest`, `pages` (route/view/livewire per page key), `layouts`,
  `sections`, `sectionComponents.registry` (FQCN list of section Livewire
  components), `dependencies`, `themeSettings`.
- **`App\FrontendEngine\EngineManager::validate()`**
  (`app/FrontendEngine/EngineManager.php:130-160`) validates a theme's
  manifest across 5 layers (Manifest → Dependencies → Files → Classes →
  Routes) before `ThemeManager::activate()` allows it to become active. This
  acceptance-gate pattern is worth reusing for a landing-page "template"
  concept.
- **`App\FrontendEngine\ThemeManager`** gates `ThemeRegistry::setActive()`
  behind validation; active theme is persisted as flat JSON at
  `storage/app/theme.json` (not DB).
- **Section config data storage** —
  `App\Support\EcomxFashion\PageSectionConfigRegistry`: file-backed (no DB)
  JSON store, one file per `{page}.{section}` at
  `storage/app/public/frontend/{theme}/{page}.{section}.json`, written via
  `flock()` + atomic temp-file-rename. This is the closest existing analog
  to "landing page content JSON."
- **Section on/off + ordering** —
  `App\Support\EcomxFashion\PageSectionRegistry`: separate file-backed JSON
  at `resources/{theme}/config/page-sections.json`, tracks `active`/`order`
  per section per page (drag-reorder + toggle).
- **Field schema** — `App\Support\EcomxFashion\SectionSchema::fieldsFor($section)`
  (`app/Support/EcomxFashion/SectionSchema.php:40-82`): a hard-coded
  `match()` returning field definitions per section key, field types:
  `text`, `media_list`, `text_list`, `category_list`, `category_select`,
  `category_multi_select`, `faq_list`, `stat_list`. **Not** a
  generic/portable JSON-schema format — it's PHP code, one section at a
  time, no validation rules beyond type.
- **Admin editor** — `App\Livewire\Admin\ThemeEngine\SectionConfigPage`,
  routed at `/admin/frontend/{page}/{section}/edit`, reads
  `SectionSchema::fieldsFor()` to render a dynamic form, reads/writes via
  `PageSectionConfigRegistry`.
- **Storefront rendering** — `App\Livewire\EcomxFashion\Home::activeSections()`
  filters `PageSectionRegistry::activeKeysForPage('home')` down to keys
  with a resolvable Livewire tag, then the blade view loops those tags.
- **Ten section components exist today**: Hero, Marquee, Categories,
  FlashSale, PromoStrip, Trending, ShopByStyle, Reviews, Instagram, WhyFaq
  (`app/Livewire/EcomxFashion/Sections/*.php`), each independently reading
  its own config via `PageSectionConfigRegistry`.

**Implication**: this section-config system is page-scoped and fixed to
hard-coded page keys declared in `config/ecomx-fashion.php`. It is not
generic enough to spin up arbitrary new landing pages without code changes.
The Landing Page Engine needs its own registry (DB-backed page/slug model)
rather than extending this file-based, fixed-page-key system directly — but
should reuse its *conventions* (flock+atomic-write JSON pattern, field-type
vocabulary, media-picker integration, section-component-registry pattern).

### 4. Media Manager

**No spatie/laravel-medialibrary, no `HasMedia` trait anywhere.** Custom
two-table system instead:

- `App\Models\File` (`app/Models/File.php`): `id, name, caption, type,
  extension`, SoftDeletes, `hasMany(FileItem)`.
- `App\Models\FileItem`: `file_id, type, size, path`, SoftDeletes — holds
  per-variant (original/thumbnail/etc.) physical file paths.
- A global `file_path($id)` helper resolves a File id to a public URL (used
  e.g. in `Product::getFeaturedImageAttribute()`).
- Admin browsing at `App\Livewire\Admin\File\Uploads` (`/admin/uploads`),
  uploads via `App\Http\Controllers\Admin\FileUploadController`
  (FilePond-based).
- `App\Livewire\Traits\WithMediaPicker` is a reusable trait (used by
  `SectionConfigPage`) providing an `openMediaPicker`/`mediaSelected`
  picker-modal protocol — **this is the trait the Landing Page Engine
  builder should reuse for image fields.**

### 5. Site-wide settings storage

DB-backed key-value model, not config files: `App\Models\Setting` — columns
`group, key, value, type, label`; `Setting::get($key, $default,
$group='general')` / `Setting::set()` cache-wrap via
`Cache::rememberForever("setting:{group}:{key}")`. Admin UI:
`App\Livewire\Admin\SiteSettings\SiteSettings` at `/admin/site-settings`.

Note: theme-level settings (active theme, palette, page-section config) are
**separate** and file-backed — not in the `Setting` DB table. Two distinct
settings systems coexist; the Landing Page Engine's own Settings page should
use the `Setting` model (group `landingpage`), matching the site-wide
pattern, not invent a third mechanism.

### 6. Permissions/roles

`spatie/laravel-permission` (`^7.1`), config at `config/permission.php`.
`App\Models\User` uses `HasRoles`. Admin UI:
`App\Livewire\Admin\Permissions\RoleList`/`RoleCreate`. A custom
`App\Models\Panel` + `PanelMiddleware` (aliased `panel`) gates `/admin`
access via `panel:admin` middleware.

---

## Ecommerce core

### 7. Product model

`app/Models/Product.php` — SoftDeletes. Fillable includes `code, name,
slug, short_description, description, brand_id, status, featured,
stock_status, product_type, combo_allowed, gift_allowed, price, sale_price,
purchase_price, combo_price, featured_image_id, image_ids, video_ids,
weight, length, width, height, meta_image_id, meta_title, meta_description,
meta_keywords, sort_order`. Casts: `product_type` → `ProductType` enum;
`image_ids`/`video_ids` → array. Relations: `brand()`, `categories()`
(BelongsToMany via `product_category_pivot`), `featuredImage()`/`metaImage()`
(BelongsTo File), `productAttributes()`, `attributeOrder()`, `variants()`
(HasMany, ordered by `sort_order`), `comboItems()`, `gifts()`,
`wishlistItems()`, `reviews()`, `reviewStatistic()`. `scopeActive()` filters
`status='active'`.

### 8. Product variants

Real relational, attribute-value driven (not JSON blobs):

- `App\Models\ProductVariant`: `product_id, sku, combination_key, price,
  sale_price, purchase_price, combo_price, stock_quantity, reorder_level,
  reorder_quantity, status, sort_order`. Relations: `product()`, `values()`
  (HasMany `ProductVariantValue`), `media()` (HasMany
  `ProductVariantMedia`), `links()`. Computed accessors `optionsMap`
  (builds `['Color'=>'White','Size'=>'M']`) and `displayImage`.
- Chain: `Attribute` (name/slug/type/status) → `AttributeValue`
  (attribute_id/value/slug/swatch_type/swatch_value/sort_order) → per-product
  join `ProductAttribute` → `ProductAttributeValue` (per-product swatch
  override) → per-variant join `ProductVariantValue`.
- Admin CRUD: `App\Livewire\Admin\Catalog\Attributes.php`,
  `App\Livewire\Admin\Catalog\ProductVariants.php` (SKU auto-generated,
  variant combination generation from selected attribute values).

### 9. Category / Collection

**Only `Category` exists — no separate `Collection` model.**
`app/Models/Category.php`: self-referential `parent()`/`children()`,
`products()` BelongsToMany, `featuredImage()`/`coverImage()`/`metaImage()`.

### 10. Cart architecture

**DB-based, not session-based.**

- `App\Models\Cart`: `customer_id, device_id, status, subtotal`;
  `scopeActive()`.
- `App\Models\CartItem`: `cart_id, product_id, variant_id, combo_id,
  is_gift, quantity, price` — snapshotted `price` at add-time.
- `App\Livewire\EcomxFashion\CartManager` is the whole cart engine.
  `getCart()` does `Cart::firstOrCreate(['customer_id'=>null,'device_id'=>$device->id])`
  — carts are keyed off the **Device** (from `DeviceTracker` middleware),
  not PHP session. `addToCart()` resolves price as `variant->sale_price ??
  variant->price` (or product-level), auto-selects first in-stock variant
  if none passed, dedupes identical `product_id+variant_id` lines,
  enforces `stock_quantity` limits. Every cart mutation is a Livewire event
  listener (`#[On('add-to-cart')]` etc.) — **this is the pattern the
  Landing Page Engine's "Buy Now"/"Add to Cart" CTA should dispatch into.**
- Marketing: `addToCart()` fires `App\Marketing\Events\AddToCart` through
  `MarketingEventService::recordForCurrentRequest()`.

### 11. Checkout flow

`App\Livewire\EcomxFashion\Checkout`:

- Fields: `name, phone, address, note, delivery_area (dhaka|outside),
  payment_method (cod|bkash), transaction_id`.
- `mount()` fires `InitiateCheckout` marketing event.
- `placeOrder()`: validates, computes `deliveryCharge` from a hard-coded
  `$deliveryAreas` array, wraps in `DB::transaction`: resolves/creates
  Customer, creates a `DeliveryAddress`, creates `Order` + `OrderItem`s from
  cart contents, calls `$order->recalculateTotals()`, optionally creates an
  `OrderPayment` row if `payment_method==='bkash'` (manual reference
  capture, status `PENDING`), optionally commits stock via
  `StockService::commitOrder()`, marks the source `Cart`
  `status='converted'`.
- Fires `Purchase` marketing event after commit.
- **No coupon application logic present** despite `Coupon`/`Promotion`
  models existing.

### 12. Order / OrderItem

- `App\Models\Order`: `customer_id, source, status, payment_status,
  fulfillment_status, currency, subtotal, discount_amount, shipping_amount,
  shipping_discount, tax_amount, total_amount, paid_amount, due_amount,
  customer_note, admin_note, billing_address_id, shipping_address_id,
  coupon_id, coupon_code, placed_at, confirmed_at, completed_at,
  cancelled_at, courier_provider, courier_tracking_number, courier_charge,
  courier_status, courier_meta (json), courier_status_updated_at`.
  `recalculateTotals()` is the single source of truth for totals math.
- `App\Models\OrderItem`: `order_id, product_id, variant_id, combo_id,
  is_gift, product_name, variant_name, sku, quantity, unit_price,
  purchase_price, discount_amount, tax_amount, total_amount,
  returned_quantity` — **pricing/variant snapshot at order-creation time**,
  so later product/variant edits never retroactively change historical
  orders.

### 13. Pricing/discounts/coupons

A real Promotion/Coupon system exists at the model layer but is **not wired
into the live Checkout flow**:

- `App\Models\Coupon`: `promotion_id, code, usage_limit,
  usage_limit_per_customer, min_order_amount, max_discount_amount`;
  relations `promotion()`, `usages()`, `customers()`.
- `Promotion`, `PromotionCondition`, `PromotionDiscountRule`,
  `PromotionItem` also exist.
- `Order` has `coupon_id`/`coupon_code`/`discount_amount` columns ready to
  receive a coupon.
- Admin UI exists (`admin.sales.coupons*` routes).
- **Gap**: `Checkout::placeOrder()` never reads/validates/applies a coupon.
  A Landing Page Engine wanting "promo code" support can reuse the
  `Coupon`/`Promotion` models, but the apply-logic doesn't exist yet on the
  storefront checkout and needs to be added regardless of landing-page
  work (see `05-phase-4-checkout-attribution.md`).

### 14. Shipping

**Hard-coded flat-rate array, not a real shipping/zone system.**
`Checkout.php`:

```php
public array $deliveryAreas = [
    ['id' => 'dhaka', 'name' => 'Inside Dhaka', 'charge' => 70],
    ['id' => 'outside', 'name' => 'Outside Dhaka', 'charge' => 130],
];
```

`Order` also has `courier_provider/courier_tracking_number/courier_charge/
courier_status/courier_meta` columns suggesting a post-order courier
integration exists elsewhere (fulfillment side), but pre-order shipping
*rate calculation* is just this 2-option flat table.

### 15. Payment methods

**No real payment gateway integration** — manual/COD-style only:

- COD: no payment row created (payment collected on delivery).
- "bKash": customer types a `transaction_id` referencing a hard-coded
  personal bKash number shown in the UI; no API call. `OrderPayment` row
  created with `status: PENDING` for staff to manually verify.

---

## Marketing / attribution

**A full, production-grade attribution system already exists — the Landing
Page Engine must plug into this, not build a parallel one.** Full schema
detail lives in [`../marketing-tracking-database.md`](../marketing-tracking-database.md);
summary relevant to landing pages:

- `App\Marketing\Attribution\AttributionTouch` (readonly DTO): `source,
  medium, campaign, term, content, fbclid, gclid, ttclid, referrer,
  landingUrl, capturedAt`.
- `marketing_attributions` table already has `landing_path` columns:
  migration `2026_08_23_190001_add_landing_path_to_marketing_attributions_table.php`
  adds `first_touch_landing_path` and `last_touch_landing_path`. Populated
  via `MarketingEventService::pathFromUrl()`
  (`app/Marketing/Services/MarketingEventService.php:231-238`, using
  `parse_url($url, PHP_URL_PATH)`).
- `marketing_sessions` also independently carries `landing_url`/
  `landing_path` per session
  (`App\Marketing\Services\MarketingSessionResolver::resolve()`).
- **How it's populated**: `MarketingEventService::recordForCurrentRequest()`
  is the entry point Livewire actions call — builds `MarketingContext`,
  resolves attribution, resolves/reuses the active `MarketingSession`
  (30-min timeout, keyed by `device_id`), persists a `MarketingEvent` row +
  items + a `MarketingAttribution` row in one transaction, queues async
  delivery to destinations (Meta CAPI etc.). Called from
  `CartManager::recordAddToCart()`, `Checkout::mount()` (InitiateCheckout),
  `Checkout::recordPurchase()`. A global `MarketingTracker` middleware
  (appended to the `web` group in `bootstrap/app.php:43`) fires `PageView`
  on every request.

**Conclusion: a new Landing Page Engine must emit its pages through the
existing `MarketingContext`/`MarketingTracker`/`AttributionService`
pipeline (so `landing_path` naturally captures the landing-page URL) rather
than building any parallel tracking, cookie, or attribution table.** The
only thing that might be genuinely new later is a landing-page-specific
identifier (e.g. `landing_page_id`) if path-string matching isn't precise
enough — even that should be an additional nullable column on
`marketing_events`/`marketing_attributions`, not a new system (see
`05-phase-4-checkout-attribution.md`).

---

## Admin architecture patterns

### 17. Admin Livewire CRUD conventions

- **List/detail/form split**: separate top-level Livewire components per
  concern. Purchase module: `PurchaseOrders` (list) vs `PurchaseOrderForm`
  (create+edit combined via `mount(?int $id = null)`, both routes point at
  the same class). Catalog: `Products` (list) vs `ProductEdit`. Sales:
  `Orders` (list) vs `OrderDetail` (read-focused) vs `OrderCreate` (separate
  create-only component — convention isn't perfectly uniform across
  modules).
- **Validation**: standard Livewire `rules()` + `$this->validate()`,
  including conditional-unique rules, e.g.
  `'orderNumber' => 'required|string|max:190|unique:purchase_orders,order_number,' . ($this->editingId ?? 'NULL')`
  (self-excluding unique check for edit mode).
- **Routing/naming**: `routes/admin.php` is `require`d under
  `Route::prefix('admin')->name('admin.')` from `bootstrap/app.php:24-27`,
  further grouped by feature prefix, e.g. `Route::prefix('catalog')->name('catalog.')`
  → `admin.catalog.products`. Consistent `admin.{module}.{resource}[.{action}]`
  naming.
- **Layout**: every admin Livewire component's `render()` ends
  `->layout('layouts.admin.admin')`. Master layout at
  `resources/views/layouts/admin/admin.blade.php` wires a global
  `Livewire.on('toast', ...)` → SweetAlert2 handler — **standard feedback
  mechanism is `$this->dispatch('toast', ['type'=>'success|error',
  'message'=>'...'])`**.
- **Activity logging**: `spatie/laravel-activitylog` used ad hoc (not
  automatic per-model), e.g. `activity('purchase')->causedBy(auth()->user())->performedOn($order)->event(...)->log(...)`.
- **Computed properties**: `#[Computed]` used for request-cached derived
  data to avoid recomputation on every Livewire re-render.
- **N+1 avoidance discipline**: explicit batched-query helper methods with
  comments explaining why per-row lookups would be an N+1.

### 18. Soft-delete convention

Present on: `Product`, `ProductVariant`, `Category`, `Attribute`,
`AttributeValue`, `File`, `FileItem`, `User`. Absent on: `Cart`,
`CartItem`, `Order`, `OrderItem`, pivot/join models, `Setting`, `Coupon`.
**Convention: catalog/reference/admin-manageable data → soft-deleted;
transactional/pivot/line-item data → hard-deleted.** A Landing Page Engine's
core "page" model should follow the catalog-data precedent (SoftDeletes).

### 19. Slug generation/validation pattern

No shared slug service/trait — each admin component inlines
`Str::slug()` reactively as the name field changes (`updatedXxxName($value)`
hook sets `$this->newSlug = Str::slug($value)` live, editable afterward). No
dedicated `HasSlug` trait exists. The Landing Page Engine should follow this
same inline convention, with its own `unique:landing_pages,slug,{id}`
self-exclusion rule (matching the `unique:purchase_orders,order_number,...`
pattern).

### 20. Tailwind/UI conventions in admin

- `resources/views/layouts/admin/admin.blade.php` — Tailwind + Alpine
  (`Alpine.store('sidebar', ...)` persisted to `localStorage`), SweetAlert2
  toasts.
- Shared blade components under `resources/views/components/`:
  `action-message`, `action-section`, `button`/`secondary-button`/
  `danger-button`, `checkbox`, `confirmation-modal`, `dialog-modal`,
  `modal`, `dropdown`/`dropdown-link`, `form-section`, `input`/
  `input-error`/`label`, `searchable-select`, `document-picker-field`,
  `media-picker-field` (pairs with `WithMediaPicker`), `empty-state`,
  `skeleton`, `stats-card`, `badge`, `banner`, `marketing/*` (GTM
  injection). **These are the primitives the Landing Page Engine admin
  should reuse, not reinvent.**

---

## Landing page templates (filesystem-only at audit time, now wired in Phase 1)

At audit time: `resources/landingpage-templates/{season-fresh-mango,
seldom-zaynah-eid, seldom-zaynah-eid-v2}/` and
`storage/landingpage-templates/basic-promo/`, each a 4-file package
(`config.json`, `schema.json`, `content.json`, `template.blade.php`),
confirmed via `grep -rl "landingpage-templates" --include="*.php" app/
routes/ database/` returning zero results — nothing in application code
referenced them. Phase 1 (`02-phase-1-core.md`) wires these into a DB
registry and a public renderer.

---

## Composer / package.json

**composer.json** (`require`): `laravel/framework ^12.0`, `livewire/livewire
^3.6.4`, `laravel/fortify ^1.30`, `laravel/jetstream ^5.4`, `laravel/sanctum
^4.0` (this is the Jetstream/Fortify starter kit —
`"name": "laravel/livewire-starter-kit"`), `spatie/laravel-permission
^7.1`, `spatie/laravel-activitylog ^4.12`. **No spatie/laravel-medialibrary**
(custom File/FileItem instead). **No page-builder/CMS package.**
`kreait/laravel-firebase ^7.2`, `matomo/device-detector` (backs
Device/DeviceTracker), `composer/semver` (used by `EngineManager` for theme
dependency validation).

**package.json**: `alpinejs ^3.15.8`, `tailwindcss ^4.2.0` +
`@tailwindcss/vite`, `sweetalert2 ^11.26.18`, `swiper ^12.1.1`,
`@splidejs/splide ^4.1.4` (carousels), `filepond` + plugins (media upload
UI), `sortablejs ^1.15.7` (drag-reorder — backs `PageSectionRegistry`
reorder UI), `chart.js ^4.5.1`, `@fancyapps/ui ^6.1.10` (lightbox),
`notyf ^3.10.0` (alternate toast lib), `flatpickr`, `jquery ^4.0.0`.
**No drag-drop page-builder JS package** (no gridstack/craft.js/
interact.js/vue-grid-layout) — confirms a visual drag-drop builder does not
exist today (Phase 2 work, `03-phase-2-builder.md`).

---

## Key implications carried into every phase doc

1. No dynamic Page/CMS model and no catch-all route exist — `LandingPage`
   model + new route are net-new, but should follow `EngineManager`'s
   engine/route-file convention.
2. The ThemeEngine/SectionConfig system is the closest analog for
   "configurable content blocks" but is hard-wired to fixed page keys — its
   *patterns* (not its storage mechanism) are worth reusing.
3. The 4-file template packages are the right shape to build on; Phase 1
   adds a DB registry + renderer around them without changing their format.
4. Attribution is fully solved — landing pages just need to be real routes
   under the standard middleware stack.
5. Cart/Checkout/Order/Product/Variant/Category are mature and reusable —
   never reimplemented.
6. Coupon/Promotion exist but aren't wired into checkout — a pre-existing
   gap, not landing-page-specific work.
7. Shipping and payment are intentionally simple/manual today — don't
   design against a richer system that doesn't exist.
