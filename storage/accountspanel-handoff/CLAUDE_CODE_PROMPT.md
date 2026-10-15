Copy everything below the line and paste it into Claude Code, from your Laravel project root, after copying this whole folder into the project as `docs/design-handoff/`.

---

Implement the AR Emporium Accounts + Master Profile module from the design handoff in `docs/design-handoff/`.

Read these first, fully:
1. `docs/design-handoff/IMPLEMENTATION_SPEC.md`: the full spec, business rules, data model and acceptance checklist.
2. Every file in `docs/design-handoff/design/`: the UI source of truth (`.dc.html` design files; §1 of the spec explains how to read them).

Rules:
- **The UI must match the design files exactly.** Same layout, spacing, colors, font sizes, icons and copy (including the Bangla text). Do not redesign, rename, reorder or "improve" anything visible. Port inline styles to Tailwind (arbitrary values allowed) so the result is pixel-identical.
- All numbers and rows in the designs are sample data. Replace them with real database data while keeping the same visual shape and color rules.
- Everything clickable in the design must work. If the backend for something doesn't exist yet (models, migrations, vouchers, posting, installments, payroll, stock valuation, SMS, import, merge), build it.
- Screens that are linked but not designed (spec §9): build them in the same design language.
- Before writing code, inspect the existing project: routes, models, migrations, the admin layout, and the existing Accounts / Purchase / Inventory modules. Reuse and extend what exists; don't duplicate tables or break existing pages.
- Stack: Laravel + Livewire 3 + Alpine.js + Tailwind CSS. Timezone Asia/Dhaka. BD money format (৳1,24,300). The week starts on Saturday.
- `docs/design-handoff/reference/previous-livewire-dashboard/` is an older partial export of the dashboard. Use it only for helper ideas (e.g. the `money()` BD formatter). Where it differs from the design files, the design files win.

Process:
1. Inspect the codebase and write a short plan mapping each spec section to files you'll create or change. List any conflicts with existing code and how you'll resolve them.
2. Build in the order of spec §10, committing per step.
3. Write feature tests for every rule in spec §7.
4. At the end, go through the §11 acceptance checklist and report what passes and anything left.
