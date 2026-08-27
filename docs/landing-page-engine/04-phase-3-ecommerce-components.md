# Phase 3 — Ecommerce Components

**Status: planned, not implemented.**

## Goal

Add Product/ProductGrid/CategoryGrid/Combo/Banner/CTA component types to
the Phase 2 registry, all reading from the *existing* Product/Variant/
Category/Cart system — never a landing-page-local copy of product data.

## Core principle (non-negotiable, per `00-overview.md`)

```text
Store product references, not product snapshots.
```

A `product_grid` section stores `{"source": "manual", "product_ids": [10,
15, 21]}` (or `{"source": "category", "category_id": 12}`, etc.) — never a
copy of the product's name/price/image. At render time, the component
queries live `Product`/`ProductVariant` data. This means a price change in
the catalog is reflected on every landing page instantly, matching how the
existing storefront (`Shop`, `Category` Livewire components) already
behaves — no new caching-invalidation problem is introduced.

## Product selection architecture

Answering the spec's Section 10 question list as one design:

| Selection mode | How stored | How resolved at render |
|---|---|---|
| Manual | `product_ids: [int]` | `Product::whereIn('id', $ids)->active()->get()`, preserving the stored order |
| Category | `category_id: int`, `limit: int`, `sort: string` | `Category::find($id)->products()->active()->orderBy(...)->limit(...)->get()` |
| Latest | `sort: 'latest'`, `limit` | `Product::active()->latest()->limit(...)->get()` |
| Best-selling | `sort: 'best_selling'`, `limit` | requires an aggregate over `OrderItem` (sum quantity per product, active orders only) — **new read-only query, no new table** |
| Featured | `sort: 'featured'` | `Product::active()->where('featured', true)->get()` — `featured` column already exists on `Product` |
| Tag | *not currently possible* — `Product` has no tags table today | flagged as a gap; either add a lightweight `product_tags` pivot (new, small, catalog-owned — not landing-page-owned) or drop this selection mode from v1 scope |
| Price range | `min_price`/`max_price` | `Product::active()->whereBetween('price', [...])->get()` |
| Dynamic | any of the above, re-evaluated on every page load (not cached into the page) | this *is* the default behavior of every mode above — "dynamic" isn't a separate mode, it's what not-storing-a-snapshot already gives you for free |

Collection: **no `Collection` model exists** (confirmed by audit) — "select
by collection" from the spec is out of scope until/unless a `Collection`
model is added to the core catalog (a catalog-team decision, not a
landing-page one). Documented as a gap here, not silently dropped.

## Product variants

Reuses the existing chain end-to-end — **no second variant engine**:

- Variant selector UI renders from `ProductVariant::optionsMap` (already
  computed accessor building `['Color'=>'White','Size'=>'M']`).
- Price updates dynamically: selecting a variant combination resolves to a
  specific `ProductVariant` row, whose `sale_price ?? price` is shown —
  identical logic to `CartManager::addToCart()`'s existing price
  resolution, factored into a small shared helper both call.
- Image updates dynamically: `ProductVariant::displayImage` accessor
  already exists and falls back to the product's image if the variant has
  none.
- Unavailable variants disabled: `ProductVariant.status` / `stock_quantity
  <= 0` checked client-side (via a small Livewire-exposed availability map)
  to disable incompatible option combinations, mirroring how a normal
  product page would.
- No variants: falls back to product-level `price`/`sale_price`/
  `featured_image_id` — same fallback `CartManager::addToCart()` already
  implements.
- Multiple option groups: handled naturally since `optionsMap` is already
  keyed by attribute name, not hardcoded to Color/Size.

## Product card

Configurable visibility flags per section instance (`show_image`,
`show_badge`, `show_short_description`, `show_regular_price`,
`show_sale_price`, `show_discount_badge`, `show_rating`, `show_stock_status`,
`show_variant_selector`, `show_quantity`, `show_add_to_cart`,
`show_buy_now`, `show_wishlist`) — a settings map on the `product`/
`product_grid` section, not a new model. Rating/wishlist only render if
`Product::reviews()`/`Product::wishlistItems()` data exists (both already
exist per the audit) — no placeholder/fake data.

## Add to Cart

**Reuses `CartManager` exactly as-is** — the landing-page product component
dispatches the same `add-to-cart` Livewire browser event `CartManager`
already listens for (`#[On('add-to-cart')]`), passing `product_id`,
`variant_id` (nullable), `quantity`. No new cart code. Answering the spec's
Section 13 questions:

1. Existing cart service — yes, exclusively.
2. AJAX or Livewire — Livewire event dispatch (matches existing pattern
   everywhere else in the app).
3. Page reload — no, Livewire partial update.
4. Mini-cart updates instantly — yes, `CartManager` already re-renders on
   its own events.
5. Success notification — reuse the existing `toast` dispatch convention.
6. Redirect to cart — configurable per CTA (see CTA/Button system below),
   default stays on page.
7. Multiple products — a "bundle add" dispatches multiple `add-to-cart`
   events in sequence (or `CartManager` gains a batch-add method if that
   proves janky in testing — implementation detail decided during build,
   not architecture).
8. Default variant — component pre-selects the variant marked
   `default_selected` if the template config sets one (matches the pattern
   already used in `season-fresh-mango`'s `content.json` packages field),
   else first in-stock.
9. Missing required variant — Add to Cart button disabled client-side until
   a valid combination is selected (no server round-trip needed to know
   this).
10. Out of stock — button disabled + "Out of stock" state shown, matching
    `CartManager`'s existing `stock_quantity` enforcement.

## Buy Now

Answering Section 14: **Buy Now does not clear the existing cart.** It adds
the item via the same `CartManager::addToCart()` path (so stock/dedup logic
isn't duplicated), then immediately redirects to
`route('ecomx-fashion.checkout')`. This is the simplest architecture that
reuses 100% of existing cart/checkout code and avoids inventing a
"temporary checkout context" concept the existing `Checkout` component has
no notion of. Quantity and selected variant are preserved because they're
already encoded in the `CartItem` row created by the shared add-to-cart
path.

## Combo / Bundle products

This is the one area where **product-level work may be needed outside the
landing-page engine**, because `Product.combo_allowed` and
`Product::comboItems()` already exist in the catalog today (per audit
finding #7) — meaning combos are likely already a first-class `Product`
concept (a product with `product_type` indicating combo, and
`comboItems()` linking to its constituent products), not something the
landing-page engine should reinvent.

**Decision**: a landing page's "combo" section is a **display/selection
component only** — it references an existing combo `Product` by ID (same
reference-not-snapshot rule as everything else) and renders its constituent
items (via `comboItems()`) for display, letting the customer pick
variants per constituent product if the combo's items have variants. Add to
Cart for a combo dispatches the same `add-to-cart` event with the combo
product's ID — `CartItem.combo_id` already exists as a column (per audit
finding #10), meaning cart-line representation for combos is **already
solved** by the existing schema. This phase's real work is: (a) confirming
`comboItems()`'s actual shape (not yet read in the audit — needs a
follow-up read of `app/Models/Product.php`'s combo relations before
building), and (b) building the *display* UI, not new commerce logic.

If it turns out combos are *not* yet a real backend concept (only scaffolded
columns/relations with no working checkout-side support), that gap must be
closed in the core Product/Order system first — flagged as a hard
dependency to verify before Phase 3 implementation starts, not an
assumption to build on top of blind.

## Banner system

Configurable: image, mobile image (separate), heading, description, button
text, button URL/action, background, overlay, alignment, height. Answering
Section 16: desktop/mobile images differ (two fields, standard `<picture>`
srcset swap). Link targets go through the CTA/action system below (product,
category, landing page, external URL — no hardcoded URL-only field). Video
background: deferred to Phase 5 polish (performance risk — flagged as an
open question, not silently included).

## CTA / Button action system

A button's `action` is a small tagged object, not a bare URL string:

```json
{ "action": { "type": "url", "url": "https://..." } }
{ "action": { "type": "product", "id": 123 } }
{ "action": { "type": "category", "id": 12 } }
{ "action": { "type": "landing_page", "id": 45 } }
{ "action": { "type": "add_to_cart", "product_id": 123, "variant_id": null } }
{ "action": { "type": "buy_now", "product_id": 123 } }
{ "action": { "type": "scroll_to", "section_id": "order_1" } }
{ "action": { "type": "phone", "number": "01700000000" } }
{ "action": { "type": "whatsapp", "number": "8801700000000" } }
```

A single `App\LandingPageEngine\CtaActionResolver::resolve($action):
string|Closure` maps `type` → either a URL (for `url`/`product`/`category`/
`landing_page`/`phone`/`whatsapp`) or a Livewire dispatch (for
`add_to_cart`/`buy_now`/`scroll_to`). New action types register the same
way component types do — no hardcoding into the button component itself.

## Explicit non-goals for Phase 3

- No product tags model added unless explicitly requested (documented gap
  instead).
- No `Collection` model added (documented gap, catalog-team decision).
- No video-background banners (Phase 5 or later, performance-gated).
- No checkout embedded directly in a landing page (Phase 4).

## Dependencies on earlier phases

Requires Phase 2's component registry contract. Does not require Phase 2's
visual builder UI to exist first if Phase 3 components are shipped as
extensions to Phase 1's flat schema-field editor instead — this ordering
flexibility is itself an open question for whoever picks this phase up
(see `07-open-questions-and-decisions.md`).
