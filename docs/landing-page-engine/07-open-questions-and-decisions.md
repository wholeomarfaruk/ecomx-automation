# Open Questions & Decisions Log

Living document. Every architectural decision from the original spec's
Section 40 question list, answered with a one-line rationale where already
decided, or flagged as genuinely open where not. Update this file instead
of re-litigating a settled question in a later phase doc.

Legend: ✅ Decided · 🟡 Open (flagged, deferred on purpose) · ⛔ Gap (needs
work outside the landing-page engine before this phase can proceed)

## Existing architecture

| # | Question | Status | Answer |
|---|---|---|---|
| 1 | Existing Frontend Engine structure? | ✅ | `EngineManager` + `ThemeManager`, engine = `routes/frontend/*.php` + `theme.json` manifest. See `01-audit-findings.md` §1–3. |
| 2 | Existing Page model? | ✅ | None — pages are hard-coded config entries, not DB rows. |
| 3 | Existing Template system? | ✅ | Theme-level yes (`ThemeManager`); page-instance-level no (that's what this engine adds). |
| 4 | Existing Media Manager? | ✅ | Custom `File`/`FileItem` + `WithMediaPicker` trait, no Spatie MediaLibrary. |
| 5 | Existing routing for dynamic pages? | ✅ | No — all routes fixed/hard-coded, no catch-all. |
| 6 | How does frontend render dynamic content? | ✅ | File-backed JSON (`PageSectionConfigRegistry`) read by fixed Livewire section components. |
| 7 | How are settings stored? | ✅ | `App\Models\Setting` (DB, group+key+value) for site-wide; separate file-backed JSON for theme-level. |
| 8 | How are permissions implemented? | ✅ | `spatie/laravel-permission` + custom `Panel`/`PanelMiddleware`. |
| 9 | How are notifications handled? | 🟡 | Not audited in depth — admin uses `toast` dispatch + SweetAlert2; customer-facing notification channel (email/SMS on order) not yet confirmed. Revisit if a landing-page flow needs customer notifications beyond what `Checkout` already sends. |

## Ecommerce

| # | Question | Status | Answer |
|---|---|---|---|
| 10 | Product model? | ✅ | `app/Models/Product.php` — see `01-audit-findings.md` §7. |
| 11 | How are variants represented? | ✅ | Relational attribute-value chain, see §8. Reused as-is (`04-phase-3-ecommerce-components.md`). |
| 12 | How is stock handled? | ✅ | `ProductVariant.stock_quantity`, enforced in `CartManager`, committed via `StockService::commitOrder()`. |
| 13 | How does Cart work? | ✅ | DB-backed, device-keyed, event-driven (`CartManager`). |
| 14 | How does Checkout work? | ✅ | `App\Livewire\EcomxFashion\Checkout`, single-page form → `Order`+`OrderItem` in a transaction. |
| 15 | How does Order creation work? | ✅ | See §12 of audit — snapshot pricing at creation, `recalculateTotals()` single source of truth. |
| 16 | How does pricing work? | ✅ | `sale_price ?? price`, resolved per variant or product-level fallback. |
| 17 | How are discounts handled? | ⛔ | `Coupon`/`Promotion` models exist but **not wired into `Checkout`** — pre-existing gap, not landing-page work. See `05-phase-4-checkout-attribution.md`. |
| 18 | How are coupons handled? | ⛔ | Same as #17. |
| 19 | How is shipping calculated? | ✅ | Hard-coded flat 2-zone table in `Checkout.php`. Reused as-is. |
| 20 | How are payment methods selected? | ✅ | COD (no payment row) or manual-bKash (customer-typed reference, `OrderPayment` status `PENDING`). No gateway API. |

## Landing page

| # | Question | Status | Answer |
|---|---|---|---|
| 21 | Use normal site header? | ✅ | Configurable per page (`header_mode`: global/custom/none) — Phase 1. |
| 22 | Use normal footer? | ✅ | Same, `footer_mode`. |
| 23 | Can disable both? | ✅ | Yes, `none` mode for both. |
| 24 | Custom header? | ✅ | `custom` mode — Phase 1 schema supports it; actual custom-header *authoring* UI is Phase 2 (builder can define a header-region section). |
| 25 | Custom footer? | ✅ | Same as #24. |
| 26 | Product as hero? | ✅ | A `hero` component can reference a product via the CTA action system (Phase 3) or a future `product_hero` component type — not built as a special case, falls out of the general registry. |
| 27 | Multiple products? | ✅ | `product_grid`/manual multi-select — Phase 3. |
| 28 | Product variants? | ✅ | Phase 3, reuses existing variant chain. |
| 29 | Bundles? | 🟡 | Depends on confirming `Product::comboItems()`'s actual current implementation — flagged as a **hard dependency to verify** before Phase 3 combo work starts (see `04-phase-3-ecommerce-components.md`). |
| 30 | Checkout CTA? | ✅ | Links to existing `Checkout` route; never embedded/duplicated — see `05-phase-4-checkout-attribution.md`. |
| 31 | Direct Buy Now? | ✅ | Add via `CartManager`, redirect to checkout — Phase 3. |
| 32 | Add multiple products to cart? | ✅ | Sequential `add-to-cart` event dispatch — Phase 3. |
| 33 | Display stock? | ✅ | Reuses `ProductVariant.stock_quantity` — Phase 3 product-card setting. |
| 34 | Display variant availability? | ✅ | Reuses existing stock/status fields — Phase 3. |

## Builder

| # | Question | Status | Answer |
|---|---|---|---|
| 35 | How should sections be represented? | ✅ | JSON `sections` array (spec shape) for builder-created pages; legacy flat `content.json` shape preserved as-is for the 4 pre-existing templates. See `02-phase-1-core.md` Note. |
| 36 | How should components be registered? | ✅ | `ComponentRegistry`, array/config-driven, matches `theme.json`'s `sectionComponents.registry` FQCN-list precedent. |
| 37 | How should component schemas be defined? | ✅ | Reuses the `schema.json` field-type vocabulary from Phase 1 (extended with `media` type). |
| 38 | How should component settings be validated? | 🟡 | Phase 1/2 validate required text fields via Livewire `rules()`; a generic schema-driven validator (deriving Laravel rules from each field's `rules` string, already present in existing `schema.json` files) is the likely mechanism — not yet built, flagged for Phase 2 implementation detail. |
| 39 | How should responsive settings be represented? | ✅ | Per-field breakpoint override map with mobile→tablet→desktop fallback chain — `03-phase-2-builder.md`. |
| 40 | How should drag/drop state be stored? | ✅ | `sortablejs` (existing dependency) driving array-order in `content.sections`, persisted on drop via debounced autosave. |
| 41 | How should undo/redo work? | ✅ | Client-side in-memory snapshot stack for step-by-step undo; separate coarser `landing_page_revisions` table for "restore an earlier save" — explicitly not conflated, `03-phase-2-builder.md`. |
| 42 | How should autosave work? | ✅ | Debounced (2–3s inactivity) Livewire persist to `landing_pages.content` directly. |
| 43 | How should revisions work? | 🟡 | Table exists (Phase 2) but pruning policy (keep last N vs time-boxed) not decided — low-stakes, decide during Phase 2 implementation. |
| 44 | How should publishing work? | ✅ | `status` enum on `landing_pages`, `published_at` timestamp — Phase 1. |
| 45 | How should preview work? | ✅ | Auth-gated route in Phase 1; signed time-limited URLs added in Phase 5. |

## Templates

| # | Question | Status | Answer |
|---|---|---|---|
| 46 | What is a system template? | ✅ | Folder under `resources/landingpage-templates/`, `source=system`, not admin-editable (developer-maintained). |
| 47 | What is a user template? | ✅ | Folder under `storage/landingpage-templates/`, `source=custom`. |
| 48 | Can a page be created from a template? | ✅ | Yes — Phase 1's `PageForm` template picker, content copied from `content.json` on creation. |
| 49 | Can a page be converted back into a template? | 🟡 | Not built in Phase 1–2. Natural implementation: export the page's current `content` as a new `storage/landingpage-templates/{new-key}/content.json` + a generated `schema.json`/`config.json` — flagged as a nice-to-have, not scheduled to a specific phase yet. |
| 50 | Can templates be duplicated? | 🟡 | Same mechanism as #49 would cover it; not separately scheduled. |
| 51 | Should template updates affect existing pages? | ✅ | **No** — locked in early this session (see the STEP 0 "Important rule" in prior conversation): a page's `content` is copied once at creation and is fully independent afterward. Template file updates only affect *new* pages created from that template going forward. |

## Commerce UX

| # | Question | Status | Answer |
|---|---|---|---|
| 52–61 | Variant selection, price/image update, unavailable/out-of-stock handling, Add to Cart success/failure, Buy Now destination | ✅ | All answered in `04-phase-3-ecommerce-components.md` "Product variants" and "Add to Cart" sections — all reuse existing `ProductVariant`/`CartManager` behavior verbatim. |
| 62 | Combo stock? | 🟡 | Depends on #29 (verify `comboItems()` first). |
| 63 | Combo order representation? | ✅ | `CartItem.combo_id`/likely `OrderItem.combo_id` columns already exist (audit finding #10, #12) — combo cart/order representation is **already solved** at the schema level, pending #29's verification of the read side. |

## Marketing

| # | Question | Status | Answer |
|---|---|---|---|
| 64 | Landing-page attribution storage? | ✅ | Existing `marketing_sessions`/`marketing_attributions` `landing_path` columns — `05-phase-4-checkout-attribution.md`. |
| 65 | UTM preservation? | ✅ | Already handled by `AttributionService`/`MarketingContext` for any real route. |
| 66 | Order-to-landing-page linkage? | ✅ | Via `marketing_events.order_id` + `first_touch_landing_path`, no new FK needed for v1 (see 🟡 below for the one open sub-question). |
| 67 | Future analytics/campaign use? | ✅ | Same tables already support this; no landing-page-specific schema needed. |

## Additional open decisions not in the original Section-40 list

- ⛔ **`basic-promo`'s product cards reverted from a Livewire child
  component (`ProductOrderForm`) back to plain HTML**, per explicit
  instruction ("remove form, use direct form not component"). The
  `<form>` in `template.blade.php`'s product loop currently has **no
  submission wiring** (`action="#"`, no route, no cart integration) — this
  is a regression from the previously-working add-to-cart flow, not yet
  reconnected. `ProductOrderForm.php`/`product-order-form.blade.php` were
  deleted (dead code, template no longer references them). See
  `02-phase-1-core.md`'s "Optional child components" section. Whoever
  picks this up next needs to decide: a real form `action` posting to a
  new route/controller calling `CartManager::addToCart()` (matching the
  "never duplicate cart logic" rule), or restoring the child-component
  approach.
- ✅ **Custom template packages moved to `storage/app/public/landingpage-templates/`**
  (from bare `storage/landingpage-templates/`). Makes custom templates'
  own assets (`preview_image`, etc.) web-reachable via the `storage:link`
  symlink, matching every other public asset area (`uploads/`,
  `frontend/`). Updated in `LandingPageTemplate::basePath()`,
  `TemplateDiscoveryService::roots()`, `AppServiceProvider::boot()`'s
  `loadViewsFrom()`. Added `LandingPageTemplate::previewImageUrl()`, used
  by `template-list.blade.php` instead of a raw filesystem `file_exists()`
  check.
- ⛔ **`storage/app/public/.gitignore` ignores everything** (`*` with only
  `!.gitignore` excepted), so custom template packages placed under
  `storage/app/public/landingpage-templates/` are currently **not tracked
  by git** — they exist only on the local filesystem and will not survive
  a fresh clone/deploy. Explicitly not fixed yet (user's call) — revisit
  before relying on a custom template surviving a deploy.
- 🟡 **Route prefix**: `/lp/{slug}` (Phase 1 default) vs bare `/{slug}`
  catch-all. Bare-slug would need collision-checking against every fixed
  route (`shop`, `category/{slug}`, `product/{slug}`, `checkout`,
  `reviews`, `track`) at both route-registration time and page-save time.
  Deferred — revisit once real usage shows whether marketing wants clean
  URLs badly enough to justify the collision-safety work.
- 🟡 **`landing_page_id` FK on `marketing_events`**: only add if path-string
  attribution matching proves fragile in practice (e.g. slug renames
  breaking historical reports). Not built preemptively.
- 🟡 **Product tags / Collection model**: both referenced by the original
  spec's product-selection wishlist but don't exist in the core catalog
  today. Out of landing-page-engine scope; flagged as catalog-team
  prerequisites if those selection modes are actually needed.
- ⛔ **`season-fresh-mango`/`seldom-zaynah-eid`/`seldom-zaynah-eid-v2` order
  forms are broken today** (reference undefined routes `cart.landing.order`/
  `cart.order.purchase`, inherited from before this engine existed).
  Visiting these 3 templates' rendered pages currently 500s. Fix requires
  converting each template's large vanilla-JS cart/order UI into the
  per-template Livewire `OrderForm.php` pattern established by
  `basic-promo` — real Phase 3/4 work (see `02-phase-1-core.md` "Per-template
  Livewire components"), not attempted in Phase 1. Until then, only
  `basic-promo` is a fully working end-to-end template.
- ✅ **Public rendering converged onto one Livewire component, branching on
  a per-template `render_mode`**: `App\Livewire\LandingPageEngine\LandingPageShow`
  is the single route target for `/lp/{slug}`. Superseded an earlier
  plain-controller-only implementation once a second template
  (`basic-promo`) needed to render as a layout-wrapped fragment while the
  original 3 templates still needed to render as complete standalone
  documents — see `02-phase-1-core.md` "Public route + rendering" for the
  full mechanism (why `#[Layout]` couldn't be used, the
  `LandingPageRenderer::renderView()` branch shared with admin preview,
  the `fragment-wrapper` view, why both branches must return a real `View`
  rather than a raw `Response`). `header_mode`/`footer_mode` on
  `landing_pages` still default to `none` and aren't
  wired to any storefront's actual header/footer partials yet — doing so
  naively would reintroduce a dependency on the ecomx-fashion engine that
  this session deliberately removed everywhere else (see `routes/landingpage.php`'s
  separation work). If needed later, resolve generically via
  `EngineManager` rather than hard-coding `ecomx-fashion.partials.*`.
- ✅ **Device-fingerprint bootstrap script extracted to a shared component**:
  `<x-device-fingerprint-script />` (`resources/views/components/device-fingerprint-script.blade.php`),
  used by both the real storefront layout
  (`resources/views/ecomx-fashion/layouts/ecomx_fashion.blade.php`) and the
  new landing-page layout — one source of truth instead of duplicating the
  cookie/localStorage self-healing + DeviceTracker client-signal logic.
- ✅ **Every template must have a mandatory root Livewire component**:
  `{StudlyTemplateKey}.php` alongside the other 4 package files,
  `template.blade.php` is no longer independently renderable — it only
  ever renders as that component's view. Enforced by
  `TemplateDiscoveryService` at the same level as the other 4 required
  files. `LandingPageRenderer::renderView()` mounts it via
  `Livewire::mount()` (passing only `landingPageId`, an int, since
  Livewire's component-property (de)hydration doesn't reliably support a
  raw `stdClass`) and echoes the resulting HTML into one of two thin
  wrapper views (`fragment-wrapper`/`standalone-wrapper`) so
  `LandingPageShow::render()` can still return a real `View`. See
  `02-phase-1-core.md` "Per-template Livewire components" for the full
  mechanism and its trade-off (per-template `@push('head')`/`@push('footer')`
  no longer works, since the root component mounts in its own isolated
  Blade rendering pass — reverted in favor of a shared webfont link
  directly in the layout).
  **Bug found and fixed via real HTTP testing (not caught by tinker)**: a
  root component's view must resolve to exactly one root HTML element,
  same as any Livewire component — `basic-promo`'s `template.blade.php`
  was a flat sequence of sibling tags (`<style>`, `<section>`×N,
  `<footer>`) with no wrapper, which crashed
  `admin.landingpages.pages.preview` with `"Attempt to read property
  childNodes on null"` (Livewire's `SupportMultipleRootElementDetection`,
  `APP_DEBUG=true` only — `DOMDocument::loadHTML()` can't find `<body>` in
  a body-less fragment). Fixed by wrapping the whole template in one
  `<div>`. This didn't surface during `php artisan tinker` testing because
  PHP `Warning`s aren't fatal there the way Laravel's real error handler
  treats them over HTTP — a reminder that tinker-only verification isn't
  equivalent to a real request. See `02-phase-1-core.md`'s "Mandatory root
  component" section for the full explanation.
  **Consequence**: `season-fresh-mango`/`seldom-zaynah-eid`/
  `seldom-zaynah-eid-v2` — none of which have a root component yet — are
  now excluded from discovery entirely (not just "renders with a known
  bug" as before). Adding their root components is bundled into the same
  Phase 3/4 work item as fixing their broken order forms, since both
  require touching the same ~2000-line per-template blade/JS.
- 🟡 **True draft/live content split** (edit a published page without the
  live version changing until an explicit "publish" action): Phase 1/2
  only have a single `content` column + `status` flag, meaning editing a
  *published* page's content changes it immediately. If this becomes a
  real requirement, the fix is either a second `draft_content` column or
  promoting the revisions table to be the source for "live" — a decision
  to make explicitly when it's actually needed, not implicitly now.
