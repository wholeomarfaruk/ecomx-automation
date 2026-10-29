# Order exchange (inside the same order)

Status: implemented (2026-10-30)

## Case

Customer bought 2 × 2,200 and received both. They keep one, send one back, and take a 1,950 dress
instead. No new order: the exchange lives inside the original order, on a second courier parcel with
its own status.

## Model

- `order_exchanges`: order, `number` (#1, #2 … per order), status, reason, `charge_amount` (exchange
  delivery charge the customer pays, also added as an `order_charges` row), `courier_shipment_id`.
- `order_exchange_items`: the delivered lines (and quantities) coming back.
- `order_items.exchange_id`: the new lines an exchange adds.
- `courier_shipments.type` (`normal` | `exchange`), `courier_shipments.merchant_ref` (the reference
  sent to the courier: `268` or `268-EX1`).

Status: requested → booked → in_transit → delivered → returning → completed, plus failed / cancelled.
The order's own status stays Delivered.

## Money

- Credit for the units coming back = their price after their share of the order discount. The order
  discount belongs to the original lines only: `returnDiscountFactor()` and
  `PostOrderReturn::newlyReturnedAmounts()` leave exchange lines out, and those lines are credited at
  full price.
- While the exchange runs, the credit is taken off the total (`Order::exchangeCreditAmount()`), so the
  order's due is exactly what the exchange parcel collects. When it completes, the credit moves into
  `returnedAmount()`; when it fails, it is gone.
- A negative balance means the shop owes the customer: parcel COD 0, and the refund goes through the
  order's Refund button.

## Stock

- New lines are taken from stock when the exchange is opened (`bookItem`). With Inventory on they must
  be packed before booking the parcel.
- Completed → the old units are returned and restocked through `RecordOrderReturns`.
- Failed → the new units are returned and restocked, and the charge is dropped unless kept.
- Cancelled (only before any parcel) → the new lines are unpacked, their stock reversed, and the lines
  and the charge removed.
- Fulfillment stays Fulfilled when every return is explained by an exchange
  (`CloseExchange::syncFulfillment`).

## Courier / webhooks

- Book via API: books a normal parcel (no integrated driver books a real exchange type yet) with
  `merchant_order_id` / invoice `268-EX1` and an instruction to collect the old item.
- Attach: a parcel booked on the courier's panel, entered by its consignment / tracking ID.
- `ApplyCourierStatus`: an exchange parcel moves only its exchange (`SyncExchangeFromCourier`, forward
  only, never auto Completed/Failed). It never changes the order's status or courier snapshot, so
  "Return received" and the other existing flows are unaffected.
- Webhook matching: tracking/consignment first, then `merchant_ref`, then the legacy plain order id,
  which only matches non-exchange parcels. Before this, `268-EX1` would have matched order 268's first
  parcel, because MySQL casts it to 268.
- COD collected on an exchange parcel: idempotent per parcel (tracking number on the payment). The
  per-order guard used to drop it. The fee comes from the parcel's own delivery charge + COD rate.
- `CourierManager::cancelShipment` on an exchange parcel unlinks it, and the exchange goes back to
  Requested.

## Guards (from the review)

- No exchange while a returned parcel is waiting to be received (`ReceiveReturn::awaiting`).
- Old item received: only with no parcel (swapped in the shop), or once the parcel is Delivered or
  Returning. Customer refused: only once the parcel is In transit or later.
- Remove parcel (Booked only): unlinks a parcel attached by mistake. It is marked cancelled here only,
  not at the courier. Attaching the same ID again reuses that row.
- Save Returns refuses units a running exchange is already bringing back, since they would be
  credited twice.
- `ReceiveReturn::totalFor` follows the same maths as `recalculateTotals` (exchange lines have no
  discount; the pending exchange credit is taken off).
- Paperfly tracking sync looks the parcel up by its `merchant_ref`. A tracking-ID webhook prefers the
  live parcel over a cancelled one with the same ID.

## Live-safety

The migration is additive. Existing rows read as normal parcels and non-exchange lines. Orders before
shipping never query the new tables.

## Not done

- With Accounts on, the exchange delivery charge is not posted to the books (order charges are posted
  only with the first sale). The new lines' sale and the old lines' return post through the usual
  Posting Pending flow.
- No courier API "exchange" parcel type (Pathao/Steadfast/RedX exchange fields are unverified).
