# Landing Page Engine — Overview

This is the entry point for the Landing Page Engine documentation set. Read
these in order (`01` → `07`); each phase doc builds on the decisions of the
ones before it. `01-audit-findings.md` is the permanent reference for what
already existed in this codebase before this engine — read it before
proposing anything new, so existing systems get reused instead of
duplicated.

## Why this exists

The app needs a way to create standalone marketing/campaign/product-launch
pages (landing pages) that are not part of the fixed storefront page set
(`home`, `shop`, `category`, `product`, ...), but that still fully integrate
with the real product catalog, cart, checkout, and order/marketing
attribution systems already in the app. Landing pages must not become a
second, parallel ecommerce stack.

## Core concept

```text
Landing Page
      ↓
Page Structure (content JSON)
      ↓
Sections / Components
      ↓
Dynamic Content
      ↓
Frontend Renderer
```

A page is never stored as one giant HTML string. Content is structured JSON
(a `sections` array — see `02-phase-1-core.md` for the exact shape), and a
renderer maps structure → Blade output. This mirrors the shape already
chosen for the standalone template packages built earlier this project
(`resources/landingpage-templates/*/content.json`).

## The one rule that overrides every other decision in this doc set

```text
Landing Page Engine
        ↓
Existing Product System
        ↓
Existing Cart System
        ↓
Existing Checkout
        ↓
Existing Order System
        ↓
Existing Marketing Attribution System
```

Never:

```text
Landing Page
 ├── Own Products
 ├── Own Cart
 ├── Own Checkout
 ├── Own Orders
 └── Own Attribution/Tracking
```

Every phase doc below explicitly calls out which existing class/table it
reuses. If a phase doc proposes new commerce logic, that is a bug in the
plan, not a feature.

## Phase index

| Doc | Phase | Status |
|---|---|---|
| [`01-audit-findings.md`](01-audit-findings.md) | Repository audit (permanent reference) | Done |
| [`02-phase-1-core.md`](02-phase-1-core.md) | Phase 1 — Pages / Templates / Settings (DB + admin CRUD + basic renderer) | **Implemented** |
| [`03-phase-2-builder.md`](03-phase-2-builder.md) | Phase 2 — Visual section builder, responsive overrides, undo/redo, autosave | Planned |
| [`04-phase-3-ecommerce-components.md`](04-phase-3-ecommerce-components.md) | Phase 3 — Product/Grid/Combo/Banner/CTA components, Add to Cart, Buy Now | Planned |
| [`05-phase-4-checkout-attribution.md`](05-phase-4-checkout-attribution.md) | Phase 4 — Checkout integration, order attribution, coupon gap | Planned |
| [`06-phase-5-polish.md`](06-phase-5-polish.md) | Phase 5 — SEO, preview URLs, performance, security, accessibility | Planned |
| [`07-open-questions-and-decisions.md`](07-open-questions-and-decisions.md) | Running decision log (spec Section 40 questions, answered) | Living document |

## Related existing docs (do not duplicate, cross-reference instead)

- [`../marketing-tracking-database.md`](../marketing-tracking-database.md) — the marketing event/attribution schema this engine plugs into for free, as long as landing pages are served through real routes under the existing middleware stack.
- [`../marketing-db-audit.md`](../marketing-db-audit.md) — related marketing DB context.
