# Accounts Panel — Master Profiles module

Status: implemented — feature tests written (tests/Feature/Accounts/MasterProfilesTest.php), not yet run (needs MySQL)

Source of truth for UI: `storage/accountspanel-handoff/design/*.dc.html` (Profiles, Customers,
Suppliers, Lenders, Owners, Employees, ProfileDetail, ProfileForm, Sidebar, ProfileTabs).
Spec: `storage/accountspanel-handoff/IMPLEMENTATION_SPEC.md` §4–§8 (profile parts).

## 1. What already exists (reuse, don't duplicate)

| Need | Existing piece | Decision |
|---|---|---|
| Profile identity | `master_profiles` (+ `customers.master_profile_id`, `suppliers.master_profile_id`, `users.master_profile_id`) | **Reuse** as the spec's `profiles` table; add missing columns. |
| Vouchers / ledger | `journal_entries` + `journal_entry_lines` (subledger morph), `PostJournalEntry` | **Reuse.** No new `vouchers` table. Profile ledger = lines whose subledger is one of the profile's Customer/Supplier rows, the profile itself, or one of its loan accounts. |
| Receipt / payment / advance | `PostCustomerPayment`, `PostSupplierPayment`, `PostSupplierAdvance` | Reuse as-is from Collect / Pay / Advance modals. |
| Loans | `loans`, `loan_repayment_schedules`, `loan_repayments`, `PostLoanReceived`, `PostLoanRepayment` | Extend: link to profile, frequency/kisti rules, support payment. |
| Owner capital / drawings | `PostOwnerInvestment`, `PostOwnerWithdrawal` (accounts 3000 / 3100) | Extend with an optional owner subledger so capital & drawings are per owner. |
| Geo (Division → District → Thana) | `states` → `cities` → `ps` | Reuse for cascading address. |
| SMS | `App\Sms\SmsManager` (driver based) | Reuse for due reminders / statements; logs when no gateway is active. |
| Panel shell | `layouts/accounts/app.blade.php` | Sidebar replaced by the design's Sidebar; new profile layout with the ProfileTabs bar. |

## 2. Data model changes

1. `master_profiles` + `code` (P-0001, unique), `contact_person`, `alt_phone`, `state_id`, `city_id`,
   `ps_id`, `address`, `trade_license`, `tags` (json). NID / TIN / BIN map to `national_id` / `tax_number` /
   `vat_number`; type `organization` = "Business"; `status` active/inactive.
2. `master_profile_roles` (profile, role, settings json, unique pair). Backfilled from customers/suppliers;
   Customer/Supplier `created` hooks keep it in sync for storefront / POS / purchase flows.
3. `master_profile_documents`, `master_profile_activities`.
4. `loans` + `master_profile_id`, `lender_type`, `total_payable`, `rate_type`, `frequency`, `installments`,
   `custom_offsets`, `support_rule`, `note`. `loan_repayment_schedules` + `installment_no`, `adjusted_amount`,
   `paid_amount`, `support_amount`.
5. `installment_plans` + `installments` for customer / supplier kisti.
6. `employee_advances`, `payroll_runs`, `payroll_items`.
7. New ledger accounts: `1160 Salary Advance` (asset), `2400 Salary Payable` (liability).

## 3. Screens → files

| Design | Route (`/accounts/...`) | Livewire component |
|---|---|---|
| Profiles | `profiles` → `accounts.profiles.index` | `Accounts\Profiles\ProfileIndex` |
| Customers | `profiles/customers` | `Accounts\Profiles\Customers` |
| Suppliers | `profiles/suppliers` | `Accounts\Profiles\Suppliers` |
| Lenders | `profiles/lenders` | `Accounts\Profiles\Lenders` |
| Owners | `profiles/owners` | `Accounts\Profiles\Owners` |
| Employees | `profiles/employees` | `Accounts\Profiles\Employees` |
| ProfileDetail | `profiles/{profile}` | `Accounts\Profiles\ProfileShow` |
| ProfileForm | `profiles/create`, `profiles/{profile}/edit` | `Accounts\Profiles\ProfileForm` |
| (not designed) | `profiles/merge`, `profiles/import`, `profiles/payroll/{month}` | Merge, Import, PayrollRun — same design language |

Shared Blade: `components/accounts/sidebar`, `components/accounts/profile-tabs`, `layouts/accounts/profiles`.
Shared PHP: `App\Support\Bdt` (৳ BD grouping), `App\Services\Profiles\ProfileBalances` (all per-profile sums
in one SQL pass), `ProfileCounts`, `InstallmentScheduler`, payroll / kisti / set-off actions.

## 4. Rules implemented

- Phone is unique per profile (normalised `01XXXXXXXXX`); duplicate → amber box with "Open profile" /
  "ওখানে role যোগ করুন".
- Ledger: Dr = পাবো (green), Cr = দিবো (red), running balance, opening row, closing totals. Posted entries only.
- Net position = payable + loan + salary due − receivable; **Set-off** posts a contra entry for `min()`.
- Kisti: monthly / weekly / daily / yearly / custom offsets; overpayment stored as support → reduces next
  kisti (`next_kisti`) or principal (`principal`).
- Credit-limit bar: ≥100% red, ≥85% amber, else green. Aging from the oldest open invoice.
- Owners: drawings are equity, not expense; profit share = posted profit × share %.
- Payroll: gross + bonus − advance deductions = net; paying clears salary payable.
- Opening balance from the form posts an opening-balance entry dated "as of".
- Vouchers made from these pages post immediately (same as every existing Accounts action today).

## 5. Out of scope here (flagged, not silently dropped)

- Blocking credit sales over the limit inside POS / order flows (shown + counted on Customers; enforcement
  in sales flows is a separate change).
- Dashboard (§5.1) and the non-profile Accounts pages (§9 Transactions, Reports, …) — already exist or are
  a separate task.
