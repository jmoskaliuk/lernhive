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

**Decision.** Introduce a floating `Context Dock` — a fixed-position, horizontal icon strip anchored at the bottom-right corner of the viewport on desktop, and a full-width strip at the bottom of the screen on mobile. The Dock is the single surface for context-aware page actions. Initial scope covers Teacher/Trainer and Admin; Student and Manager come later.

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

**Status.** Accepted. Core shipped in 0.9.21 (Teacher course actions + Admin shortcut). Block editing added in 0.9.22. Dock repositioned from sidebar-column to bottom-right viewport corner in 0.9.51 (see follow-up below).

**Follow-up (0.9.51) — Dock moved from sidebar column to bottom-right corner.** Between 0.9.21 and 0.9.50 the Dock was anchored inside the left sidebar column (`left: 0; width: $lh-sidebar-width; justify-content: center`). Visually the rightmost icons spilled into the main content area as soon as a 6th icon was added, and the sidebar anchor tied the Dock's width to a navigation surface it has no semantic relationship with. In 0.9.51 `_dock.scss` switches the desktop rule to `right: 1.5rem; bottom: 1.5rem; left: auto; width: auto; max-width: calc(100vw - 3rem); justify-content: flex-end` so the Dock hugs its contents, grows leftward when new items are added, and keeps a consistent 1.5 rem breathing distance from the viewport's right edge. The mobile `@media` branch (full-width strip at the screen bottom) is unchanged except for an explicit `justify-content: center` override to shield it from the new desktop flex-end rule.

## Architecture decision ADR-04 — Admin layout uses admin.php, not drawers.php (2026-04-11, shipped in 0.9.20)

**Decision.** The `admin` layout in `config.php` uses `admin.php` + `admin.mustache` (same LernHive app-shell with sidebar, plus a dedicated admin page header and admin top-nav), not the bare `drawers.php` path.

**Why.** Admin pages need the Moodle admin settings category tree for navigation. The LernHive sidebar only shows Home / Dashboard / My Courses / Site Admin — not the settings sub-tree. A dedicated layout file keeps admin UX separate from learner/trainer UX without risking regressions.

**Consequences.**
- Admin pages have no LernHive block regions (no `content-top`, `content-bottom`, etc.) and no Context Dock.
- Admin pages share `sidebar.mustache` for consistent left navigation.
- `admin.mustache` renders a page header (same structure as drawers) plus the admin secondary navigation bar. See ADR-06 for the tab-bar implementation strategy.

**Status.** Accepted and shipped in 0.9.20. Superseded in terms of admin tab-bar implementation by ADR-06 (0.9.34).

## Architecture decision ADR-05 — Page header owns Launcher, profile link, and language selector (2026-04-11, shipped in 0.9.26)

**Decision.** The Launcher icon (9-dot grid) moves from the sidebar to the top-right page header, alongside a new profile avatar link and language selector. The Moodle `output.user_menu` dropdown is replaced by a custom user block that separates the avatar (direct profile link) from a small chevron dropdown (preferences, logout).

**Why.** The sidebar is a navigation surface, not an action toolbar. Placing the Launcher and profile controls in the page header follows platform conventions (macOS, Google, GitHub) where app-level actions live at the top, context actions live in a floating Dock. Clicking the profile avatar directly to the profile page (without a dropdown) reduces friction for the most common action.

**Consequences.**
- `sidebar.mustache` no longer includes `{{> theme_lernhive/launcher }}`. The sidebar stays purely navigational.
- `drawers.mustache` and `admin.mustache` page header actions now follow the order: `[notifications] [lang menu] [launcher] [user block]`.
- The Launcher dropdown in header context is right-aligned and uses dark-on-white icon colors (not white-on-dark as in the sidebar).
- Language selector wraps `$OUTPUT->lang_menu()` with a globe icon prefix; hidden when Moodle has only one language.
- `lib.php` gains the helper function `theme_lernhive_get_header_user_context()` (shared by drawers.php and admin.php). A second helper, `theme_lernhive_get_admin_topnav()`, was added in 0.9.26 but removed again in 0.9.34 — see ADR-06.
- 0.9.27 follow-up: the user-block chevron dropdown was replaced with three explicit icon buttons (avatar → profile link, gear → preferences, sign-out → logout), removing the `<details>/<summary>` pattern.

**Status.** Accepted and shipped in 0.9.26. User-block dropdown superseded by explicit icon buttons in 0.9.27.

## Architecture decision ADR-06 — Admin tab bar delegates to Moodle core secondary_navigation (2026-04-11, shipped 0.9.34 + 0.9.36)

**Decision.** The admin settings tab bar (`General | Users | Courses | Grades | Plugins | Appearance | Server | Reports | Development`) is built by delegating entirely to Moodle core's `\core\navigation\views\secondary::load_admin_navigation()` pipeline and rendered via `core/moremenu`. Theme_lernhive's admin layout follows the exact same pattern as `theme_boost/layout/drawers.php` and `theme_boost/templates/drawers.mustache`. The previous custom helper `theme_lernhive_get_admin_topnav()` (0.9.26) is removed.

**Why.** The 0.9.26 custom helper walked `admin_get_root()->children` directly and treated every `admin_category` node as a top-level tab. In Moodle 5.x that tree is effectively flat — category nodes and leaf setting nodes sit at the same level, which produced an L1/L2-mixed tab list (`General | Users | Courses | Grades | AI | Competencies | Badges | H5P | Licence | Location | Language | Messaging`). Core's `load_admin_navigation()` already solves this: it promotes the site-admin root as a "General" tab and only lifts children with `display && !is_short_branch()` to the top level, producing the canonical Boost 9-tab sequence. Delegating keeps theme_lernhive automatically consistent with any future core nav refactor.

**Consequences.**
- `layout/admin.php` builds `$secondarymoremenu` from `new \core\navigation\output\more_menu($PAGE->secondarynav, 'nav-tabs', true, $tablistnav)` and also forwards `overflow` via `$PAGE->secondarynav->get_overflow_menu_data()` — exactly the Boost pattern.
- `templates/admin.mustache` replaces the custom two-level `{{#hasadmintopnav}}` / `{{#hasadminsecondnav}}` blocks with `{{#secondarymoremenu}} … {{> core/moremenu}} {{/secondarymoremenu}}` and a tertiary `{{#overflow}} … {{> core/url_select}} {{/overflow}}` block.
- `theme_lernhive_get_admin_topnav()` is deleted from `lib.php` (~165 lines of custom walker gone).
- `.lernhive-admin-topnav` / `.lernhive-admin-topnav--secondary` SCSS rules in `_layout.scss` are replaced by a thin `.lernhive-secondary-navigation` wrapper that only supplies horizontal gutter + bottom margin so the core `<nav class="moremenu navigation">` sits flush with the flush-layout shell from 0.9.33.
- **CSS scoping lesson (0.9.36 follow-up):** Boost's `core/moremenu` partial renders *both* the primary navbar and the secondary/admin tab bar as `<nav class="moremenu navigation">`. Theme_lernhive distinguishes them only at the wrapper level (`.primary-navigation` vs. `.secondary-navigation`), **exactly like Boost**. Any rule that tries to hide the primary navbar must be scoped to `.primary-navigation` — never to `nav.moremenu` or a bare `nav` selector — or it will also kill the admin tab bar. A 0.9.34-era `nav.moremenu { display: none !important }` rule in `_base.scss` accidentally did exactly that and had to be scoped down in 0.9.36.

**Status.** Accepted and shipped. Structural delegation 0.9.34; CSS scoping fix 0.9.36; JS-bootstrap fix 0.9.42 (see follow-up below).

**Follow-up (0.9.42) — JS bootstrap was missing the whole time.** The render path from 0.9.34/0.9.36 was structurally correct, but clicks on the admin tabs still didn't switch panels: the markup had `role="tablist"`, `data-bs-toggle="tab"`, and the correct `#linkX` href, yet nothing happened. Root cause was unrelated to the nav pipeline: theme_lernhive's custom layout templates (`admin.mustache`, `drawers.mustache`, `course.mustache`) were missing the trailing `{{#js}} require(['theme_boost/loader'], …) {{/js}}` block that Boost's upstream layouts all ship. Without that require, `theme_boost/loader` never ran, `theme_boost/aria.js` never installed its global `[role="tablist"] [data-bs-toggle="tab"]` click handler, and clicks never reached `bootstrap.Tab.show()`. Fixed in 0.9.42 by adding the standard Boost require block to all three custom layouts — see the "Layout JS bootstrap contract" section in `03-dev-doc.md` for the forward-looking rule this introduces.

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
