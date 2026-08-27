# Phase 2 — Visual Section Builder

**Status: planned, not implemented.**

## Goal

Turn the Phase 1 flat "loop schema.json fields" editor into a real visual,
section-based builder: canvas + component palette + inspector, drag-drop
reorder, responsive (desktop/tablet/mobile) overrides, undo/redo, autosave,
and a real draft/published content split. This phase does **not** add any
ecommerce component types — those are Phase 3.

## Data model changes

- `landing_pages.content` starts being written in the `sections` array
  shape from the original spec:

  ```json
  {
      "version": 1,
      "sections": [
          { "type": "hero", "id": "hero_1", "settings": {}, "content": {} }
      ]
  }
  ```

  This coexists with Phase 1's flat-object shape (existing pages using the
  4 legacy templates keep working — `template.blade.php` for those
  templates expects the flat shape and is never migrated). New
  builder-created pages use a new class of template (a "builder template")
  whose `template.blade.php` is generic: it loops `content.sections` and
  dispatches each to a component renderer, rather than hand-authoring
  markup per template.

- **New**: `landing_page_revisions` table (`id`, `landing_page_id`,
  `content` json snapshot, `created_by`, `created_at`) — added *in this
  phase*, not Phase 1, because undo/redo and "restore a previous save" only
  matter once there's an interactive builder generating many small edits.
  Revision pruning policy (keep last N, or time-boxed) is an open question
  — see `07-open-questions-and-decisions.md`.

- **Decision on undo/redo mechanism**: client-side state history
  (Alpine/JS in-memory stack of `content` snapshots) for the *in-session*
  Ctrl+Z/Ctrl+Shift+Z experience — this is instant and needs no server
  round-trip. The `landing_page_revisions` table is a *separate*, coarser
  safety net (e.g. one revision per explicit Save or every N autosaves) for
  "restore this page to how it looked yesterday," not for step-by-step
  undo. Do not conflate the two.

## New/reused classes

### Component registry

`App\LandingPageEngine\ComponentRegistry` — a simple array-backed registry
(config-driven, following the `sectionComponents.registry` FQCN-list
pattern already used by `theme.json`) mapping a component `type` string to
its definition:

```text
type            (string, unique key, e.g. "heading")
name            (display name for the palette)
icon            (heroicon name or similar)
category        (text | media | layout | marketing — ecommerce category added in Phase 3)
schema          (field definitions — reuses the schema.json field-type vocabulary from Phase 1)
default_values  (seed content when a new instance of this component is added)
editor          (Blade/Livewire partial for the inspector panel)
renderer        (Blade view/component that renders the section on the frontend)
```

New component types register by adding an entry to this registry — the
canvas/inspector/renderer never need to know about specific types, only the
registry contract. This directly satisfies the spec's "new components
registerable without changing the main builder" requirement.

Initial Phase 2 component set (content-only, no ecommerce):
`heading`, `paragraph`, `rich_text`, `list`, `image`, `image_gallery`,
`video`, `container`, `row`, `columns`, `spacer`, `divider`, `hero`, `cta`,
`banner`, `countdown`, `testimonial`, `faq`, `features`, `custom_html`
(role-gated — see Security notes in `06-phase-5-polish.md`).

### Builder UI

- `App\Livewire\Admin\LandingPages\Builder\Canvas` — three-pane layout
  (component palette / canvas / inspector), reusing existing admin Tailwind
  conventions, `sortablejs` (already a project dependency per the audit)
  for drag-drop reorder instead of introducing a new JS drag-drop library.
- `App\Livewire\Admin\LandingPages\Builder\Inspector` — renders the
  selected section's `editor` partial from the registry, plus the
  responsive-override controls (see below).
- Autosave: debounced (client-side timer, e.g. 2–3s of inactivity) Livewire
  call persisting `content` to the `landing_pages` row directly — no new
  "draft content" column needed in Phase 2, since `status=draft` already
  means "not publicly visible" (Phase 1). A true draft/live content split
  (edit while published version stays untouched) is **explicitly deferred**
  — flagged as an open question, since it requires either a second content
  column or promoting `landing_page_revisions` into the source for the
  live page, both of which are real design decisions that shouldn't be
  made implicitly inside a builder-UI phase.

### Responsive overrides

Each section's `settings` gains an optional per-breakpoint override map:

```json
{
    "settings": {
        "columns": { "desktop": 4, "tablet": 2, "mobile": 1 },
        "padding": { "desktop": "48px", "mobile": "24px" }
    }
}
```

Only `desktop` is required; `tablet`/`mobile` fall back to the next-larger
defined breakpoint if absent (mobile falls back to tablet, tablet falls
back to desktop) — avoids forcing every field to be re-specified at every
breakpoint. Renderer components read this via a small shared helper rather
than each component re-implementing breakpoint-fallback logic.

## Admin UI

Builder is reached from `PageForm` — instead of Phase 1's flat field-list
editor, a "Open Builder" action replaces it. The `PageForm` metadata fields
(name/title/slug/SEO/header-footer mode) remain a simpler modal or side
panel, not absorbed into the canvas.

## Frontend/public UI

No change to `LandingPageShow`/`LandingPageRenderer` routing — only the
*shape* of what they receive changes for builder-created pages (a generic
per-component renderer loop instead of a bespoke `template.blade.php` per
template).

## Explicit non-goals for Phase 2

- No ecommerce component types (Phase 3).
- No true draft/live content split beyond Phase 1's status flag (flagged as
  open question, not solved here).
- No multi-user concurrent-edit conflict resolution (single-editor
  assumption for now).

## Dependencies on earlier phases

Requires Phase 1's `landing_pages`/`landing_page_templates` tables,
`LandingPageRenderer`, and admin route/layout conventions.
