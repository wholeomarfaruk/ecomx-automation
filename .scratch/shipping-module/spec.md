# Shipping module

Replace the hard-coded checkout delivery areas (Inside Dhaka 70 / Outside Dhaka 130,
duplicated in both theme checkouts) with admin-managed zones, methods and rates.

## Principles

- Checkout stays simple: one "Delivery area" dropdown. A method choice only
  appears when the chosen area has more than one active method.
- One calculator (`App\Services\Shipping\ShippingCalculator`) prices every checkout.
- Free-delivery coupons / campaigns stay in the Offer module. Zone `code` is passed
  as the offer context's `shipping_method`, so existing `dhaka` / `outside`
  conditions keep matching.
- The order keeps a snapshot (zone, method, breakdown) so later rate edits never
  change past orders.
- Live courier rates are out of scope.

## Concepts

- **Zone**: a named delivery area the customer picks (Inside Dhaka, Outside Dhaka, Dhaka suburbs…).
- **Method**: how it is delivered inside a zone (Standard, Express, Pickup), with a rate.
- **Rate type**: flat, weight (base + per kg), weight bands, quantity (first + each extra),
  cart-amount bands, percentage (min/max), free. Weight types can use volumetric weight
  (L×W×H ÷ divisor) when larger than actual weight. Optional "free over" per method.
- **Product / variant override** (ticket 02): shipping class, fixed charge, free delivery,
  extra per unit, zone restriction; precedence variant → product → class → method default;
  admin-selected cart strategy for combining per-item charges.
- **Extras** (ticket 03): COD charge, advance delivery-charge payment, per-zone minimum
  order and max weight.

## Tickets

1. `issues/01-zones-methods-rates.md`
2. `issues/02-product-variant-overrides.md`
3. `issues/03-checkout-extras.md`
