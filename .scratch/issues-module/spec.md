# Issues module + "Lost by courier"

Status: implemented (2026-10-29)

## Why

Order #268: two dresses, the customer returned both, Pathao lost one (Zareen) and paid 1,800 compensation.
Faking it as "delivered at 1,800" with a discount puts courier money on the customer's ledger, inflates
sales, and the order-level discount is (correctly) shared across items on return, so the numbers never
matched. The professional treatment: the customer kept nothing; the lost unit is a stock loss; the
courier's compensation is a claim recovery.

## Lost by courier

- `order_items.lost_quantity` is the part of `returned_quantity` the courier lost. It comes off the
  customer's total like any return, but it is **not restocked**. `lost_posted_quantity` tracks how much
  of it is written off in the books.
- Order page: Kept / Returned / **Lost** picker (inventory off) or a "Lost Qty" input (inventory on).
- Save Returns → `RecordOrderReturns` (lost included in returned) →
  - stock: `OwnOrderStock::target()` keeps lost units out; `StockService::releaseOrder()` /
    `restockReturnedItem()` skip them (and take them back out if they were restocked earlier).
  - accounts on: `PostLostStock` → Dr 5910 Stock Loss / Cr 1200 Inventory at cost (delta only).
  - `ReportCourierLoss` opens/updates one "Lost by courier" Issue per order, against the parcel's
    courier, with a suggested claim (the lost units' value after the order discount).

## Issues (reusable)

- `issues` table, polymorphic both ways: `subject` (what it's about) and `party` (who's answerable).
- `HasIssues` trait (Order, Courier so far); an optional `issueParties()` on the subject model lists
  who an issue can be raised against.
- `ReportIssue` is the only path for creating an issue. `ResolveIssue` sets Resolved/Closed and the
  recovered amount; with accounts on it posts Dr the received-into account (the courier's Courier Cash
  by default) / Cr 4100 Other Income (delta only).
- UI: `IssuePanel` (drop on any record's page), `ReportIssueModal` (opened with the
  `open-report-issue` event), Issues list `/admin/issues`, detail `/admin/issues/{id}`, a sidebar link
  with the open count, and an Open Issues card on the dashboard.
- Permissions: `issue.view` (202), `issue.manage` (203), created by the migration.
- `<x-issue-badge>`: red "Issue" while any issue is open, green "Fixed" once resolved, grey "Closed"
  when only dropped ones remain. Counts come from `HasIssues::issueBadgeCounts()` (withCount, so no
  per-row queries). Shown next to the order number on the Orders list and the order page; lost items
  also get a "Lost" tag on the order page.

## Inventory off / Accounts off

- Inventory off: `OwnOrderStock` keeps lost units deducted from the product/variant `stock_quantity`
  (target = kept + lost; on Returned = lost). No ledger, no batches.
- Accounts off: no Stock Loss entry and no recovery entry. The issue still records the claim and the
  recovered amount, and the Resolve modal hides "Received into".

## Order #268, done right

Mark Zareen **Lost** and Flower Grace **Returned** → Save Returns. The order becomes Returned, the
total equals the 80 shipping already paid, and nothing is due. Then open the auto-created issue, set the
claim to 1,800, and when Pathao pays: Resolve → recovered 1,800 → received into Pathao's account.
