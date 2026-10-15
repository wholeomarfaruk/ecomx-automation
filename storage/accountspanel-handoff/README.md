# AR Emporium — Design handoff

This package contains the Accounts dashboard and the Master Profile module, in their latest design.

## What's inside
| Path | What it is |
|---|---|
| `design/*.dc.html` | The UI source of truth: 9 pages + 2 shared components (Sidebar, ProfileTabs) |
| `design/canvas.json` | Layout of the design canvas (for reference only) |
| `IMPLEMENTATION_SPEC.md` | Full spec for Claude Code: UI rules, tokens, every page, data model, business rules, checklist |
| `CLAUDE_CODE_PROMPT.md` | The prompt to paste into Claude Code |
| `reference/previous-livewire-dashboard/` | Earlier Livewire dashboard code (for helpers only; the design wins) |

## How to use it
1. Copy this whole folder into your Laravel project as `docs/design-handoff/`.
2. Open Claude Code from the project root.
3. Paste the text from `CLAUDE_CODE_PROMPT.md`.

## Pages
- Accounts Dashboard: `Main.dc.html`
- Master Profiles: `Profiles.dc.html`
- Customers: `Customers.dc.html`
- Suppliers: `Suppliers.dc.html`
- Lenders: `Lenders.dc.html`
- Owners: `Owners.dc.html`
- Employees: `Employees.dc.html`
- Profile details: `ProfileDetail.dc.html`
- New / edit profile: `ProfileForm.dc.html`
