# AR Emporium — Accounts panel (Accounts + Master Profile + Commission)
## Implementation spec for Claude Code

**Where:** the existing Laravel admin's **Accounts panel** at `aremporiumbd.com/admin/accounts` (and `/admin/profiles` for Master Profiles). Build inside the current project.

**UI source of truth:** `design/*.dc.html` (22 screens + 2 shared components).
- Build every screen **exactly as designed**: layout, spacing, colors, font sizes, icons, copy (Bangla included), section order and states.
- **Do not redesign, rename, reorder or "improve" anything visible.**

**Functionality:**
- Everything visible must work.
- **If the project lacks a feature, build it.**
- **If an existing feature behaves differently from this spec, update it to match** without breaking other modules (Purchase, Inventory, Sales, Orders).

---

## 0. Ground rules

1. **UI fidelity.**
   - Every inline `style="…"` in the design files is the exact spec.
   - Port it to Tailwind (arbitrary values allowed: `text-[12.5px]`, `rounded-[14px]`, `bg-[#f1f3f7]`) or a small shared CSS file.
   - The result must be pixel-identical at 1440px and behave the same at phone width.
2. **Copy is final.** Keep every label exactly, mixed Bangla/English included.
3. **Sample data → real data.** All names, numbers and rows in the designs are samples (hard-coded or produced in `renderVals()`).
   - Replace them with DB queries.
   - Keep the same shape: columns, sub-lines and color rules.
4. **Inspect the project first.** Reuse what already exists: routes, models, migrations, layout, auth, roles, and the current Accounts / Purchase / Inventory / Sales / Order code. Extend or migrate it; never duplicate tables.
5. **Stack:** Laravel + **Livewire 3** + **Alpine.js** (bundled with Livewire) + **Tailwind CSS**.
   - Timezone `Asia/Dhaka`. Currency BDT with lakh grouping.
   - The week starts on **Saturday**. The financial year is **July–June** (configurable in Settings).
6. Work in small commits. Write feature tests for every rule in §7.

---

## 1. How to read a `.dc.html` design file

Ignore `<script src="./support.js">`, `data-props` and `$preview`; they are design-tool plumbing.

| In the design | Meaning | Implement as |
|---|---|---|
| `<x-dc> … </x-dc>` | Page markup | Blade view |
| `<helmet><style>` | Page-level base CSS | Layout CSS |
| `{{ name }}` | Value from `renderVals()` | `{{ $name }}` from the Livewire component |
| `<sc-for list="{{rows}}" as="r">` | Loop | `@foreach` |
| `<sc-if value="{{flag}}">` | Conditional | `@if` |
| `onClick` / `onChange` + `this.setState` | Interaction | `wire:click` / `wire:model.live` (server state) or Alpine `x-data` (pure UI state) |
| `style="{{x.style}}"` computed in JS | Active/status variants | `@class([...])` with the exact variants defined in `renderVals()` |
| `<dc-import name="Sidebar" active="…">` | Shared component + prop | `<x-accounts.sidebar active="…" />` |
| `<dc-import name="ProfileTabs" active="…">` | Shared top tab bar | `<x-profiles.tabs active="…" />` |
| `href="X.dc.html"` | Link to another screen | `route(...)` (§3) |
| `href="X.dc.html#id"` | Link + anchor | Route + fragment / open the matching panel |

Each `renderVals()` also contains the **logic to port**. Port these exactly:
- filter rules
- colour thresholds, e.g. the credit-limit bar: `>=100% #dc2626`, `>=85% #f59e0b`, else `#10b981`
- running balances
- the EMI schedule generator (`LoansEmi.dc.html`)
- the depreciation calculation (`FixedAssets.dc.html`)
- the commission tier math (`Commission.dc.html`)
- the cash-count totals (`CashBank.dc.html`)

---

## 2. Design tokens

**Fonts and base:**
- **Fonts:** `Plus Jakarta Sans` (Latin) and `Hind Siliguri` (Bangla), weights 400–700. Stack: `'Plus Jakarta Sans','Hind Siliguri',sans-serif`.
- **Base:** page bg `#f1f3f7`, text `#111827`, 14px.

| Token | Value |
|---|---|
| Sidebar | bg `#0f172a`, active item `#1e293b` + white 600, text `#cbd5e1`, muted `#94a3b8`/`#64748b`, divider `#334155` |
| Primary | `#4f46e5`; dark text `#312e81`; link `#4338ca`; light bg `#eef2ff`; border `#c7d2fe` |
| Card | `#fff`, border `1px #e5e7eb`, radius `14px`, padding `16px 18px` |
| Table | row divider `#f0f1f4`; header 11.5px uppercase `#6b7280` letter-spacing .04em; subtotal rows bg `#f8fafc`/`#f9fafb` |
| Input | border `#d1d5db`, radius `9px`, padding `9–10px 12px`, 13–14px |
| Money in / পাবো | `#047857` |
| Money out / দিবো | `#b91c1c` |
| Advance / loan / equity | `#4338ca` |
| Warning / overdue text | `#b45309` |
| Muted / clear | `#6b7280` |
| Info box | bg `#eef2ff`, text `#312e81`, border `#c7d2fe` |
| Warning box | bg `#fffbeb`, text `#78350f`, border `#fde68a` |
| Danger box | bg `#fef2f2`, text `#7f1d1d`, border `#fecaca` |
| Success box | bg `#ecfdf5`, text `#064e3b`, border `#a7f3d0` |

**Chips:**
- Active: `1px #4f46e5`, bg `#4f46e5`, white, 600, radius 20px, padding `6px 12px`, 12.5px.
- Inactive: `1px #e5e7eb`, white bg, `#374151`.

**Period pills:**
- Container: white, border `#e5e7eb`, radius 10px, padding 4px.
- Active pill: `#4f46e5`, white, radius 7px, padding `7px 12px`.

**Role badges** (11.5px/600, radius 6, padding `2px 8px`):

| Role | Background | Text |
|---|---|---|
| Customer | `#d1fae5` | `#065f46` |
| Supplier | `#e0f2fe` | `#075985` |
| Lender | `#e0e7ff` | `#3730a3` |
| Owner | `#fef3c7` | `#92400e` |
| Employee | `#ccfbf1` | `#115e59` |

**Account kind badges:**

| Kind | Background | Text |
|---|---|---|
| Cash | `#fef3c7` | `#92400e` |
| Bank | `#e0f2fe` | `#075985` |
| Mobile banking | `#fce7f3` | `#9d174d` |

**Status badges** (12px/600, radius 6, padding `3px 8px`):

| Status | Background | Text |
|---|---|---|
| Paid / Posted / Ready / On track | `#d1fae5` | `#065f46` |
| Due / Pending / Draft / Has issues / Due soon | `#fef3c7` | `#92400e` |
| Needs approval | `#e0e7ff` | `#3730a3` |
| Upcoming / Scheduled / Reversed | `#f3f4f6` | `#374151` |
| Overdue / Rejected / Late | `#fee2e2` | `#991b1b` |

**Toggle switch (Settings):** 44×26 track (`#4f46e5` on, `#d1d5db` off) with a 20px white knob.

**Money format:**
- Bangladeshi grouping with ৳: `৳1,24,300`, `৳17,75,250`.
- Signed values: `+ ৳500` / `− ৳22,500` (U+2212).
- Use one helper, e.g. `bdt($n, signed:false)`. A reference `money()` is in `reference/previous-livewire-dashboard`.

**Phones and dates:**
- Phones are masked in the mockups (`017••-••4521`) for privacy only. Show real numbers.
- Dates: `12 Oct`, `11 Oct 2026`, plus relative forms ("Today", "2 days ago").
- Bangla weekday names appear in the day headers.

**Icons:** inline stroke SVGs copied from the files. No emoji.

---

## 3. Screens and routes

All routes live under the admin auth middleware. Suggested names are below; match existing names if they already exist.

| Design file | Screen | Route name → URL | Sidebar `active` |
|---|---|---|---|
| `Main.dc.html` | Accounts Dashboard | `admin.accounts.dashboard` → `/admin/accounts` | dashboard |
| `PostingPending.dc.html` | Posting Pending | `admin.accounts.posting` → `/admin/accounts/posting` | posting |
| `Transactions.dc.html` | Transactions (day book) | `admin.accounts.transactions` | transactions |
| `CashBank.dc.html` | Cash & Bank | `admin.accounts.cash-bank` | cash |
| `Receivables.dc.html` | Receivables (Due) | `admin.accounts.receivables` | receivables |
| `Commission.dc.html` | Customer Commission & Rebate | `admin.accounts.commission` | commission |
| `Payables.dc.html` | Payables | `admin.accounts.payables` | payables |
| `LoansEmi.dc.html` | Loans & EMI | `admin.accounts.loans` | loans |
| `FixedAssets.dc.html` | Fixed Assets | `admin.accounts.fixed-assets` | assets |
| `Expenses.dc.html` | Expenses | `admin.accounts.expenses` | expenses |
| `Reports.dc.html` | Reports (+ Daily report, Saved reports) | `admin.accounts.reports` (`?report=daily\|saved\|pl\|bs\|cash…`) | reports |
| `Settings.dc.html` | Accounts Settings | `admin.accounts.settings` (`?section=general\|posting\|numbering\|categories\|users\|sms`) | settings |
| `Profiles.dc.html` | Master Profiles | `admin.profiles.index` → `/admin/profiles` | profiles |
| `Customers.dc.html` | Customers | `admin.profiles.customers` | customers |
| `Suppliers.dc.html` | Suppliers | `admin.profiles.suppliers` | suppliers |
| `Lenders.dc.html` | Lenders | `admin.profiles.lenders` | lenders |
| `Owners.dc.html` | Owners | `admin.profiles.owners` | owners |
| `Employees.dc.html` | Employees | `admin.profiles.employees` | employees |
| `ProfileDetail.dc.html` | Profile details | `admin.profiles.show` → `/admin/profiles/{profile}` | profiles |
| `ProfileForm.dc.html` | New / edit profile | `admin.profiles.create` / `.edit` | profiles |
| `Sidebar.dc.html` | Shared sidebar | — | prop |
| `ProfileTabs.dc.html` | Shared profile tab bar | — | prop |

**Live counts:**
- The sidebar's "All Profiles" child counts, the ProfileTabs counts and the Posting Pending badge are live counts.
- "+ New customer / supplier / lender / owner / employee" opens the profile form with that role pre-checked (`?role=`).

---

## 4. Shared layout

### 4.1 Sidebar (`Sidebar.dc.html`)
One Blade component used on every Accounts/Profiles page. Items appear in this exact order.

**Brand** at the top.

**PROFILES**
- All Profiles (collapsible), with children and counts: Master Profiles, Customers, Suppliers, Lenders, Owners, Employees.

**ACCOUNTS**
- Dashboard
- Transactions
- Posting Pending (amber badge = draft count)
- Cash & Bank
- Receivables (Due)
- **Commission**
- Payables
- Loans & EMI
- Fixed Assets
- Expenses
- Reports
- Settings

**OPERATIONS**
- Purchase, Inventory (existing modules)

**Active state:** the active item gets bg `#1e293b`, white text, weight 600.

**Mobile:** the sidebar stacks above the content. The root is a `flex-wrap` row: sidebar `flex:1 1 248px`, content `flex:999 1 560px; min-width:0`.

### 4.2 Accounts pages top bar
Every Accounts page except the profile pages has a white bar containing:
- page title, plus a breadcrumb `Accounts / …`
- bell button
- "SU" avatar and username

The dashboard version is "Accounts Home / Dashboard / Accounts".

### 4.3 Profile tabs bar (`ProfileTabs.dc.html`)
- Sits at the top of all profile pages.
- Six tabs, each with a count pill.
- The active tab has a 3px `#4f46e5` underline.
- Bell and avatar sit on the right.

---

## 5. Page specs

Port the exact content and behaviour from each file. The notes below give what must be **real**.

### 5.1 Accounts Dashboard — `Main.dc.html`
**Period filter:**
- Pills: Today / Yesterday / This week (Saturday start) / This month / This year / Custom.
- The resolved range shows under the pills.
- The period is kept in the URL (`?period=`).
- All per-period KPIs respect it.

**KPI cards:**
- Cash In, Cash Out, Cash on Hand, Bank & MFS.
- Receivable (with the 60+ overdue figure), Payable (due within 7 days), Loan Outstanding (with support advance), Profit (posted only).

**Inventory section:** stock in, stock out (COGS), total qty / in-transit COD, stock value at average cost + sale value.

**Posting Pending table:** checkboxes, Post (with confirm), Post selected, and "Review all" linking to Posting Pending.

**Master Profiles card:** one row per role. The "Login Profiles" row stays.

**Installments table:** frequency chips with counts; Support column. "+ New EMI rule" goes to `LoansEmi#new-plan`.

**Smaller cards:**
- Due Aging; "View all" goes to Receivables.
- Payables; "View all" goes to Payables.
- Cash & Bank; "Transfer" goes to `CashBank#transfer`.
- Today's transactions; "সব দেখুন" goes to Transactions.

**Alerts:** generated by rules.

### 5.2 Posting Pending — `PostingPending.dc.html`
**Summary cards:** waiting (count + amount + ready now), needs approval, waiting > 48h, rejected, posted today.

**Lock banner:** "Books locked up to …", taken from Settings.

**Status chips:** Waiting to post / Ready / Needs approval / Has issues / Scheduled / Rejected, each with a live count.

**Filters:** type, source (Manual/System), created by.

**Table:** checkboxes. When anything is selected, a bulk bar appears with the count + total, Clear, Reject selected and Post selected.

**Waiting time:** shown on each row; more than 48h shows red.

**Review panel** (opens when a ref is clicked):
- header, party, details, created by
- info/warn/danger note
- journal lines (Dr/Cr, balanced total)
- **"Post করলে যা বদলাবে"**: the balance before → after for each affected party/account, computed live
- **Checks** list: ✓ ok / ! warning / × blocking
- history

**Primary action by status:**

| Status | Primary | Secondary | Reject allowed |
|---|---|---|---|
| ready | Post | Edit | yes |
| approval | Approve & post (approvers only) | Edit | yes |
| issue | "Fix issues to post" (disabled) | Edit | yes |
| scheduled | Post now | Change date | no |
| rejected | Edit & resubmit | Delete | no |

**Reject:** opens a reason textarea (required) with "Send back to maker".

**Posted today:** list with a **Reverse** action. Reversing creates an opposite journal; posted vouchers are never edited.

**Posting rules card:** the same switches as Settings › Posting rules.

### 5.3 Transactions — `Transactions.dc.html`
**Header actions:** + টাকা আসলো (receipt), + টাকা গেলো (payment), + Expense, + Transfer, + Journal. Each opens its voucher form; all are created as **draft**.

**Period:** pills plus the range text, Export and "Print day book".

**Summary** (computed for the period): money in and out (posted, plus the draft amount), net, transfers, entry counts.

**Filters:** type chips, Account, Status, and a "Draft দেখাও" toggle.

**Table:** grouped by day, with a header row per day showing posted in/out totals.
- Draft amounts show at 60% opacity.
- Reversed/Rejected rows are struck through.
- Transfers show grey in both In and Out.

**Detail panel:** journal lines, attachment, Print, Duplicate, Reverse (posted only) or "Open in Posting Pending" (drafts).

### 5.4 Cash & Bank — `CashBank.dc.html`
**Total card:** all-account posted total, the "after drafts" figure, and Cash / Bank / Mobile banking split with a stacked bar.

**Account cards:** kind badge, reconciliation status, balance, today's +/−. Clicking a card selects that account.

**Ledger of the selected account:**
- opening row, rows with running balance, closing row
- a draft strip when the account has drafts

**Right column for cash accounts — Cash count:**
- Denomination inputs (1000, 500, 200, 100, 50, 20, 10, coins). The total is live.
- Shows counted vs book and the difference.
- The button turns into "Record shortage/excess ৳X", which creates an adjustment voucher. "Save cash count" when it matches.

**Right column for bank/MFS accounts — Reconcile:**
- Enter the statement balance; the difference is shown.
- A suggestion offers to match a draft or add a missing entry (e.g. bank interest).
- "Mark reconciled" stores the date (last reconciled).

**Transfer form:**
- Fields: from, to, amount, charge, date, note.
- A live preview shows both balances after the transfer.
- The charge posts to "Bank & MFS charges".
- Buttons: Save as draft / Transfer.

**Header actions:** + New account, Statement import (CSV of bank/bKash statement for matching).

### 5.5 Receivables — `Receivables.dc.html`
**Cards:** customer due (60+ figure), courier COD receivable, advances given (supplier/staff split), collected this month vs target (target stored in Settings).

**Customer aging:** stacked bar plus a legend with amounts and customer counts per bucket.

**Aging table by customer:**
- Columns: buckets 0–30 / 31–60 / 61–90 / 90+, promise/follow-up, last follow-up.
- Bucket chips filter the table.
- Footer shows totals for all customers.

**Actions:** Collect (receipt), Remind (SMS).

**Promise to pay:**
- Stored per customer with date and amount.
- A broken promise (date passed, not paid) shows red.

**Today's follow-up:** generated from the rules (90+ with no response, broken promise, call-again date, promise due). Buttons: Call (`tel:`), SMS, and Write off / Stop credit / Set promise.

**Courier COD settlement:**
- Per courier: delivered-unsettled parcels, amount, oldest date, status.
- "Match settlement" imports or matches the courier payout against parcels and creates the receipt.

**Advances given:** suppliers and staff, showing the monthly deduction.

### 5.6 Customer Commission & Rebate — `Commission.dc.html` (new module)
A yearly commission/rebate on customer sales. **Some invoices or items are excluded** (sold too cheap).

**Tab 1 — Customer progress:**
- Per customer: eligible sales, excluded, current tier, gap to next tier (bar), accrued, status (Earning / Below threshold / On hold).
- Clicking a customer opens the side panel:
  - eligible breakdown: gross − returns − excluded − unpaid = eligible
  - tier ladder with the current tier highlighted
  - accrued and projected year-end (eligible × 365 / days elapsed in the FY)
  - hold note
  - excluded invoices list

**Tab 2 — Schemes & rules:**
- Scheme cards: multiple schemes (yearly rebate, festival commission, agent referral).
- Editor fields:
  - type (Rebate / Commission / Instant discount)
  - basis (net sales ৳ / quantity / gross profit)
  - period (yearly Jul–Jun / half-year / quarter / month / custom)
  - who qualifies (price group, tag, selected customers, all)
- **Tier mode:**
  - "Whole amount": the reached tier's rate applies to the whole eligible amount.
  - "Slab": each band at its own rate.
  - Switching the mode recalculates everything live.
- **Tiers table:** editable rows (from, to, rate), plus "+ Add tier".
- **Exclusion rules** (each a toggle showing the excluded amount):
  - item tag "Clearance"
  - invoice discount > X% (default 15)
  - line price < avg cost × (1 + Y%) (default 5)
  - manual exclusions with a reason
  - free/sample items
- **Eligibility:**
  - only fully paid invoices count
  - hold if the customer has dues older than N days (default 60)
  - returns reverse commission
- **Settlement method:** credit note / deduct from due / cash-bKash / customer chooses.
- **Accounting:** Sales rebate (contra-revenue) or Commission expense.

**Tab 3 — Exclusions:**
- Every excluded invoice/line, with Auto/Manual source and reason.
- "Include back" and "Exclude" both require a reason and are logged.
- Rows whose rule is turned off show struck through.

**Tab 4 — Settlement:**
- Year-end preview per customer: projected tier, amount, method, hold notes.
- "Run settlement" is enabled only after period end and creates the vouchers as drafts.
- "Pay advance commission" covers mid-year payouts.
- Last year's history.

**Header actions:**
- FY selector.
- "Statement to customers" (SMS/PDF of their progress).
- "+ New scheme".

**Accrual:**
- At month end, post (draft) `Dr Sales rebate / Cr Rebate payable` for the month's accrued change.
- Settlement: `Dr Rebate payable / Cr Customer receivable` (credit note), or Cash/bKash.

### 5.7 Payables — `Payables.dc.html`
**Cards:** overdue, this week (to Friday), next week, later (to +30 days).

**Cash plan, next 30 days:**
- payable total vs cash+bank+MFS + expected inflow (COD pending + promised collections), giving the projected balance
- a "Covered / Short" badge
- the "tightest day" sentence

**List:**
- All payables: supplier bills, loan EMIs, salary, recurring bills.
- Chips for timing and source.
- Selecting rows shows a bulk bar: "Pay from [account]", then "Create payments" (drafts).
- Row actions: Pay, Reschedule.
- Footer: shown total.

**Side cards:**
- Supplier aging: not due / ≤7d / overdue.
- Recurring bills: + Add recurring bill (amount, day of month, account, varies flag). Recurring bills auto-create a draft on their day.

### 5.8 Loans & EMI — `LoansEmi.dc.html`
**Cards:** active plans, to pay this month, to receive this month, overdue kisti, support/advance held.

**Plans table:**
- All installment plans: loans taken, credit sales on kisti, supplier kisti, staff advances.
- Columns: direction badge (দিবো/পাবো), rule, progress, kisti, remaining, next (with the support-adjusted amount), status.
- Direction and frequency chips filter it.

**New plan builder (`#new-plan`):**
- **Type:** Loan taken / Credit sale on kisti / Supplier kisti. The party list and labels change with it.
- **Basics:** party, amount, first due date.
- **Frequency:** Monthly / Weekly / Daily / Yearly / Custom days (comma offsets).
- **Kisti count**, unless Custom.
- **Interest:** Flat (% per year on principal over the plan span) / Reducing (EMI formula at the periodic rate) / None.
- **Support rule:**
  - next kisti: extra paid reduces the next kisti
  - principal: reduces the remaining balance
  - advance: kept as advance
- **Late rule:** reminder only / late fee / block new credit.
- **Live preview:**
  - kisti amount, rounded up to ৳10, with the last kisti absorbing the remainder
  - total, interest, count, end date
  - schedule table (first 5 rows + "…" + last)
  - a note on what gets created
- **Create plan:**
  - generates the installments
  - a loan creates a draft receipt voucher (`Dr Bank / Cr Loan`)
  - a credit sale moves the invoice due into the plan

### 5.9 Fixed Assets — `FixedAssets.dc.html`
**Cards:** cost, accumulated depreciation (to the last run), book value, this month's depreciation (not yet run).

**Asset list:**
- Category chips; "Disposed দেখাও" toggle.
- Rows show a depreciated-% bar and book value.
- Clicking an asset opens the detail panel.

**Detail panel:**
- cost, book value, monthly depreciation, remaining life
- method, purchase details, location/assignee, warranty
- book-value-by-year bars
- history
- Edit, Record repair (creates an expense voucher in Repairs), Sell/dispose (gain/loss journal)

**Depreciation:**
- Straight line, months-based, no salvage by default. Per asset: life (years), method.
- "Run October depreciation" creates a draft journal `Dr Depreciation / Cr Accumulated depreciation` for all active assets not fully depreciated.

**Reminders:** service due, in repair, tax token/fitness renewals (date fields on the asset).

**+ Add asset:** optionally links to a purchase/payment voucher.

### 5.10 Expenses — `Expenses.dc.html`
**Cards:** this month's spend (posted + draft), budget usage bar (days elapsed), categories over budget, last month with a 6-month mini trend.

**Category vs budget list:**
- Clicking a category filters the expense list; "Clear filter".
- Over budget shows red, > 85% amber.

**Expense list:** date, description, ref/category/payee, paid from, receipt Attached/Missing, amount, status.

**Quick add:**
- Fields: category, amount, date, paid from (with balance), paid to, note, receipt upload (required above the Settings threshold).
- "Repeat every month" makes it a recurring expense.
- Buttons: Save as draft / Save expense.

**Recurring list:** rent, utilities, internet; depreciation links to Fixed Assets.

**Header:** month selector, "Edit budgets" (Settings › categories).

### 5.11 Reports — `Reports.dc.html`
**Header actions:** "Saved reports · N", and **"Save this report"**.

**Save panel:**
- name
- schedule: daily 9 PM / weekly Sat / monthly 1st / month end / none
- recipients
- delivery: PDF archive, Email, WhatsApp (PDF), SMS summary
- The current filters are saved with it.

**Report navigation:**
- **প্রতিদিন:** Daily report, Saved reports.
- **Financial statements:** P&L, Balance Sheet, Trial Balance, Cash flow.
- **Books:** Cash book, Bank book, Day book, Party ledger.
- **Business:** Sales summary, Stock valuation, Receivable aging, Payable aging, Expense by category, Loan & EMI schedule, Payroll summary.

**Common bar:** "N drafts not included" with a Review link; Period selector; Excel / PDF / Print.

**Daily report (দিনের হিসাব):**
- date picker; Open/Closed state badge
- 5 summary tiles: sales, money in, money out, new dues, stock
- per-account opening / in / out / closing table with totals (the transfer note)
- **Checklist before closing:** drafts pending, cash count difference, MFS/bank reconciliation, receipts missing. Each has a link.
- day note
- "Send to owners"
- **"Close day & save":** stores a snapshot plus PDF; the day becomes Closed; "Reopen day" is permission-gated and creates a new version.
- Previous days list: closed by / auto-closed at 23:59 / short amount, plus PDF.

**Saved reports:**
- Table: name, report + filter, schedule, recipients, last run; Open / Run now / Edit.
- PDF archive counter with "Open archive".
- Scheduled runs use the Laravel scheduler + queue. Delivery uses a notification channel interface (mail, WhatsApp, SMS; default log driver).

**P&L:**
- Current period vs previous, with % of sales.
- Sections: income (sales, returns, net sales, COGS, gross profit); expenses by category; finance cost; net profit.
- Summary tiles: net sales, gross profit + margin, net profit.

**Balance Sheet:**
- Assets: current assets and fixed assets (cost − accumulated depreciation).
- Liabilities and owners' equity: capital, profit this year, drawings.
- A green "balanced" check line. If it doesn't balance, show it red with the difference.

**Cash book:** an account ledger. The other reports must also be built: proper tables with the same styling, export and print.

### 5.12 Settings — `Settings.dc.html`
Left section nav, plus Save changes / Discard.

- **General:** business name, BIN/TIN, financial year (Jul–Jun / Jan–Dec), week start, currency format, timezone, books start date, **lock date**.
- **Posting rules:** switches with amount inputs:
  - owner approval above ৳X
  - attachment required above ৳Y
  - maker-checker
  - lock date enforcement
  - auto-post system entries
  - block editing posted entries
- **Voucher numbering:**
  - prefix + next number per type (RV, PV, JV, EXP, CT, EMI, SAL, OB, SO), with a preview
  - option to reset each FY
- **Expense categories:** monthly budgets; the receipt rule.
- **Users & permissions:**
  - role × permission checkbox matrix (Owner, Accountant, Sales, Inventory)
  - permissions: view, create draft, post, approve, reverse, reports, cash count/reconcile, settings
  - Use the existing roles/permissions package if present; otherwise use Gates/Policies.
  - Logins are granted from Employee profiles.
- **SMS & reminders:**
  - gateway (log / BulkSMSBD / SSL Wireless / custom HTTP), sender ID
  - Bangla templates with variables `{name} {amount} {date} {due} {kisti_no}`
  - internal alerts: voucher > 48h, EMI in 3 days, daily 9 PM summary

### 5.13 Master Profile module
Implement as in the first handoff:

| Screen | File |
|---|---|
| Master Profiles | `Profiles.dc.html` |
| Customers | `Customers.dc.html` |
| Suppliers | `Suppliers.dc.html` |
| Lenders | `Lenders.dc.html` |
| Owners | `Owners.dc.html` |
| Employees | `Employees.dc.html` |
| Profile details | `ProfileDetail.dc.html` |
| Profile form | `ProfileForm.dc.html` |

Key behaviour:
- **One person = one profile.** Phone is unique, with a live duplicate check that offers "add role here".
- **Roles:** Customer, Supplier, Lender, Owner, Employee, each with its own settings.
- **Profile details:** net position plus **Set-off** (contra voucher); tabs for Ledger, Installments, Sales, Purchases, Documents, Notes.
- **Employees:** payroll prep (gross − advance deductions = net), salary advances, login access.
- **Owners:** capital, drawings, profit share %, net equity, monthly drawings bars.
- **Lenders:** loan cards and the 60-day schedule. Data comes from Loans & EMI.

---

## 6. Data model

Adapt to existing tables and create what's missing.

```
profiles, profile_roles(settings json), profile_documents, profile_activities, promises_to_pay(profile_id, date, amount, status)
accounts(kind cash|bank|mfs, number, opening_balance, last_reconciled_at)
vouchers(ref, type, date, status draft|submitted|approved|posted|rejected|reversed|scheduled, scheduled_for,
         profile_id, account_id, amount, narration, source manual|system, source_type/id,
         created_by, approved_by, posted_by, posted_at, rejected_by, reject_reason, reversed_by_id)
voucher_lines(voucher_id, ledger, profile_id, debit, credit)
voucher_attachments, voucher_events(audit trail: created/submitted/approved/rejected/posted/reversed/edited, user, at, note)
ledgers / chart_of_accounts (system ledgers: cash, bank, receivable, payable, loan, capital, drawings, sales, sales_returns,
         sales_rebate, rebate_payable, cogs, inventory, expense:{category}, salary, salary_payable, salary_advance,
         support_advance, depreciation, accumulated_depreciation, fixed_assets, finance_cost, bank_charges, security_deposit, …)
cash_counts(account_id, date, denominations json, counted, book, difference, adjustment_voucher_id, user_id)
reconciliations(account_id, date, statement_balance, book_balance, difference, status, matched json)
transfers → contra vouchers (CT-) with optional charge line
courier_settlements(courier, parcels json, amount, received_at, voucher_id)

loans(profile_id, principal, total_payable, rate_type flat|reducing|none, rate, frequency, installments, start_date,
      custom_offsets json, support_rule next|principal|advance, late_rule, status)
installment_plans(source_type loan|sale|purchase|staff, source_id, profile_id, direction pay|receive, …same rule fields)
installments(plan_id, no, due_date, amount, adjusted_amount, paid_amount, support_amount, status, voucher_id)

fixed_assets(code, name, category, purchase_date, cost, life_years, method, salvage, location, assigned_profile_id,
             warranty_until, status in_use|repair|disposed, disposed_at, disposal_amount, reminders json)
depreciation_runs(month, voucher_id), depreciation_lines(run_id, asset_id, amount)

expense_categories(name, monthly_budget, ledger), recurring_expenses(category_id, amount, varies, day_of_month, account_id, payee, active)
bills / payables schedule (supplier bills from Purchase, recurring bills, salary, EMI → unified payable view)

commission_schemes(name, type, basis, period, starts_on, ends_on, eligible_rule json, tier_mode whole|slab,
                   settlement_method, accounting rebate|expense, status)
commission_tiers(scheme_id, from_amount, to_amount, rate)
commission_exclusion_rules(scheme_id, rule clearance|discount_over|below_cost|manual|sample, value, active)
commission_exclusions(scheme_id, invoice_id, invoice_line_id nullable, source auto|manual, reason, included_back bool, user_id, at)
commission_accruals(scheme_id, profile_id, month, eligible, rate, amount, voucher_id)
commission_settlements(scheme_id, profile_id, fy, amount, method, voucher_id, status)

payroll_runs, payroll_items, employee_advances
day_closings(date, status open|closed|auto, closed_by, closed_at, snapshot json, pdf_path, note, version)
saved_reports(name, report_key, filters json, schedule cron/preset, recipients json, channels json, last_run_at)
report_archives(saved_report_id nullable, report_key, period, pdf_path, created_at)
settings (key/value): fy_start, week_start, lock_date, approval_threshold, attachment_threshold, maker_checker,
         auto_post_system, block_posted_edit, numbering json, collection_target, sms gateway/templates, alerts
stock_movements, products.avg_cost (reuse Inventory)
```

---

## 7. Business rules

1. **One person = one profile.** Phone is unique.
2. **Posting workflow.**
   - Entries move Draft → (approval if above the threshold) → Posted.
   - Only posted entries affect balances, KPIs, reports, profit and commission.
   - Maker-checker: the creator cannot post their own entry (owners exempt, if configured).
   - Nothing dated on or before the lock date can be posted or edited.
   - Posted entries are immutable; corrections are Reverse only.
   - Reject requires a reason and returns the entry to the maker.
   - A scheduled (post-dated) entry auto-posts on its date via the scheduler.
   - Every state change writes `voucher_events`.
3. **Attachment rule:** above the threshold, posting is blocked without an attachment ("Has issues").
4. **Ledger convention:** Dr = they owe us (পাবো), Cr = we owe them (দিবো). Running balances are shown as `৳X Dr/Cr`.
5. **Set-off:** a contra voucher for min(receivable, payable) on the same profile.
6. **Installments.**
   - Schedule generation per frequency, or custom day offsets.
   - Kisti amounts round up to ৳10; the last kisti takes the remainder.
   - **Support payment** (paid > kisti) follows `support_rule`.
   - Status: paid / partial / overdue / due / upcoming.
   - The late rule applies.
7. **Credit limit:** block new credit sales over the limit.
8. **Aging:** by the oldest unpaid invoice; buckets 0–30 / 31–60 / 61–90 / 90+.
9. **Promise to pay:** a promise whose date has passed and is still unpaid is "broken". It drives Today's follow-up.
10. **Courier COD:** delivered but unsettled parcels count as receivable from the courier until the settlement voucher posts.
11. **Inventory.**
    - Weighted average cost; COGS posted on sale.
    - Returns re-enter at avg cost; damage goes to the stock loss expense.
    - Stock in transit is still stock.
12. **Cash count:** a difference creates a cash shortage/excess adjustment voucher (draft).
13. **Reconciliation:** statement vs book. Suggest matches from drafts and unmatched statement lines.
14. **Transfers:** a contra entry. A charge posts to Bank & MFS charges. Transfers are excluded from income and expense.
15. **Depreciation:** straight line by months. A monthly run creates a draft journal. Disposal posts the gain/loss.
16. **Expenses:** budgets per category per month. Recurring expenses auto-create drafts.
17. **Commission/rebate.**
    - **Eligible** = posted, fully paid sales in the period − returns − excluded invoices/lines.
    - **Whole mode:** `eligible × rate(tier reached)`.
    - **Slab mode:** `Σ band × band rate`.
    - **Hold:** if the customer has dues older than N days, accrual is 0 and the amount is shown as "(hold)".
    - **Projection:** `commission(eligible × 365 / days elapsed)`.
    - **Month-end accrual:** a draft journal.
    - **Settlement:** after period end, by the scheme's method.
    - **Exclusion changes** are logged with a reason.
18. **Owners:** drawings are not expenses. Profit share = profit × share%. Equity = capital + share − drawings.
19. **Payroll:** net = gross + bonus − advance deductions. Unpaid net = salary payable.
20. **Daily close.**
    - Closing stores a snapshot and PDF.
    - Open days auto-close at 23:59 (status "auto").
    - Reopening requires permission and creates a new version.
21. **Saved reports:** run on schedule via queue; store the PDF in the archive and deliver via the configured channels.
22. **Dates:** week starts Saturday; FY July–June (settings); timezone Asia/Dhaka.

---

## 8. Interactions to wire

Everything clickable in the designs works.

**Filters, sorting and tabs:**
- Filters, chips, sort, search: Livewire with query string; tables paginate at 25.
- Inner tabs: Commission, Reports, Settings, ProfileDetail.
- Selectable cards and rows: Cash & Bank accounts, Fixed Assets, Commission customers, Posting/Transactions refs.

**Live calculators:**
- cash count
- transfer preview
- EMI builder
- commission tier mode
- exclusion toggles
- expense category filter
- Settings permission matrix and switches

**Modals:** Collect, Pay, Advance, Pay kisti, Add capital, Record drawing, Transfer, Prepare payroll, Set-off, New voucher types, New account, Add asset, Record repair, Dispose, Add recurring bill, New scheme, Exclude invoice, Upload document, Add note, Import, Merge duplicates, Save report.

Build them in the same design language: white panel, radius 14px, header 15–17px bold, inputs as in the forms, primary `#4f46e5`.

---

## 9. Build order

1. Tokens, layout, sidebar and tabs components, helpers (`bdt()`, dates, FY/week).
2. Settings (general, posting rules, numbering, permissions) — the other modules depend on these.
3. Vouchers + lines + events + posting workflow + Posting Pending + Transactions.
4. Accounts (Cash & Bank): ledger, transfer, cash count, reconcile.
5. Master profiles + roles + form + details (ledger, set-off).
6. Receivables (aging, promises, follow-up, courier COD) + Customers; Payables (+ cash plan, recurring bills) + Suppliers.
7. Loans & EMI (plans, schedule engine, support rules) + Lenders + dashboard installments.
8. Expenses (categories, budgets, recurring); Fixed Assets (depreciation runs).
9. Owners, Employees (payroll, advances).
10. Commission & Rebate (schemes, tiers, exclusions, accrual, settlement).
11. Inventory valuation hooks (avg cost, COGS, in transit) + dashboard stock section.
12. Reports (all statements and books, daily report + close day, saved reports + scheduler + archive).
13. Accounts Dashboard wired to real data + alerts.
14. Feature tests for §7; then go through the §10 checklist.

---

## 10. Acceptance checklist

- [ ] Every screen matches its `.dc.html` side by side at 1440px: sections, order, spacing, colors, sizes, copy.
- [ ] At phone width the sidebar stacks, filters and chips wrap, and tables scroll inside their card.
- [ ] No sample data remains; all values come from the DB.
- [ ] All buttons, chips, tabs, links, checkboxes, toggles and calculators work.
- [ ] Only posted entries affect balances, KPIs, reports and commission.
- [ ] The approval threshold, attachment rule, maker-checker and lock date are all enforced.
- [ ] The support payment adjusts the next kisti, the principal or the advance per rule.
- [ ] Commission is correct in both whole and slab modes, with exclusions, the paid-only rule and holds.
- [ ] Depreciation, cash count adjustments, reconciliation and transfers with charges post correctly.
- [ ] The Balance Sheet balances; the P&L ties to posted vouchers.
- [ ] Daily close snapshot/PDF and saved-report schedules run.
- [ ] BD money format, Saturday week, FY Jul–Jun, Asia/Dhaka everywhere.
