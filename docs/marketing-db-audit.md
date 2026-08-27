# Marketing Tracking — Database Audit (pre-Step 13)

No files created/modified/migrated. Read-only audit.

## 1. Existing tracking architecture

Two separate, unconnected systems currently exist:

1. **`devices` / `device_visits` / `device_ip_addresses`** — a mature, wired, running visitor/device tracking system. Cookie-based (`device_fingerprint`, 10-year lifetime), populated on every web request via `DeviceTracker` middleware.
2. **`app/Marketing/*`** — the full canonical-event architecture built across Steps 1-11 (Context, Identity, Attribution, Events, Destinations/Meta, Jobs, Services). **Fully coded, zero database persistence, and not invoked from anywhere in the app.**

These two systems have never been connected.

## 2. Existing relevant tables

| Table | Exists? | Purpose |
|---|---|---|
| `devices` | Yes | Visitor/device identity (fingerprint, UA, browser, platform, customer_id/user_id link, trust flags) |
| `device_visits` | Yes | Page-view log per device (URL, referer, morphed to Product/Category) |
| `device_ip_addresses` | Yes | IP history per device (hits, first/last seen) |
| `customers` | Yes | email, phone, alternative_phone, first_name, last_name, etc. |
| `orders` / `order_items` | Yes | total_amount, currency, per-item product_id/quantity/unit_price |
| `jobs` / `failed_jobs` / `job_batches` | Yes (migrated, unused — `QUEUE_CONNECTION=sync`) | Laravel default queue tables |
| `sessions` (Laravel default) | **No** | `SESSION_DRIVER=file`, no DB session table |
| `pos_sessions` | Yes | POS register sessions — unrelated to web visitor sessions |
| `marketing_visitors` / `marketing_sessions` / `marketing_events` / etc. | **No** | Not yet created (this is what Step 13 proposes) |
| Any campaign/analytics/attribution table | **No** | Confirmed via migration grep — none exist |

## 3. `device_visits` deep analysis

**Full model** (`app/Models/DeviceVisit.php`):
```php
class DeviceVisit extends Model
{
    const UPDATED_AT = null;
    protected $fillable = [
        'device_id', 'url', 'route_name', 'method', 'status_code', 'ip_address', 'referer',
        'visitable_type', 'visitable_id', 'content_type', 'content_slug', 'content_title',
    ];
    public function device(): BelongsTo { return $this->belongsTo(Device::class); }
    public function visitable(): MorphTo { return $this->morphTo(); }
}
```

**Data flow**:
```
Web request
     │
     ▼
DeviceTracker middleware (web group, appended after StartSession)
     │
     ├── handle(): read/set `device_fingerprint` cookie (UUID, 10yr)
     │        └── resolveDevice(): raw INSERT...ON DUPLICATE KEY UPDATE keyed on devices.fingerprint (unique)
     │              → same cookie value next visit = same Device row, forever
     │
     └── terminate() [post-response, non-blocking]
              ├── touchActivity() — throttled 300s, updates last_active_at
              ├── recordIpAddress() — upsert device_ip_addresses
              └── recordVisit() [GET only, excludes ajax/Livewire]
                       └── DeviceVisit::create([url, route_name, method, status_code, ip_address, referer, visitable...])
```

**Verdict: B) Reuse with modification.**
- `devices.fingerprint` (cookie-backed, 10-year) is a genuinely stable long-lived anonymous visitor identity — exactly what a `marketing_visitors` table would try to reinvent from scratch.
- `device_visits` does **not** capture UTM/click-IDs, landing page, or session grouping — it's a raw page-view log, not an attribution or session table. It cannot replace `marketing_sessions` or attribution storage as-is.
- **Recommendation**: `marketing_events.device_id` (FK → `devices`) instead of a new `marketing_visitors` table. Do not build a parallel visitor-identity table.

## 4. Existing models/services/middleware involved

- `App\Http\Middleware\DeviceTracker` — sole writer of `devices`/`device_visits`/`device_ip_addresses`. Registered globally on `web` group in `bootstrap/app.php`.
- `App\Support\DeviceActivity` — reads device/visit data for admin "active now" UI.
- `App\Console\Commands\PruneDeviceVisits` — deletes visits older than 90 days (retention precedent already exists).
- `App\Marketing\Context\MarketingContextBuilder` — bound as scoped singleton in `AppServiceProvider`, **never resolved anywhere**.
- `App\Marketing\Services\MarketingEventService`, `App\Marketing\Jobs\DispatchMarketingEventJob` — fully built, **never called** from any controller/Livewire component.
- No `DeviceService`/`DeviceResolver` class — resolution logic lives inline in `DeviceTracker::resolveDevice()`.

## 5. Existing columns inventory (tracking-relevant tables)

**`devices`** (from fillable): `customer_id, user_id, fingerprint (unique), user_agent, sec_ch_ua, device_type, platform, device_brand, device_model, manufacturer, operating_system, os_version, browser, browser_version, app_version, build_number, screen_resolution, screen_density, language, timezone, fcm_token, ip_address, last_login_at, last_active_at, is_trusted, is_allowed`

**`device_visits`**: `device_id (FK), url, route_name, method, status_code, ip_address, referer, visitable_type/id (morph), content_type, content_slug, content_title, created_at` (no `updated_at`)
Indexes: `[device_id, created_at]`, `[content_type, created_at]`

**`device_ip_addresses`**: `device_id (FK), ip_address, hits, first_seen_at, last_seen_at, timestamps`
Unique: `[device_id, ip_address]`. Index: `ip_address`.

**`customers`** (marketing-relevant subset): `email, phone, alternative_phone, first_name, last_name, full_name` — all already consumed by `App\Marketing\Identity\IdentityResolver`/`CustomerMatcher`.

**`orders`**: `customer_id, currency, total_amount, placed_at, ...`
**`order_items`**: `order_id, product_id, variant_id, product_name, sku, quantity (decimal:3), unit_price (decimal:4), total_amount`

**Gap found**: `MetaPayloadBuilder::buildPurchaseData()` expects item arrays keyed `item_id/quantity/price` — `OrderItem`'s actual fields are `product_id/quantity/unit_price`. A mapping step (not a schema change) is needed wherever an `Order` is turned into a `Purchase` event.

## 6. Required Marketing Tracking capabilities → existing table mapping

| Capability | Existing table/code | Gap |
|---|---|---|
| Visitor identity | `devices.fingerprint` | none — reuse |
| Device/browser/platform info | `devices` | none — reuse |
| IP history | `device_ip_addresses` | none — reuse |
| Page-view log | `device_visits` | none — reuse (or extend for content join) |
| Session grouping | — | **missing entirely**, genuinely new |
| UTM/click-ID capture | `app/Marketing/Context/MarketingContext.php` (in-memory only) | not persisted anywhere |
| First/last touch attribution | `app/Marketing/Attribution/*` (cookie-only) | **`mk_first_touch`/`mk_last_touch` cookies are never actually set** — `AttributionService::cookies()` exists but is called by nothing (verified: `grep -rn "cookies(" app/Marketing/` → only the definition, no call site) |
| Canonical marketing event (PageView/AddToCart/Purchase/etc.) | `app/Marketing/Events/*` (in-memory, fire-and-forget) | **no persistence table** |
| Event→destination delivery status/retry history | `app/Marketing/Jobs/DispatchMarketingEventJob` (queued but `QUEUE_CONNECTION=sync`, no logging) | **no persistence table** |
| Meta CAPI payload building | `MetaAdapter`/`MetaPayloadBuilder` (real, working) | fine as-is, just needs event source data |
| Google/TikTok CAPI | — | **no adapter code exists at all**, out of scope for Step 13 |
| Client-side pixel (fbq/gtag/dataLayer) | — | **no embed anywhere in blade views** |
| Customer/order data for Purchase events | `customers`, `orders`, `order_items` | field-name mapping needed, no schema change |

## 7. Reuse tables (Category A)

- `devices` — visitor/device identity. `marketing_events.device_id` → FK here.
- `device_ip_addresses` — IP history, no change needed.
- `customers`, `orders`, `order_items` — no change needed for marketing purposes.
- `jobs`/`failed_jobs` — already provisioned for when `QUEUE_CONNECTION` moves off `sync`.

## 8. Modify tables (Category B)

**None strictly required.** `device_visits` *could* optionally gain `utm_source/utm_medium/utm_campaign/fbclid/gclid/ttclid` columns to unify page-view logging with attribution capture, but this conflates two different concerns (raw page-view log vs. marketing event ledger) and risks widening a high-volume table pruned every 90 days — attribution data should outlive that retention window. **Recommendation: do not modify `device_visits`.** Keep it as pure page-view/access log; build the marketing event ledger as a new, separate table.

## 9. New tables required (Category C) — revised from the Step 13 proposal

Given the audit, the original 7-table proposal is reduced:

| Original Step 13 table | Verdict |
|---|---|
| `marketing_visitors` | **Drop.** Duplicates `devices`. Use `device_id` FK instead. |
| `marketing_sessions` | **Keep, but optional for v1.** Nothing in the app groups requests into sessions today. Can be deferred — Step 13 can ship without it (attribution/event tables don't strictly need `session_id` to function; it can be added later as a nullable FK). |
| `marketing_events` | **Keep — genuinely new, core table.** No persistence exists today for canonical events. |
| `marketing_event_items` | **Keep — genuinely new.** Needed for product-level reporting; no existing table serves this for marketing purposes (order_items is order-scoped, not event-scoped, and a ViewContent/AddToCart event has no order at all). |
| `marketing_attributions` | **Keep — genuinely new**, but per Step 13.26/13.27's own correction: **one row per event** (snapshot), not one row per touch-type. This also finally gives `AttributionService::cookies()` a reason to be wired up (once persisted, first/last touch can be read back from DB instead of only cookies). |
| `marketing_event_destinations` | **Keep — genuinely new.** Nothing tracks Meta CAPI delivery status/attempts/dedup today; `MetaAdapter::send()` currently just throws on failure with no record kept. |
| `marketing_destination_logs` | **Defer to a later step.** Useful once delivery volume justifies per-attempt payload logging, but adds complexity (privacy-sensitive payload storage) beyond what's needed to get events flowing end-to-end for the first time. `marketing_event_destinations` alone (status + attempts + last error) covers v1 observability. |

**Net recommendation for Step 13 v1**: build **`marketing_events`**, **`marketing_event_items`**, **`marketing_attributions`**, **`marketing_event_destinations`** (4 tables, not 7). Reference `devices.id` for visitor identity. Add `marketing_sessions` and `marketing_destination_logs` in a later step once there's a real need (session-level reporting, or delivery volume that justifies per-attempt logs).

## 10. Enums

The 4 enums proposed in Step 13 (`MarketingEventName`, `MarketingDestination`, `MarketingDeliveryStatus`, `AttributionTouchType`) are unaffected by this audit — none currently exist, all are needed regardless of the table-count reduction above. Note `AttributionTouchType` becomes unnecessary if `marketing_attributions` is one-row-per-event (per #9) rather than one-row-per-touch-type — it was only needed for the abandoned 3-row-per-event design.

## 11. Privacy/security note carried over

Step 13.15's flag stands: `devices.ip_address`/`user_agent`, and any future `marketing_events.ip_address`/`identity_data` (email/phone from `IdentityResolver`), are sensitive. `device_visits` already has a 90-day prune precedent (`PruneDeviceVisits` command) — the same retention pattern should extend to `marketing_events`.

## 12. Risks / conflicts

- **`DeviceTracker` ordering**: any future code reading `devices.fingerprint` to populate `marketing_events.device_id` must run after `DeviceTracker` (which is appended to `web` group) so `$request->attributes->get('device')` is populated — confirmed via `EnforceBlocks`' existing dependency comment on the same attribute.
- **Attribution cookies are dead code today**: `mk_first_touch`/`mk_last_touch` are read by resolvers but never written by any middleware — this needs to be wired (a small middleware calling `AttributionService::resolve()` + `cookies()` and attaching to the response) for first/last touch to ever persist across visits, independent of the database work.
- **`QUEUE_CONNECTION=sync`**: `DispatchMarketingEventJob` will block the request/response cycle until this is changed — fine for initial testing, but should be flagged before going live with real Meta CAPI calls.

## Summary

**REUSE**: `devices`, `device_ip_addresses`, `customers`, `orders`/`order_items`, `jobs` (queue tables).
**MODIFY**: none.
**NEW (Step 13 v1)**: `marketing_events`, `marketing_event_items`, `marketing_attributions` (one row per event), `marketing_event_destinations`.
**DEFERRED**: `marketing_sessions`, `marketing_destination_logs` (add later, not blocking v1).
**DROPPED**: `marketing_visitors` (duplicates `devices` — use `device_id` FK).
