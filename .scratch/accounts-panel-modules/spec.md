# Accounts Panel — remaining modules (handoff 2)

Status: in-progress

Source of truth: `storage/ar-emporium-accounts-handoff/ar-emporium-handoff/design/*.dc.html`
Spec: `.../IMPLEMENTATION_SPEC.md`. Profile screens are unchanged from handoff 1 (already built);
`Main` (dashboard links) and `Sidebar` (new items + active states) changed.

## Decisions (conflicts with the spec, resolved)

| Spec says | Project reality | Decision |
|---|---|---|
| Routes under `/admin/accounts`, `/admin/profiles` | The Accounts Panel lives at `/accounts` (own panel, own users); `/admin/accounts/*` are older admin pages | New screens go in the panel: `/accounts/...`, names `accounts.*`. Old admin pages stay untouched. |
| Sidebar has a separate PROFILES section | Owner asked: All Profiles right after Dashboard, no separate section | Keep owner's order; add **Commission** and point every item at the new panel pages, with active states. |
| Profile pages / Accounts pages use a design-specific top bar | Owner asked: one shared Accounts layout everywhere | Keep `layouts.accounts.app`; page title + `Accounts / …` breadcrumb render at the top of each page body. |
| New `vouchers` / `voucher_lines` tables | `journal_entries` + `journal_entry_lines` already carry every posting (Purchase, Sales, Orders, COD…) | **Extend `journal_entries`**: voucher ref + type, source (manual/system), approval / reject / schedule fields, cached amount + party + money account; add `voucher_events` and `voucher_attachments`. `status` widened to draft · posted · void · rejected · scheduled. |
| Ledgers list | Chart of accounts already seeded by code | Reuse; add missing system ledgers by migration (sales rebate, rebate payable, bank & MFS charges, cash short/excess, repairs, stock loss…). |
| PDF for daily close / saved reports | No PDF library installed; `vendor/` is not in git | Archive stores a self-contained HTML snapshot served as a print-ready page ("Save as PDF"). A real PDF engine (dompdf) can be added later as one swap in `ReportArchive`. |

## Build order (spec §9) → files

1. **Foundation** — `Support/Bdt` (+ BD weekday names), `Support/AccountsPeriod` (Saturday week, July FY), sidebar v2, page-head with breadcrumb, shared UI bits (status badge, toggle, side panel).
2. **Settings** (`Accounts\Settings`) — general, posting rules, numbering, expense categories + budgets, users & permissions (spatie), SMS & reminders. Store: `Setting` group `accounts`.
3. **Vouchers + posting workflow** — migration on `journal_entries`, `VoucherEvent`, `VoucherAttachment`, `Services\Accounts\Vouchers` (create draft, number, submit, approve, post, reject, reverse, schedule, checks). Screens: **Posting Pending**, **Transactions** (+ voucher forms: receipt, payment, expense, transfer, journal).
4. **Cash & Bank** — accounts, ledger, cash count (`cash_counts`), reconcile (`bank_reconciliations` reuse), transfer with charge, new account, statement import.
5. **Receivables** — aging, promises to pay (`promises_to_pay`), today's follow-up, courier COD settlement (reuse `courier_cod_settlements`), advances given.
6. **Payables** — unified payable schedule (bills, EMI, salary, recurring), cash plan, recurring bills.
7. **Loans & EMI** — all plans (loans + kisti plans), new plan builder (round to ৳10, support rule incl. advance, late rule).
8. **Expenses** (categories, budgets, recurring) + **Fixed Assets** (depreciation runs as drafts, repair, dispose, reminders).
9. **Commission & Rebate** — schemes, tiers (whole / slab), exclusion rules + exclusions, accrual, settlement.
10. **Reports** — P&L, BS, TB, cash flow, books, business reports, daily report + close day, saved reports + scheduler + archive.
11. **Dashboard** — new links, alerts.
12. Scheduler: scheduled vouchers, recurring drafts, auto day-close 23:59, saved reports, month-end commission accrual.
13. Feature tests for §7.

## Rules enforced in one place (`Vouchers` service)
Lock date · maker-checker · approval threshold · attachment threshold · posted immutable (reverse only) ·
reject needs a reason · scheduled auto-post · every change writes `voucher_events` · posting a receipt / payment
applies it to open invoices / bills oldest-first.
