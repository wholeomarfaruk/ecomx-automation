# 01 — Zones, methods, rate types, calculator

Status: done

- `shipping_zones`, `shipping_methods` tables; seeded with Inside Dhaka (dhaka, flat 70)
  and Outside Dhaka (outside, flat 130) so behaviour is unchanged on deploy.
- `orders.shipping_zone_id`, `orders.shipping_method_id`, `orders.shipping_meta` snapshot.
- Admin: Settings → Shipping page to manage zones and methods (all rate types).
- `ShippingCalculator` used by both theme checkouts; hard-coded list removed.
- Checkout shows the charge, "Free" when zero, and "add ৳X more for free delivery".

## Comments

- 2026-09-28: Implemented. Checkout keeps one "Delivery area" dropdown; the
  "Delivery option" radios only render when a zone has 2+ active methods.
  Admin order detail shows "(zone · method)" next to Shipping. Admin-created
  orders (OrderCreate / OrderEditor / POS) still take a manual shipping amount.
