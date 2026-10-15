# AR Emporium — Accounts panel design handoff

This package covers the full Accounts panel, the Master Profile module, and Customer Commission & Rebate, all in their latest design.

## Contents
| Path | What |
|---|---|
| `design/*.dc.html` | UI source of truth (22 screens + Sidebar + ProfileTabs) |
| `design/canvas.json` | Canvas layout (reference only) |
| `IMPLEMENTATION_SPEC.md` | Full spec for Claude Code |
| `CLAUDE_CODE_PROMPT.md` | Prompt to paste into Claude Code |
| `reference/previous-livewire-dashboard/` | Older dashboard code (helpers only) |

## How to use
1. Copy this folder into your Laravel project as `docs/design-handoff/`.
2. Open Claude Code in the project root.
3. Paste the text from `CLAUDE_CODE_PROMPT.md`.

## Screens
**Accounts**
| Screen | File |
|---|---|
| Dashboard | `Main.dc.html` |
| Posting Pending | `PostingPending.dc.html` |
| Transactions | `Transactions.dc.html` |
| Cash & Bank | `CashBank.dc.html` |
| Receivables | `Receivables.dc.html` |
| Commission | `Commission.dc.html` |
| Payables | `Payables.dc.html` |
| Loans & EMI | `LoansEmi.dc.html` |
| Fixed Assets | `FixedAssets.dc.html` |
| Expenses | `Expenses.dc.html` |
| Reports (+ Daily report, Saved reports) | `Reports.dc.html` |
| Settings | `Settings.dc.html` |

**Profiles**
| Screen | File |
|---|---|
| Master Profiles | `Profiles.dc.html` |
| Customers | `Customers.dc.html` |
| Suppliers | `Suppliers.dc.html` |
| Lenders | `Lenders.dc.html` |
| Owners | `Owners.dc.html` |
| Employees | `Employees.dc.html` |
| Profile details | `ProfileDetail.dc.html` |
| New / edit profile | `ProfileForm.dc.html` |
