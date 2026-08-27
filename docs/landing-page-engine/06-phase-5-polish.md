# Phase 5 — Polish (SEO, Preview, Performance, Security, Accessibility)

**Status: planned, not implemented.**

## Goal

Everything needed to make landing pages production-safe and genuinely
fast/marketing-grade, once the functional builder + ecommerce integration
(Phases 2–4) exist. This phase is intentionally last — optimizing before
the shape of the content is stable would be wasted/rework-prone effort, per
the spec's own "do not over-engineer caching before measuring" rule.

## SEO

Already partially covered by Phase 1's `landing_pages.seo` JSON column
(`meta_title`, `meta_description`, `meta_image`). This phase adds:

- `canonical` URL field (defaults to the page's own `/lp/{slug}` URL,
  overridable).
- `og_title`/`og_description`/`og_image` (fall back to `meta_title`/
  `meta_description`/`meta_image` if unset — not required fields).
- `robots` (default `index,follow`; a "noindex" toggle for ad-only landing
  pages that shouldn't rank organically — common real-world need for
  campaign pages).
- Structured data (`schema`): JSON-LD, scoped initially to `Product`/
  `Offer` schema when the page's primary content includes a Phase 3
  product/combo component — reuses the product's already-existing
  price/name/image fields, not new data entry.

Kept modular (a small `App\LandingPageEngine\SeoResolver` building a single
`<head>` partial) specifically so other future page types (if the
fixed-page ThemeEngine system ever wants the same SEO fields) could reuse
it — but that reuse is not built now, just kept possible per the spec's
"keep SEO modular so it can later be reused by other page types" note.

## Preview / signed URLs

Phase 1 ships auth-only preview (`admin.landingpages.pages.preview`, gated
by `panel:admin`). This phase adds **signed, time-limited preview URLs**
(Laravel's built-in `URL::temporarySignedRoute()`) for sharing a draft with
someone outside the admin panel (e.g. a client approving copy before
launch) — without granting them admin access. Preview always renders using
live product/variant/pricing data (never mock data), per the spec's
explicit requirement.

## Performance

- Lazy-loading images/video below the fold (`loading="lazy"` at the
  component-renderer level, not something authors configure per-field).
- Responsive images: reuse whatever the existing `File`/`FileItem` media
  pipeline already produces (audit found `FileItem.type` supports
  original/thumbnail variants) rather than building a new image-processing
  pipeline. If the existing pipeline doesn't produce enough size variants
  for landing-page hero/banner use, that's a media-system enhancement
  ticket, not landing-page-engine scope creep.
- Minimal JS per component: Phase 2's component renderers should degrade to
  server-rendered HTML + small Alpine snippets (matching the rest of the
  app's Livewire+Alpine pattern), not a client-side framework bundle.
- Full-page HTML caching: only for **published** pages with no
  personalization (no logged-in-customer-specific content) — cache key
  includes `slug` + `updated_at` so a page edit auto-invalidates. Not
  applied to preview/draft rendering. This is deferred until real traffic
  patterns justify it — per spec Section 34's explicit "do not
  over-engineer caching before measuring" instruction.
- N+1 avoidance: product/category components must eager-load
  (`with('variants', 'featuredImage', ...)`) exactly like existing
  `Shop`/`Category` Livewire components already do — audited pattern, not
  a new one.

## Security

- **Custom HTML/JS component** (from Phase 2's registry) is gated by a
  dedicated permission (via the existing `spatie/laravel-permission`
  system, audit finding #6) — e.g. `landingpage.custom-html`, not just "any
  admin." Default: no role has it; must be explicitly granted.
- All other component types render through Blade (auto-escaped by
  default) — no `{!! !!}` raw output except where a schema field is
  explicitly typed `html` (matching the existing pattern already used for
  `hero.subtitle` HTML-allowed fields in the current templates, e.g.
  `season-fresh-mango`'s `<br>`-allowed subtitle) and even then, run
  through a sanitizer allow-list (not full raw HTML) unless the
  `custom_html` component specifically was used.
- Slug validation: alphanumeric + hyphen only (matches `Str::slug()`
  output), rejecting anything that could collide with existing fixed
  routes (`shop`, `category`, `product`, `checkout`, `reviews`, `track`) —
  checked at save time in `PageForm`, not just relying on the `/lp/`
  prefix to avoid collisions forever (in case the bare-slug routing option
  from `07-open-questions-and-decisions.md` is chosen later).
- CSRF: automatic via Livewire (no action needed).
- Mass assignment: `LandingPage`/`LandingPageTemplate` use explicit
  `$fillable`, not `$guarded = []` — deliberately stricter than
  `MarketingSession`'s `$guarded = []` (audit finding), since landing pages
  are admin-authored content, not an internal tracking ledger.
- File validation: image fields reuse the existing
  `FileUploadController`/FilePond validation as-is (no new upload path).

## Accessibility

- Every component renderer required to emit semantic HTML (`<button>` not
  `<div onclick>`, `<nav>` for menus, proper heading hierarchy per section
  — not every section forced to `<h2>` regardless of context).
- Image/media components require `alt` text field in their schema (can be
  empty for decorative images, but the field must exist so authors are
  prompted, not silently omitted).
- Form components (checkout-adjacent, e.g. embedded lead-capture in later
  phases) require associated `<label>` elements — reusing the existing
  `<x-label>`/`<x-input>` component pair's accessibility behavior rather
  than hand-rolling inputs.
- Focus states: reuse existing Tailwind focus-ring utility classes already
  used across the admin/storefront rather than introducing new ones.

## Mobile-first frontend

- Sticky "Buy Now"/"Add to Cart" bar on mobile viewports for product-
  bearing landing pages (a small, opt-in section setting — not forced on
  every template).
- Product galleries/variant selectors/banners must be authored mobile-first
  in their renderer CSS (matches existing responsive-override system from
  Phase 2 — `mobile` breakpoint is the fallback baseline, not an
  afterthought shrink of desktop).

## Explicit non-goals for Phase 5

- No A/B testing (explicitly out of scope for the whole engine per the
  original spec's sidebar constraint — "Do NOT add ... A/B Testing etc. at
  this stage").
- No CDN/edge-cache integration decisions made here (infrastructure
  concern, separate from the engine's own architecture).

## Dependencies on earlier phases

Builds on all of Phases 1–4; several items (SEO structured data, sticky
buy bar) specifically depend on Phase 3's product components existing.
