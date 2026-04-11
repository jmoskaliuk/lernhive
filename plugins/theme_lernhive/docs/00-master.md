# theme_lernhive — Master Document

**Plugin type:** theme
**Release target:** R1

## Purpose

LernHive visual layer, interaction system, and Moodle-facing theme implementation target.

## Role in LernHive

This plugin is part of the LernHive ecosystem and should fit the core rules:
- Moodle stays the core
- English-first terminology
- use Moodle core strings wherever possible
- no business logic in the theme
- must stay understandable for UX, docs, marketing and sales

## Main dependencies
- Moodle theme APIs
- product navigation and terminology rules
- LernHive plugin output that the theme styles but does not control functionally

## Main features
- design tokens and component styling
- responsive left-oriented navigation
- calm, touch-friendly page layouts
- Launcher and helper surfaces as action-oriented UI
- six predictable block regions (content-top, content-bottom, sidebar-bottom, footer-left/center/right) — no right-hand collapsible drawer

## Out of scope — things the theme must NOT own
- course content rendering (belongs in course format plugins — see Architecture decision ADR-01 below)
- snack-specific presentation logic (target: `format_lernhive_snack` in 0.10.0)
- community feed rendering (target: `format_lernhive_community` in 0.11.0)
- any business logic; the theme is a pure presentation layer

## Architecture decision ADR-01 — Course content lives in course formats, not theme (2026-04-11)

**Decision.** LernHive will move course-specific presentation (Snack, Community, classic course) out of `theme_lernhive` and into dedicated course format plugins. The theme keeps site chrome (shell, sidebar, header, blocks) and stops rendering course-content surfaces.

**Why.** Moodle's canonical separation of concerns places course content rendering in course format plugins (e.g., `format_topics`, `format_grid`), not in themes. The current setup — Snack rendering in `theme_lernhive/templates/snack_*.mustache` and course-specific CSS in `_course.scss` — fights Moodle's grain, blocks per-course customization, and makes the theme non-reusable.

**Consequences.**
- The theme becomes format-agnostic and smaller. Any Moodle theme will be able to host LernHive courses once the formats ship.
- A new `format_lernhive_snack` plugin owns the short-form experience (target 0.10.0). Templates move from `theme_lernhive/templates/snack_*.mustache` into the format's `templates/`.
- A new `format_lernhive_community` plugin owns community feeds (target 0.11.0). "Community" is treated as a course format because community lifecycle (enrol, sections, completion, gradebook) aligns with Moodle course semantics.
- `_course.scss` selectors scoped to `body.path-course-view` get migrated into the format plugins' own styles.
- Optional `format_lernhive_classic` only if required — core `format_topics` may suffice.

**Status.** Accepted. Implementation begins post-0.9.3 block-regions refactor.

## Architecture decision ADR-02 — Block regions replace the right-hand drawer (2026-04-11, shipped in 0.9.3)

**Decision.** Remove the single `side-pre` collapsible block drawer and introduce six fixed block regions: `content-top`, `content-bottom`, `sidebar-bottom`, `footer-left`, `footer-center`, `footer-right`. `content-bottom` is the default region for new blocks.

**Why.** A collapsible right-hand drawer does not fit a modern LMS shell and disturbs reading flow. Blocks are still a valuable mechanism to place context-specific UI, so the concept stays — but in predictable, reading-flow-aware positions inspired by the Boost Union multi-region pattern.

**Consequences.**
- Existing block placements in `side-pre` are orphaned. Acceptable because LernHive is still alpha (MATURITY_ALPHA) and dev.lernhive.de is the only deployment.
- Variant chosen: **fixed regions in template (A)**, not toggle-settings (B). Settings can be added later if real need emerges.
- The course page no longer ships a hardcoded "Course helpers" aside; that space is now a standard `content-bottom` region that any plugin can populate via Moodle blocks.

**Status.** Accepted and shipped in 0.9.3.

## Release scope

### Phase 1 — Mockup
- define the visual direction for LernHive
- define page shells, navigation, cards, and action surfaces
- define responsive behavior for desktop and mobile
- align all theme decisions with the documented LernHive product model

### Phase 2 — Moodle theme implementation
- translate the approved mockup direction into `theme_lernhive`
- keep Moodle core functionality intact
- implement styling, layout regions, navigation treatment, and reusable components

### Release 1
- simple, guided, readable UI
- left navigation as the primary navigation pattern
- Launcher stays action-oriented and does not become full navigation
- Explore is styled only for the optional LXP Flavour

### Later
- richer refinement after the Release 1 baseline is stable
- no hidden movement of advanced interaction ideas into the initial theme scope

## DevFlow files
- 00-master.md
- 01-features.md
- 02-user-doc.md
- 03-dev-doc.md
- 04-tasks.md
- 05-quality.md
