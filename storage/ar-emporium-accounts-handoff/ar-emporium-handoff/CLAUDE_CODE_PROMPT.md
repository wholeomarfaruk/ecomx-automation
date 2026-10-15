Copy everything below the line and paste it into Claude Code. Run it from your Laravel project root, after copying this folder into the project as `docs/design-handoff/`.

---

Implement the AR Emporium **Accounts panel** (the Accounts module, Master Profiles, and Customer Commission & Rebate) inside this project, at `/admin/accounts` and `/admin/profiles`. Use the design handoff in `docs/design-handoff/`.

Read these first, fully:
1. `docs/design-handoff/IMPLEMENTATION_SPEC.md` — screens, routes, tokens, data model, business rules, build order and acceptance checklist.
2. Every file in `docs/design-handoff/design/` — the UI source of truth: 22 screens and 2 shared components as `.dc.html` design files. Spec §1 explains how to read them.

Rules:
- **The UI must be the same as the design files.** Keep the same layout, spacing, colors, font sizes, icons and copy, including the Bangla text. Do not redesign, rename, reorder or "improve" anything visible. Port inline styles to Tailwind (arbitrary values are fine) so the result is pixel-identical.
- All numbers, names and rows in the designs are sample data. Replace them with real database data, keeping the same visual shape and color rules.
- Everything clickable in the designs must work:
  - **If a feature doesn't exist in this project, build it.**
  - **If an existing feature works differently from the spec, update it to match**, without breaking the other modules (Purchase, Inventory, Sales, Orders).
- Before writing code, inspect the codebase: routes, models, migrations, the admin layout, auth/roles, and the existing Accounts, Purchase, Inventory and Sales code. Reuse and extend what exists. Don't duplicate tables or break existing pages.
- Stack: Laravel, Livewire 3, Alpine.js and Tailwind CSS. Timezone is Asia/Dhaka. Use BD money format (৳1,24,300). The week starts on Saturday, and the financial year runs July–June, configurable in Settings.
- `docs/design-handoff/reference/previous-livewire-dashboard/` is an older partial export. Use it only for helper ideas, such as the `money()` formatter. Where it differs from the design files, the design files win.

Process:
1. Inspect the codebase. Write a short plan that maps each spec section to the files you'll create or change, and list any conflicts with existing code and how you'll resolve them. Show me the plan before starting.
2. Build in the order of spec §9, committing after each step.
3. Write feature tests for every rule in spec §7.
4. At the end, go through the §10 acceptance checklist and report what passes and what is left.
