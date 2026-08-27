# Phase 4 — Checkout Integration & Order Attribution

**Status: planned, not implemented.**

## Goal

Let a landing page lead a customer all the way into a real order, and make
sure that order is attributable back to the landing page it came from —
using entirely existing systems.

## Checkout integration

Per the spec's Section 18 questions, and the audit's finding that
`App\Livewire\EcomxFashion\Checkout` is a mature, working component:

- **Checkout is never duplicated.** A landing page's checkout CTA is a link
  to `route('ecomx-fashion.checkout')` (Buy Now flow, Phase 3) or a normal
  Add to Cart → customer navigates to cart → checkout (standard flow). No
  "checkout section" embedded inline inside a landing page's builder
  content — that would mean reimplementing `Checkout`'s validation, order
  creation, and payment logic a second time, which directly violates the
  spec's own core architectural rule.
- If product-market feedback later shows a strong need for a single-page
  "landing page + embedded checkout" experience (common in some ad-funnel
  patterns), the correct architecture is **not** a landing-page-local
  checkout form — it's making `Checkout` embeddable as a Livewire component
  inside a landing page's content (i.e., the *existing* component, mounted
  in a new place), which is a UI-composition change to `Checkout`, not a
  new checkout system. Flagged as a future option, not built now.
- Coupons: `Checkout::placeOrder()` doesn't apply coupons today (confirmed
  gap, audit finding #13). This must be fixed in `Checkout` itself to
  benefit both the normal storefront and landing pages simultaneously — a
  landing-page-only "promo code" field would be exactly the kind of
  parallel commerce logic this project must avoid. Out of scope for the
  Landing Page Engine to build; flagged as a prerequisite ticket against
  the core checkout if promo codes are needed.
- Shipping: reuses `Checkout`'s existing flat 2-zone `$deliveryAreas` table
  as-is. No landing-page-specific shipping logic.
- Payment methods: reuses `Checkout`'s existing COD/manual-bKash flow
  as-is.
- Stock reservation: reuses `StockService::commitOrder()` exactly as
  `Checkout` already calls it — no separate reservation logic for
  landing-page-originated orders.

## Order attribution

This is **already solved** by the existing marketing system (audit finding
under "Marketing / attribution", full schema in
[`../marketing-tracking-database.md`](../marketing-tracking-database.md)),
provided landing pages are served as real routes under the standard
middleware stack — which Phase 1 already ensures (`routes/landingpage.php`,
inheriting the global `web`-group `MarketingTracker`/`DeviceTracker`
middleware regardless of which storefront engine is active).

What happens automatically, with zero landing-page-specific code:

1. A visitor lands on `/lp/{slug}?utm_source=facebook&utm_campaign=...`.
2. `MarketingTracker` middleware fires a `PageView` event via
   `MarketingEventService::recordForCurrentRequest()`.
3. `AttributionService::resolve()` captures `landingUrl`/`landing_path` =
   `/lp/{slug}` into the current `MarketingSession` (first-touch, since
   it's a new session) and every subsequent `MarketingAttribution` row for
   that session/device carries `first_touch_landing_path`.
4. When the visitor eventually buys (`AddToCart` → `InitiateCheckout` →
   `Purchase`, all already firing from `CartManager`/`Checkout`), the
   resulting `MarketingEvent`/`MarketingAttribution` rows — including the
   `Purchase` event tied to the created `Order` via `marketing_events.order_id`
   — carry `first_touch_landing_path = '/lp/{slug}'` and
   `first_touch_utm_*` automatically.

**No new `landing_page_id` FK on `Order` is needed for basic attribution.**
Path-string matching (`first_touch_landing_path LIKE '/lp/%'`, or exact
match against a specific page's slug) is sufficient to answer "did this
order come from a landing page, and which one" using tables that already
exist.

### When a real `landing_page_id` FK might still be worth adding

If path-string matching proves too fragile in practice (e.g. a landing
page's slug is later renamed, breaking historical joins), a small, additive
migration adding a nullable `landing_page_id` to `marketing_events` (not to
`Order` — keep it in the marketing domain where attribution already lives)
resolved at `PageView`-time from the current route would be a minimal,
backward-compatible addition. This is documented as an **open decision**
(see `07-open-questions-and-decisions.md`), not built preemptively —
per the plan's own principle of not over-engineering before the need is
measured.

## Explicit non-goals for Phase 4

- No new attribution tables, cookies, or tracking scripts.
- No coupon system built inside the landing-page engine.
- No embedded/duplicated checkout form.

## Dependencies on earlier phases

Requires Phase 1's public route (for attribution to flow) and Phase 3's
Add to Cart/Buy Now CTAs (for there to be a purchase funnel to attribute in
the first place). Does not depend on Phase 2's visual builder.
