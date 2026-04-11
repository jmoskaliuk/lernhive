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
- Context Dock — floating action strip for Teacher/Trainer and Admin contextual actions

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

## Architecture decision ADR-03 — Context Dock as the central action layer for Teacher/Trainer (2026-04-11, shipped in 0.9.21)

**Decision.** Introduce a floating `Context Dock` — a fixed-position icon strip anchored at the bottom of the sidebar column on desktop, and a horizontal strip at the bottom of the screen on mobile. The Dock is the single surface for context-aware page actions. Initial scope covers Teacher/Trainer and Admin; Student and Manager come later.

**Why.** Moodle's default "Blocks editing on" and page-action buttons appear as large text buttons at arbitrary positions in the page header or content area. This breaks reading flow and makes it hard to discover actions. A fixed, predictable action surface gives teachers a stable "home base" for actions regardless of which page they are on, and keeps the page header clean.

**Actions in scope for 0.9.21–0.9.22:**

| Context | Icon | Action |
|---|---|---|
| Course page (teacher) | pencil / ✓ | Course edit mode on/off |
| Any page (edit capable) | th / th-large | Block editing on/off |
| Course page (teacher) | users | Participants list |
| Course page (teacher) | bar-chart | Gradebook |
| Course page (teacher) | cog | Course settings |
| Any non-admin page (admin) | shield | Site admin shortcut |

**Actions deferred (Student, post-R1):** progress overview, continue-learning shortcut.
**Actions deferred (Manager, later):** course management, user enrolment, reporting shortcuts.

**Design rules.**
- Dock is dark-themed (darker than sidebar) with backdrop-blur.
- Tooltips appear on hover via CSS only — no JS required for baseline.
- Progressive disclosure: JS IIFE counts page visits in localStorage; after 3 visits, tooltips are hidden (class `.lh-dock--experienced`). Reset on cache clear. Safe fallback when localStorage blocked.
- Dock is NOT shown on admin pages (those use `admin.php` layout with full Moodle admin tree navigation).
- Mobile: horizontal strip at bottom of screen, tooltip appears above the icon.

**Consequences.**
- The `regionmainsettingsmenu` (which currently renders "Blocks editing on" as a text button above page content) can be hidden in a follow-up once teachers confirm the Dock covers the same function.
- The theme must not absorb business logic via the Dock — all items are simple URL links or toggle URLs, never custom AJAX handlers.

**Status.** Accepted. Core shipped in 0.9.21 (Teacher course actions + Admin shortcut). Block editing added in 0.9.22.

## Architecture decision ADR-04 — Admin layout uses admin.php, not drawers.php (2026-04-11, shipped in 0.9.20)

**Decision.** The `admin` layout in `config.php` uses `admin.php` + `admin.mustache` (standard Moodle secondary navigation including the admin settings category tree), not `drawers.php` (LernHive app-shell with sidebar).

**Why.** Admin pages need the Moodle admin settings category tree for navigation. The LernHive sidebar only shows Home / Dashboard / My Courses / Site Admin — not the settings sub-tree. Switching layout file restores full admin navigation at zero risk to learner/trainer UX.

**Consequences.**
- Admin pages have no LernHive block regions (no `content-top`, `content-bottom`, etc.).
- Admin pages do not show the Context Dock (admin has its own native navigation tree).
- `admin.mustache` uses `{{{ output.full_header }}}` which renders Moodle secondary navigation for admin context.

**Status.** Accepted and shipped in 0.9.20.

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
