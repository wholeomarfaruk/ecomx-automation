# AR Emporium — Accounts + Master Profile module
## Implementation spec for Claude Code

**Source of truth for the UI: `design/*.dc.html`.**
Build the screens exactly as designed: same layout, spacing, colors, font sizes, copy (including the Bangla text), order of sections, and states.
**Do not redesign, rename, reorder, or "improve" any visible element.**
Where a design shows something that has no backend yet (a button, a filter, a number), **build the functionality** so that everything visible actually works.

---

## 0. Ground rules

1. **UI fidelity first.** Every inline `style="…"` in the design files is the exact spec. Convert to Tailwind with arbitrary values where needed (`text-[12.5px]`, `rounded-[14px]`, `px-[18px]`, `bg-[#f1f3f7]`) or a small shared CSS file. Results must look identical.
2. **Copy is final.** Keep every label and heading exactly as written, mixed Bangla/English included (e.g. `আমি পাবো · Receivable (Due)`).
3. **Sample data → real data.** All numbers, names and rows in the designs are sample data, either hard-coded or produced in `renderVals()`. Replace them with database queries. Keep the **same shape**: the same columns, the same sub-lines (e.g. "12 receipts · COD ৳31,400") and the same color rules.
4. **Inspect the existing project before writing code.** This is an existing Laravel admin (`/admin/...`) that already has an Accounts module (Dashboard, Transactions, Cash & Bank, Receivables, Payables, Loans, Fixed Assets, Owner, Expenses, Reports, Settings), plus Purchase and Inventory. Reuse existing models, tables, auth and layout where they exist. Migrate or extend; don't duplicate. Don't break existing pages.
5. **If something is missing, build it.** Use the same design language for screens that aren't designed yet (see §9).
6. Stack: Laravel + **Livewire 3** + **Alpine.js** (bundled with Livewire) + **Tailwind CSS**. Timezone `Asia/Dhaka`. Currency BDT.

---

## 1. How to read a `.dc.html` design file

These are design-tool component files. Ignore `<script src="./support.js">`, the `data-props` attribute and the `$preview` key. They are tooling.

| In the design | Meaning | Implement as |
|---|---|---|
| `<x-dc> … </x-dc>` | The page markup | Blade view markup |
| `<helmet><style>` | Page-level base CSS (body bg, link colors) | Layout CSS |
| `{{ name }}` | Value from `renderVals()` | Blade `{{ $name }}` from the Livewire component |
| `<sc-for list="{{rows}}" as="r">` | Loop | `@foreach ($rows as $r)` |
| `<sc-if value="{{flag}}">` | Conditional | `@if ($flag)` |
| `onClick="{{pick}}"` + `this.setState` | Client interaction | `wire:click` (server state) or Alpine `x-data` (pure UI state) |
| `style="{{c.style}}"` (computed in JS) | Active/inactive or status styling | `@class([...])` with the exact two style variants found in `renderVals()` |
| `<dc-import name="Sidebar" active="customers">` | Shared component with a prop | `<x-admin.sidebar active="customers" />` Blade component |
| `<dc-import name="ProfileTabs" active="…">` | Shared top tabs bar | `<x-profiles.tabs active="…" />` |
| `href="Customers.dc.html"` | Link to another screen | `route(...)` (see §3) |
| `renderVals()` sample arrays | Sample rows and how each row is styled | Query results + the same styling logic |

Each `renderVals()` also documents **filter logic and style rules**, e.g. the credit-limit bar color in `Customers.dc.html`:
`>=100% → #dc2626`, `>=85% → #f59e0b`, else `#10b981`. Port these rules exactly.

---

## 2. Design tokens (use everywhere)

**Fonts.** `Plus Jakarta Sans` for Latin, `Hind Siliguri` for Bangla. Stack: `'Plus Jakarta Sans', 'Hind Siliguri', sans-serif`. Load weights 400/500/600/700 from Google Fonts.

**Base.** Page bg `#f1f3f7`. Body text `#111827`, 14px.

| Token | Value |
|---|---|
| Sidebar bg | `#0f172a`; active item bg `#1e293b`; text `#cbd5e1`; muted `#94a3b8` / `#64748b`; divider `#334155` |
| Brand / primary | `#4f46e5` (buttons, active chips, tab underline), hover text `#312e81`, link `#4338ca` |
| Card | bg `#fff`, border `1px #e5e7eb`, radius `14px`, padding `16px 18px` |
| Table row divider | `#f0f1f4`; header text 11.5px uppercase `#6b7280`, letter-spacing .04em |
| Inputs | border `#d1d5db`, radius `9px`, padding `10px 12px`, 13.5–14px |
| Positive money (পাবো / in) | `#047857` |
| Negative money (দিবো / out) | `#b91c1c` |
| Advance / loan / equity | `#4338ca` |
| Overdue / warning text | `#b45309` |
| Clear / muted | `#6b7280` |
| Owner capital | `#92400e` |

**Chips (filters).** Active: `border 1px #4f46e5; bg #4f46e5; color #fff; weight 600; radius 20px; padding 6px 12px; 12.5px`. Inactive: `border 1px #e5e7eb; bg #fff; color #374151`.

**Period pills (dashboard).** Active: `bg #4f46e5; color #fff; weight 600; radius 7px; padding 7px 12px`. Inactive: transparent, `#374151`. Container: white, border `#e5e7eb`, radius 10px, padding 4px.

**Role badges** (11.5px, weight 600, radius 6px, padding 2px 8px):

| Role | Background | Text |
|---|---|---|
| Customer | `#d1fae5` | `#065f46` |
| Supplier | `#e0f2fe` | `#075985` |
| Lender | `#e0e7ff` | `#3730a3` |
| Owner | `#fef3c7` | `#92400e` |
| Employee | `#ccfbf1` | `#115e59` |

**Status badges** (12px, weight 600, radius 6px, padding 3px 8px):

| Status | Background | Text |
|---|---|---|
| Paid / Posted | `#d1fae5` | `#065f46` |
| Due / Pending / Due soon | `#fef3c7` | `#92400e` |
| Upcoming | `#f3f4f6` | `#374151` |
| Overdue | `#fee2e2` | `#991b1b` |

**Money format.** Bangladeshi grouping with ৳: `৳1,24,300`, `৳17,75,250`. Signed values: `+ ৳500` / `− ৳22,500` (U+2212 minus). Make one helper (e.g. `bdt($amount, signed: false)`).
Reference implementation: `reference/previous-livewire-dashboard/app/Livewire/Accounts/Dashboard.php` → `money()`.

**Phone numbers.** The designs mask phones (`017••-••4521`) only for privacy in the mockup. Show real numbers.

**Dates.** Short form `12 Oct`, full `11 Oct 2026`, relative "Today / Yesterday / 2 days ago" in "Last activity".

**Icons.** Inline stroke SVGs (copy them from the design files). No emoji.

---

## 3. Screens and routes

| Design file | Screen | Suggested route | Sidebar / tab active |
|---|---|---|---|
| `Main.dc.html` | Accounts Dashboard | `admin.accounts.dashboard` → `/admin/accounts` | Accounts › Dashboard |
| `Profiles.dc.html` | Master Profiles (all) | `admin.profiles.index` → `/admin/profiles` | profiles |
| `Customers.dc.html` | Customers | `admin.profiles.customers` → `/admin/profiles/customers` | customers |
| `Suppliers.dc.html` | Suppliers | `admin.profiles.suppliers` | suppliers |
| `Lenders.dc.html` | Lenders | `admin.profiles.lenders` | lenders |
| `Owners.dc.html` | Owners | `admin.profiles.owners` | owners |
| `Employees.dc.html` | Employees | `admin.profiles.employees` | employees |
| `ProfileDetail.dc.html` | Profile details (any profile) | `admin.profiles.show` → `/admin/profiles/{profile}` | profiles |
| `ProfileForm.dc.html` | New / edit profile | `admin.profiles.create`, `admin.profiles.edit` | profiles |
| `Sidebar.dc.html` | Shared sidebar component | — | prop `active` |
| `ProfileTabs.dc.html` | Shared top tab bar for all profile screens | — | prop `active` |

Each profile screen is a **separate page** (separate route + Livewire component). The top tab bar (`ProfileTabs`) switches between them.
Counts in the tabs and sidebar (`242, 199, 24, 3, 2, 8`) are **live counts**: total profiles and profiles per role. The "New customer / supplier / lender / owner / employee" buttons open the profile form with that role pre-checked (`?role=customer`).

---

## 4. Shared layout

### 4.1 Sidebar (`Sidebar.dc.html`, also inline in `Main.dc.html`)

Order, exactly:
1. Brand: indigo square "A" + "AR Emporium".
2. Section label **PROFILES** → collapsible **All Profiles** (chevron) → children with counts: Master Profiles, Customers, Suppliers, Lenders, Owners, Employees. Active child gets bg `#1e293b`, white, 600.
3. Section label **ACCOUNTS** → Dashboard, Transactions, Posting Pending (amber count badge = live number of draft vouchers), Cash & Bank, Receivables (Due), Payables, Loans & EMI, Fixed Assets, Expenses, Reports, Settings. Each has its own icon from the design.
4. Section label **OPERATIONS** → Purchase, Inventory (existing modules).

Make it one Blade component used by every admin page. On mobile the sidebar stacks above the content: the root is a `flex-wrap` row, sidebar `flex: 1 1 248px`, content `flex: 999 1 560px; min-width: 0`.

### 4.2 Dashboard header (`Main.dc.html`)
White bar: "Accounts Home", breadcrumb "Dashboard / Accounts", bell button, avatar "SU" + username.

### 4.3 Profile tabs bar (`ProfileTabs.dc.html`)
White bar at the very top of every profile screen. Tabs: Master Profiles, Customers, Suppliers, Lenders, Owners, Employees, each with a count pill. Active tab: 3px `#4f46e5` bottom border, text `#312e81`, weight 700, count pill `#e0e7ff/#3730a3`. Right side: bell + avatar. The tabs scroll horizontally on small screens.

---

## 5. Page specs

### 5.1 Accounts Dashboard — `Main.dc.html`

**Period filter** (top right): Today, Yesterday, This week, This month, This year, Custom.
- Below the pills, show the resolved date range (`11 Oct 2026`, `04 Oct – 11 Oct 2026`).
- Custom shows From/To date inputs + Apply.
- **The week starts on Saturday.**
- Keep the period in the URL query (`?period=yesterday`).
- Every money KPI that is "per period" (Cash In/Out, Stock In/Out, Profit, transactions list) must respect it. The card labels in the design read "আজকে …"; keep the text as-is.

**KPI row 1:**

| Card | Value | Sub-line |
|---|---|---|
| Cash In | Sum of posted receipts in the period | Receipt count + COD part |
| Cash Out | Sum of posted payments in the period | Payment count + EMI count |
| Cash on Hand | Cash account balance | — |
| Bank & mobile banking | Sum of bank + bKash/Nagad account balances | Counts per type |

**KPI row 2:**

| Card | Value | Sub-line |
|---|---|---|
| Receivable (Due) | Total receivable | Overdue amount (60+ days) |
| Payable | Total payable | Due within 7 days |
| Loan Outstanding | Total loan outstanding | Lender count + total support advance |
| Profit | Profit for the period, **posted entries only** | — |

**স্টক · Inventory section** (4 cells):
- **Stock In:** today's qty in, at cost, split purchase/return.
- **Stock Out:** qty out, COGS, split sold/damaged.
- **Total Stock:** qty, product count, and qty in transit (COD parcels not yet delivered).
- **Stock Value:** at weighted average cost, plus sale value (qty × selling price).
- The "Stock ledger" link goes to the inventory movement list.

**Posting Pending table:**
- Lists draft vouchers: ref, type, details, party, amount, created by.
- Header shows count + total.
- Each row has a **Post** action with a confirm step.
- **Post selected** uses checkboxes, which you must add exactly in the existing table style.
- "Review all" goes to the Posting Pending page.

**Master Profiles card:**
- One row per role: count, sub-line, amount.
- Rows link to the role pages.
- "+ New profile" goes to the form.
- Keep the "Login Profiles" row as designed. It shows users with login: count and how many can post vouchers.

**কিস্তি ও EMI · Installments table:**
- Lists installments due around the period, across customer kisti plans, loans to lenders, supplier kisti and yearly items.
- Columns: Party, Rule, Kisti n/N, Due, EMI, Paid, Support, Status.
- Frequency chips (All, Monthly, Weekly, Daily, Yearly, Custom) with live counts filter the table.
- "+ New EMI rule" opens the loan/plan form (§9).

**Due Aging:** receivable buckets 0–30 / 31–60 / 61–90 / 90+ days, each with a bar whose width = share of the total.

**Payables:** the next due items. Overdue lines are red.

**Cash & Bank:** every cash/bank/MFS account with its balance. "Transfer" opens an account-transfer voucher form.

**আজকের লেনদেন:** time, title, account, Posted/Pending badge, signed amount.

**Alerts** (red/amber/green boxes), generated by rules:
- installment overdue
- N vouchers waiting to post
- loan EMI due in ≤ 7 days (with balance check)
- support advance carried to next kisti

### 5.2 Master Profiles — `Profiles.dc.html`
- **Header actions:**
  - **Import (Excel):** column mapping; dedupe by phone.
  - **Export:** CSV/XLSX of the current filter.
  - **+ New profile.**
- **Summary cards:**
  - **Total profiles:** count + "+N this month" + employee count.
  - **Receivable:** total + number of profiles.
  - **Payable + Loan:** total, split suppliers / lenders.
  - **Advance:** total, split given / received.
- **Duplicate banner:** count of profiles sharing a phone number. "Review & merge" opens a merge screen: pick the primary, move roles, ledgers and documents, then soft-delete the rest.
- **Filters:**
  - search (name, phone, code, NID)
  - Type (Individual/Business)
  - District
  - Status (Active/Inactive/All)
  - Role chips (All, Customer, Supplier, Lender, Owner, Employee, Multi-role)
  - Balance chips (All, পাবো, দিবো, Advance, Clear)
- **Table:**
  - Columns: checkbox, avatar initials + name + code + type, phone, role badges, area, balance + label, last activity, View.
  - Balance label per profile: `পাবো` (due) / `দিবো` / `Net দিবো` (multi-role net) / `Loan বাকি` / `Capital` / `Advance নেওয়া` / `Salary advance` / `বেতন বাকি` / `Clear`, with the color rules from `renderVals()`.
  - Paginated: "Showing 1–N of total" + Previous/Next.
  - Empty state text: `এই filter-এ কোনো profile নেই।`

### 5.3 Customers — `Customers.dc.html`
- **Actions:**
  - **Due reminder SMS:** bulk SMS to selected customers, or all with due if none selected.
  - **Collect:** receipt voucher modal.
  - **+ New customer.**
- **Cards:**
  - **Total customers:** + count with due + count on kisti.
  - **Total due:** + collected this month.
  - **Overdue 60+:** amount + customer count.
  - **Over credit limit:** count. New credit sales to these customers must be blocked.
  - **Advance received.**
- **Filters:** search, District, Sort (Highest due / Oldest due / Recent activity / Name A–Z), "Kisti plan আছে" checkbox, status chips (All, Due, Overdue 60+, Advance, Clear) with counts.
- **Columns:** customer + code + phone, area, total purchase + order count, due amount + age of oldest due, credit-limit bar (rule in §1), kisti plan badge, last payment amount + date, actions Collect · SMS · View.

### 5.4 Suppliers — `Suppliers.dc.html`
- **Actions:** Pay (payment voucher), Advance দিন (advance payment), + New supplier.
- **Cards:** total suppliers (+ how many are also customers), total payable, due in 7 days (+ overdue part), advance given, purchased this month.
- **Filters:** search, Category, Terms; status chips (All, Payable, Overdue, Advance given, Clear).
- **Columns:** supplier + "Also customer" badge when the profile has the Customer role, category, terms badge, purchase this year, balance + label, next due date + relative note, last purchase, Pay · View.

### 5.5 Lenders — `Lenders.dc.html`
- **Actions:** Pay kisti, + New loan, + New lender.
- **Cards:** borrowed, outstanding, this month's kisti (paid / upcoming split), support advance.
- **One card per loan:**
  - name, code, note, type badge (Bank / Business / Owner loan)
  - rule badge (Monthly · 12 kisti, etc.), rate badge
  - progress bar of the paid share
  - 4 numbers: নেওয়া, মোট ফেরত, দেওয়া হয়েছে, বাকি
  - next kisti box: date + kisti no, amount after support adjustment, note
  - buttons: Pay kisti, Schedule, Ledger
- **Next 60 days table:** date, lender, kisti, EMI, support adjust (`+ ৳1,500 extra` / `− ৳1,500`), payable, status.

### 5.6 Owners — `Owners.dc.html`
- Note under the title: drawings are not expenses.
- **Actions:** Add capital, Drawing, + New owner.
- **Cards:** total capital, profit for the year (posted), drawings for the year (+ this month), net equity (= capital + profit share − drawings).
- **One card per owner:**
  - share %
  - "Lender · ৳X বাকি" badge if the owner is also a lender
  - capital, profit share, drawings, net equity
  - monthly drawings mini bar chart (Jan–current month; bar height ∝ amount; zero months grey)
  - buttons: Add capital, Record drawing, Statement
- **Recent table:** capital-in / drawing / loan-repaid entries with type badge colors as in the design.

### 5.7 Employees — `Employees.dc.html`
- **Actions:** Advance দিন (salary advance), Pay salary, + New employee.
- **Cards:** headcount (+ how many have login), monthly payroll, salary payable (month + employee count), advances outstanding.
- **Payroll banner:** "October 2026 payroll — Gross − advance deduction = net · due date", with a **Prepare payroll** button (§9).
- **Filters:** search, Department; chips (All, Salary বাকি, Advance আছে, Login access, Inactive).
- **Columns:** employee + code + phone, designation + department, joined, salary, advance + monthly deduction, last month status (Paid / "৳X বাকি"), paid via (Cash/bKash/Bank/Nagad), login role badge or "No access", Pay · View.
- **Payroll history:** month, gross, bonus (e.g. Eid), advance deducted, net paid, status.

### 5.8 Profile details — `ProfileDetail.dc.html`
- **Back link:** "‹ Master Profiles".
- **Header:** initials tile, name, role badges, Active badge, code · type · contact person, phone, address, "Since" date. Actions: Statement (PDF / SMS), Edit, Pay, Collect.
- **Position cards:** one per money role the profile has (Customer পাবো with credit-limit usage; Supplier দিবো with due date/terms).
- **Net position card:** net = payable − receivable, labelled দিবো/পাবো, with a **Set-off ৳X** button that creates a contra voucher for `min(receivable, payable)`.
- **Tabs:** Ledger (default), Installments, Sales, Purchases, Documents, Notes & activity.
  - **Ledger:**
    - From/To filters + Role filter, Print, Send via SMS/WhatsApp.
    - Columns: Date, Voucher (link), Particulars + sub-line, Debit, Credit, running Balance with Dr/Cr suffix.
    - First row: opening balance. Footer: closing totals.
    - Legend line: Dr = they owe us (পাবো), Cr = we owe them (দিবো). Dr balance green, Cr red.
  - **Installments:** plan header (invoice, n × amount, start, support rule) + grid of kisti boxes (paid = green, next = amber, later = white) showing the adjusted amount when support was paid.
  - **Sales, Purchases:** simple tables as designed.
  - **Documents:** list + upload.
  - **Notes & activity:** add note textarea + timeline (payments, SMS sent, setting changes, role added).
- **Right column:** Details (alt phone, email, address, TIN, BIN, trade license, opening balance), Role settings cards per role, "+ Add role", Tags.

### 5.9 New / Edit profile — `ProfileForm.dc.html`
- **Type toggle:** Individual / Business. Business shows "Business name" + "Contact person"; Individual shows "Full name".
- **Mobile (required):**
  - Validate as a BD mobile number.
  - **Live duplicate check** on phone (and alt phone).
  - On a match, show the amber box exactly as designed, with "Open profile" and "ওখানে role যোগ করুন". The second link adds the role to the existing profile instead of creating a new one.
- **Role cards (multi-select):** Customer, Supplier, Lender, Owner, Employee. Checking one reveals its settings panel:
  - **Customer:** credit limit, payment terms, price group, "কিস্তিতে বিক্রি করা যাবে".
  - **Supplier:** category, payment terms, bKash/bank no.
  - **Lender:** type, plus the info text. The loan itself is created in Loans & EMI.
  - **Owner:** profit share %, opening capital.
  - **Employee:** designation, department, joining date, monthly salary, paid via, account no., "Software login দিন" (creates a linked user account with a role).
- **Opening balance:** amount + আমি পাবো / আমি দিবো toggle + as-of date. Posts an opening-balance voucher.
- **Address:** Division → District → Upazila/Thana (cascading; seed BD geo data) + address line.
- **Identity & documents:** NID (10/17 digits), TIN (12), BIN, trade license, multi-file upload.
- **Tags & notes:** Active checkbox.
- **Footer:** Cancel, Save & add another, Save profile.
- Auto code `P-0001…` shown in the subtitle.

---

## 6. Data model (adapt to existing tables; create what's missing)

```
profiles            id, code (P-0001 unique), type (individual|business), name, contact_person,
                    phone (unique, normalized 01XXXXXXXXX), alt_phone, email,
                    division_id, district_id, upazila_id, address,
                    nid, tin, bin, trade_license, tags (json), notes, is_active, timestamps, soft deletes
profile_roles       id, profile_id, role (customer|supplier|lender|owner|employee), settings (json), timestamps
                    unique(profile_id, role)
                    settings examples:
                      customer: credit_limit, terms_days, price_group, allow_kisti
                      supplier: category, terms (cash|7|15|30|kisti), payout_account
                      lender:   lender_type (bank|business|personal)
                      owner:    share_percent
                      employee: designation, department, joined_on, salary, pay_via, pay_account, user_id
profile_documents   id, profile_id, title, path, mime, expires_on
profile_activities  id, profile_id, user_id, type, text, meta (json), created_at

accounts            id, name, kind (cash|bank|mfs), number, opening_balance            (Cash & Bank)
vouchers            id, ref (RV-/PV-/JV-/EXP-/EMI-/OB-/CT-), type (receipt|payment|journal|expense|
                    emi|opening|contra|capital|drawing|salary|advance), date, status (draft|posted),
                    profile_id, profile_role, account_id, amount, narration, created_by, posted_by, posted_at
voucher_lines       id, voucher_id, ledger (cash|bank|receivable|payable|loan|capital|drawings|sales|
                    cogs|inventory|expense|salary|salary_advance|support_advance|…), profile_id, debit, credit

loans               id, profile_id (lender), principal, total_payable, rate_type (flat|reducing|none),
                    rate, frequency (monthly|weekly|daily|yearly|custom), installments, start_date,
                    custom_offsets (json, e.g. [15,45,90] days), support_rule (next_kisti|principal), status
installment_plans   id, source_type (loan|sale|purchase|other), source_id, profile_id, frequency,
                    count, amount, start_date, custom_offsets, support_rule
installments        id, plan_id, no, due_date, amount, adjusted_amount, paid_amount, support_amount,
                    status (upcoming|due|paid|partial|overdue), paid_at, voucher_id

payroll_runs        id, month, gross, bonus, advance_deducted, net, status, due_on
payroll_items       id, run_id, profile_id, salary, bonus, advance_deduction, net, paid_amount, status, voucher_id
employee_advances   id, profile_id, amount, monthly_deduction, balance, given_on, voucher_id

stock_movements     id, product_id, type (purchase|sale|return|damage|adjust|transit_out|transit_return),
                    qty, unit_cost, ref_type, ref_id, created_at
products            … + avg_cost, stock_qty (reuse the existing products table)
```

Profile balance = the sum of that profile's lines in `receivable/payable/loan/salary/advance` ledgers (Dr − Cr), from posted vouchers only. Cache it if needed.

---

## 7. Business rules

1. **One person = one profile.** Phone is unique. Creating with an existing phone must offer to add the role instead.
2. **Posting.** New vouchers are `draft`. Only `posted` vouchers count in balances, profit, KPIs and reports. Posting is permission-gated (users whose role allows it). Posted vouchers are immutable: correct them with a reversing journal.
3. **Ledger convention.** Dr = they owe us (পাবো), Cr = we owe them (দিবো). Running balance shown as `৳X Dr` / `৳X Cr`.
4. **Multi-role net position.** Net = (payable + loan + salary due) − (receivable). **Set-off** creates a contra voucher (`CT-`) moving `min()` between receivable and payable for that profile.
5. **Installments / EMI.**
   - Frequencies: monthly, weekly, daily, yearly, custom (explicit day offsets). Generate the schedule on plan creation.
   - **Support payment.** If a payment is above the kisti amount (e.g. EMI ৳21,000, paid ৳22,500), the extra (৳1,500) is stored as `support_amount`. With `support_rule = next_kisti`, it reduces the next kisti's `adjusted_amount` (৳19,500). With `principal`, it reduces the outstanding principal.
   - Show support as its own column everywhere, as designed.
   - Status: `paid` if paid ≥ adjusted amount; `partial`; `overdue` if past due and unpaid; `due` if due within the period; else `upcoming`.
6. **Credit limit.** Block new credit sales when (due + new amount) > limit. Usage % = due / limit.
7. **Aging.** Age = days since the oldest unpaid invoice. Buckets 0–30 / 31–60 / 61–90 / 90+. "Overdue 60+" = sum of the 61–90 and 90+ buckets.
8. **Inventory valuation: weighted average cost.**
   - On purchase post: `new_avg = (old_qty × old_avg + in_qty × in_cost) / (old_qty + in_qty)`. Landed cost (transport, labour) is added to the cost.
   - On sale: Dr COGS / Cr Inventory at `avg_cost`.
   - Customer return of good stock: back in at `avg_cost`. Damage: Stock Loss expense.
   - COD parcels with the courier = "in transit": not yet sold, still stock.
   - Stock value = Σ qty × avg_cost. Sale value = Σ qty × selling price.
9. **Owners.**
   - Capital in: Dr Cash/Bank, Cr Capital.
   - Drawing: Dr Drawings, Cr Cash/Bank. **Not an expense.**
   - Profit share = profit × share%. Net equity = capital + profit share − drawings.
10. **Employees / payroll.**
    - Prepare payroll per month: gross = Σ salary, + bonus, − advance deductions (each advance has a monthly deduction until its balance is 0) = net.
    - Paying creates salary payment vouchers. Unpaid net = salary payable.
    - Advance: Dr Salary Advance, Cr Cash.
11. **Opening balance** from the profile form posts an `OB-` voucher dated "as of".
12. **Week starts Saturday.** "This week" = Saturday 00:00 → now (Asia/Dhaka).
13. **SMS reminders / statements.** Implement behind a driver interface (log driver by default) so a BD SMS gateway can be plugged in. The statement PDF uses the ledger tab content.

---

## 8. Interactions to wire

Everything that is clickable in the designs must work:

- **Filters, chips, sort, search:** Livewire, with query-string persistence. Paginate tables at 25 rows.
- **Period pills + custom range:** on the dashboard.
- **Tab switching:**
  - profile tabs → separate pages
  - ProfileDetail inner tabs → Livewire/Alpine state
- **Checkbox selection** and bulk actions (Post selected, Due reminder SMS, Export selected).
- **Every action button opens a modal or page:**
  - Collect: receipt voucher
  - Pay: payment voucher
  - Advance: advance voucher
  - Pay kisti: installment payment with the support field
  - Add capital
  - Record drawing
  - Transfer: between accounts
  - Prepare payroll
  - Set-off
  - New loan
  - New EMI rule
  - Upload document
  - Add note
  - Import
  - Merge duplicates
- **Modal styling:** build modals in the same language: white, radius 14px, header 15px bold, inputs as in the form, primary button `#4f46e5`.

---

## 9. Not designed yet: build in the same design language

Use the same card, table, chip, badge and input styles. Copy the page skeleton from any profile page (sidebar + white top bar + `24px 28px` content padding + H1 24px/700 + gray 13px subtitle + right-aligned actions).

- **Transactions:** voucher list with filters (type, status, account, date, profile).
- **Posting Pending:** full page version of the dashboard table, with review drawer and bulk post.
- **Cash & Bank:** account list, account ledger, transfer.
- **Receivables (Due)** and **Payables:** aging tables (can reuse the Customers/Suppliers queries).
- **Loans & EMI:** loan list + **New loan / EMI rule form**:
  - lender, principal, total payable or rate (flat/reducing/none)
  - frequency chips (Monthly/Weekly/Daily/Yearly/Custom), number of kisti, start date, custom offsets
  - support rule (next kisti / principal)
  - schedule preview table
- **Fixed Assets** (list + purchase + depreciation optional), **Expenses** (categories + expense vouchers), **Reports** (Profit & Loss, Balance Sheet, Cash book, Party ledger, Stock valuation, Aging), **Settings** (voucher prefixes, posting permissions, SMS driver, opening date).
- **Payroll run** screen (from "Prepare payroll").
- **Merge duplicates** screen.
- **Import profiles** screen (upload → map columns → preview → import).

---

## 10. Suggested build order

1. Tokens, layout, sidebar component, profile tabs component, money/date helpers.
2. `profiles` + `profile_roles` + form (§5.9) + Master Profiles list (§5.2) + Profile details (§5.8, ledger reading from vouchers).
3. Vouchers + posting workflow + accounts (Cash & Bank) + Posting Pending.
4. Customers, Suppliers pages + Collect/Pay modals + aging + credit limit.
5. Loans & installments (schedule generator, support rule) + Lenders page + dashboard installments table.
6. Owners (capital/drawings) + Employees (advances, payroll).
7. Inventory valuation (avg cost, COGS, in transit) + dashboard stock section.
8. Accounts Dashboard (§5.1) wired to real data, alerts.
9. Remaining §9 screens, SMS/PDF, import, merge.
10. Feature tests for every rule in §7 (especially posting, support adjustment, avg cost, set-off, payroll deductions).

---

## 11. Acceptance checklist

- [ ] Each designed screen, at 1440px wide, matches its `.dc.html` side by side: same sections, order, spacing, colors, font sizes and copy.
- [ ] At phone width the sidebar stacks, filters wrap, and tables scroll horizontally inside their card.
- [ ] No sample data left. All counts, sums and badges come from the database.
- [ ] Every button, chip, tab, link and checkbox in the designs works.
- [ ] Only posted vouchers affect balances, KPIs and profit.
- [ ] Support payment reduces the next kisti (or principal) per rule; shown in the Support column.
- [ ] Duplicate phone is blocked, with the "add role" path working.
- [ ] Weighted average cost, COGS and stock value are correct after purchase, sale, return and damage.
- [ ] Money always uses BD grouping with ৳; the week starts Saturday; timezone Asia/Dhaka.
