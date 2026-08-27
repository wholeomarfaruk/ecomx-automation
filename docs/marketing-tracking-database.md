# Marketing Tracking Database

Database foundation for the canonical marketing event ledger. This is schema
only — no tracking logic, middleware, or destination wiring is implemented
in this step (see `app/Marketing/` for the destination/context/attribution
code that will eventually persist against these tables).

## Tables

| Table | Purpose |
|---|---|
| `marketing_sessions` | Groups requests into a browsing session; carries landing page, referrer, device/browser info, and UTM/click-ID capture for that session |
| `marketing_events` | Canonical, immutable marketing event ledger — one row per business event (PageView, ViewContent, AddToCart, InitiateCheckout, Purchase, Lead) |
| `marketing_event_items` | Product-level snapshot rows for events with line items (ViewContent/AddToCart/InitiateCheckout/Purchase) |
| `marketing_attributions` | One row per event — a snapshot of first-touch, last-touch, and session-touch attribution at the moment the event occurred |
| `marketing_event_destinations` | One row per (event, destination, channel) — tracks delivery status/attempts/retry for each destination the event is sent to |

## Reused existing tables (not duplicated)

- **`devices`** — long-lived anonymous/device identity (10-year `device_fingerprint` cookie). `marketing_sessions.device_id` / `marketing_events.device_id` reference this instead of a new visitor table.
- **`device_visits`** — remains the raw page/access log (unchanged, untouched by this step). It is *not* the same thing as `marketing_events`: `device_visits` logs every GET request; `marketing_events` logs intentional canonical marketing events.
- **`device_ip_addresses`** — IP history per device, reused as-is for IP-wise reporting.
- **`customers`**, **`orders`**, **`order_items`** — reused for identity (email/phone) and commerce data (currency/total/line items). No columns added.

No `marketing_visitors` table was created — it would have duplicated `devices`.

## Relationships

```
devices                    customers                  orders
   │                           │                          │
   ├── device_visits           │                          │
   │   (unchanged)             │                          │
   │                           │                          │
   └── marketing_sessions ─────┘                          │
              │                                            │
              └── marketing_events ────────────────────────┘
                         │
             ┌───────────┼────────────┐
             ▼           ▼            ▼
   marketing_event_items │  marketing_event_destinations
                          │
                 marketing_attributions
                 (one row per event)
```

`products` / `product_variants` are referenced (nullable FKs) from
`marketing_event_items`, but the item row snapshots `product_name`/`sku`/
`unit_price` at event time rather than relying solely on the live product
record — so historical marketing data stays accurate if a product's price
or name changes later.

## Event lifecycle

```
Business action (page view, add to cart, checkout, purchase, lead)
        │
        ▼
marketing_events row created (event_id = UUID, event_name, occurred_at, ...)
        │
        ├── marketing_event_items      (if the event has line items)
        ├── marketing_attributions     (one row: first/last/session touch snapshot)
        └── marketing_event_destinations (one row per destination+channel the event is sent to)
```

## Browser + server delivery / deduplication

A single canonical event (`marketing_events.event_id`) may be delivered
through multiple channels without being recorded as multiple events:

```
marketing_events
-----------------
event_id = abc-123
event_name = Purchase

marketing_event_destinations
-----------------------------------------
event_id | destination | channel | status
abc-123  | meta        | browser | success
abc-123  | meta        | server  | success
abc-123  | ga4         | browser | success
abc-123  | tiktok      | server  | success
```

The unique constraint `(marketing_event_id, destination, channel)` on
`marketing_event_destinations` is the mechanism that prevents the same
event from being recorded twice for the same destination+channel pair —
verified by attempting a duplicate insert during implementation (correctly
rejected by the database).

`marketing_events.event_channel` describes **where the canonical event
originated** (`browser` / `server` / `backend`), not where it is delivered
to — that's `marketing_event_destinations`' job. The two are intentionally
separate concepts.

## Attribution

`marketing_attributions` is a **snapshot**, not a live/current-cookie
lookup — enforced by a unique constraint on `marketing_event_id` (one row
per event, verified during implementation: a second insert for the same
event is correctly rejected). This means historical reports never change
retroactively just because a visitor's current attribution cookies changed.

Each event snapshot carries three independent touch records:
first-touch, last-touch, and session-touch — each with its own
source/medium/campaign/term/content and click-ID fields.

## Reporting dimensions supported

| Dimension | Tables involved |
|---|---|
| Customer-wise | `customers` → `marketing_events`/`marketing_sessions` (via `customer_id`) |
| Device-wise | `devices`, `device_visits`, `device_ip_addresses` → `marketing_sessions`/`marketing_events` (via `device_id`) |
| IP-wise | `device_ip_addresses`, `marketing_sessions.ip_address`, `marketing_events.ip_address` (no new IP table) |
| Session-wise | `marketing_sessions` → `marketing_events` |
| Page-wise | `device_visits` (raw log) + `marketing_events` (canonical PageView events) |
| Product-wise | `marketing_event_items` → `products`/`product_variants` |
| Campaign/source/medium-wise | `marketing_events.utm_*`/`source`/`medium`/`campaign`, `marketing_attributions.first_touch_*`/`last_touch_*` |
| Event-wise / full history | `marketing_events` with all its relations |
| Destination delivery status | `marketing_event_destinations` |

## Indexes

Composite indexes were added on the query dimensions expected to dominate
reporting: `(event_name, occurred_at)`, `(device_id, occurred_at)`,
`(customer_id, occurred_at)`, `(session_id, occurred_at)`,
`(utm_source, utm_campaign, occurred_at)`, `(source, medium, occurred_at)`,
`(content_type, content_id, occurred_at)`, and per-click-ID + timestamp
indexes (`fbclid`, `gclid`, `ttclid`). No indexes were placed on JSON
columns.

Several composite index/constraint names exceed MySQL's 64-character
identifier limit when auto-generated from long column lists — these were
given explicit short names in the migrations (e.g.
`marketing_attributions_first_touch_idx`,
`marketing_event_destinations_unique`).

## Privacy considerations (not yet implemented)

`ip_address`, `user_agent`, and the JSON `identity_data`/`context_data`
columns on `marketing_events` can carry sensitive data (IP, UA, hashed or
raw email/phone depending on what the identity layer puts there). No
retention/redaction policy is implemented in this step — `device_visits`
already has a 90-day prune precedent (`PruneDeviceVisits` command) that the
same pattern should extend to for `marketing_events`, but with commerce
events (Purchase/Lead) likely needing longer retention than raw analytics
events. This is a decision for a later step, not implemented here.

## Enums

- `App\Marketing\Enums\MarketingEventName` — PageView, ViewContent, AddToCart, InitiateCheckout, Purchase, Lead. `marketing_events.event_name` is stored as a plain `string` column (not a DB enum type) so new event names don't require a migration — only a new enum case.
- `App\Marketing\Enums\MarketingDestination` — meta, google, ga4, tiktok, webhook.
- `App\Marketing\Enums\MarketingDeliveryStatus` — pending, processing, success, failed, retrying, skipped.
- No `AttributionTouchType` enum — unnecessary once attribution is one row per event (first/last/session touch are columns, not enum-discriminated rows).

## Known gap to resolve when wiring destinations (not a schema issue)

`OrderItem`'s actual fields are `product_id`/`quantity`/`unit_price`, while
`MetaPayloadBuilder::buildPurchaseData()` expects item arrays keyed
`item_id`/`quantity`/`price`. `OrderItem` was **not modified** — a mapping
step is needed wherever an `Order` is turned into a `Purchase` event /
`MarketingEventItem` rows, in a later step.

## Verification performed

- `php artisan migrate` — all 5 migrations ran successfully.
- Created a real `MarketingSession` → `MarketingEvent` → `MarketingEventItem` +
  `MarketingAttribution` + two `MarketingEventDestination` rows (Meta
  browser + Meta server) against real `Device`/`Customer` records, then
  re-fetched with all relations eager-loaded — every relationship
  (`device`, `customer`, `session`, `items`, `attribution`, `destinations`)
  resolved correctly, and the `status` column correctly cast to the
  `MarketingDeliveryStatus` enum.
- Confirmed the `(marketing_event_id, destination, channel)` unique
  constraint blocks a duplicate destination+channel insert.
- Confirmed the `marketing_event_id` unique constraint on
  `marketing_attributions` blocks a second attribution row for the same
  event.
- Test data was cleaned up afterward — the tables are empty in this
  environment.

## Not implemented in this step

Per scope: no changes to `DeviceTracker`, no attribution-cookie middleware,
no controller wiring, no `MarketingEventService` dispatch calls, no GTM/
Meta Pixel/CAPI/TikTok/GA4 wiring, no queue connection change, no admin
reports/dashboard, no retention/deletion logic. These are later steps.
