# theme_lernhive — Tasks

## Done (shipped)

- [x] 0.9.40 — Icon taxonomy: add Type 4 `.lh-icon-info` (2026-04-11):
  - `scss/lernhive/_icons.scss` — file-header comment rewritten from a three-type to a four-type taxonomy (Navigation / Artifact / Action / **Information**). The new Type 4 documents intent explicitly: "passive signal — does NOT navigate, does NOT classify content, does NOT trigger a function; communicates state, help, warning, or error".
  - New `.lh-icon-info` class: 28 × 28 px rounded square (6 px radius), `cursor: help`, NO transform/scale/lift on hover, NO box-shadow on hover — visually distinct from both `.lh-icon-action` (circle, grows) and `.lh-icon-artifact` (9 px tile, metadata). A subtle background-tint deepen is the only hover feedback.
  - Semantic modifiers: `--locked`, `--complete`, `--new`, `--pending` (status), `--help`, `--warning`, `--error`, `--success`, `--info` (form/alert signals). All modifiers use existing `$lh-*-light` / accent palette — no new brand tokens.
  - Size variants: `--sm` (20 × 20, borderless — inline use in body copy + table cells), `--lg` (36 × 36, for alert banners + empty states).
  - `mockups/icon-matrix.md` — four-type taxonomy documented as a canonical section with the per-type Lucide mapping for the Information scope (`lock`, `check`, `clock`, `circle-help`, `triangle-alert`, `circle-x`, `info`, …). Explicit "do NOT use Information for" list to prevent future regressions where a card-action "info" button gets misfiled as Information.
  - First template rewire: `local_lernhive_onboarding/tours.php` + `templates/tour_overview.mustache` — category status pill (not_started / in_progress / completed) now renders as `.lh-icon-info--sm` chip + visible text label. Hard-coded Google-ish pill colours (`#f5f6f8`, `#e8f0fe`, `#e6f4ea`) are removed and replaced with theme-token-driven SCSS. Status → visual mapping (`not_started → locked/circle-dot`, `in_progress → pending/clock`, `completed → complete/check`) lives in `tours.php` so the template stays declarative.
  - Not yet rewired: help marks in admin settings forms, alert icons in form-validation surfaces, the content-locked icons on card grids outside of onboarding. Each surface comes in a separate commit so it can be smoke-tested individually.


- [x] 0.9.9 — Fix login form invisible (#maincontent height:1px trap); add lernhive-skip-anchor
- [x] 0.9.18 — drawers.mustache admin navigation: add Site Admin link to sidebar for siteadmin users
- [x] 0.9.19 — Fix #maincontent in drawers.mustache: move skip-anchor out of content section wrapper
- [x] 0.9.20 — Route 'admin' layout to admin.php (standard Moodle admin nav tree); no block regions on admin pages (ADR-04)
- [x] 0.9.21 — Context Dock: floating action strip for Teacher/Trainer (course edit mode, participants, gradebook, course settings) + Admin (site admin shortcut); CSS tooltips with progressive disclosure (ADR-03)
- [x] 0.9.22 — Context Dock: add block editing toggle (all pages where user_can_edit_blocks); lang strings dockblockson/dockblocksoff; icon: fa-pen-to-square
- [x] 0.9.23–0.9.25 — Admin layout: restore sidebar on admin pages; fix chrome-hiding CSS scope to cover admin pages; add output.secondary_nav to admin template
- [x] 0.9.26 — Page header redesign (ADR-05):
  - Launcher moved from sidebar to page header top-right; dropdown right-aligned, dark-on-white colors
  - Profile avatar → direct link to own profile page; chevron dropdown for preferences / logout
  - Language selector (globe icon + `lang_menu()`) between notifications and Launcher
  - Admin settings top-nav: horizontal tab-bar from `admin_get_root()` above main content on all admin pages
  - New lib.php helpers: `theme_lernhive_get_header_user_context()`, `theme_lernhive_get_admin_topnav()`
  - Sidebar is now purely navigational (launcher removed)
- [x] 0.9.27 — UX cleanup + Plugin Shell foundation:
  - User block: `<details>` dropdown replaced by gear (Preferences) + sign-out (Logout) icon buttons — drawers.mustache + admin.mustache + _navigation.scss
  - Admin nav: two-level horizontal tab-bar — Level 1 top categories + Level 2 sub-items of active category — lib.php + admin.mustache + _layout.scss
  - Plugin Shell: new `_plugin-shell.scss` partial — 2-zone sticky page header for local plugins (Zone A header+tags, Zone B info/CTA, card grid with states, action buttons)
  - Mockup: `plugin-shell-concept.html` v0.2 (inline SVG sprite) + `plugin-shell-spec.md`
- [x] 0.9.37 — Page-header alignment + avatar redesign + CTA strip icon (2026-04-11):
  - `scss/lernhive/_layout.scss` — `.lernhive-page-header`: `align-items: center → flex-start` so action icons pin to the top of the header rather than floating vertically centred when `__main` is tall (page title + breadcrumb); top padding `$lh-spacing-sm (8px) → $lh-spacing-xs (4px)`.  `__actions` gets `padding-top: $lh-spacing-xs` for a consistent 4 px visual buffer.
  - `scss/lernhive/_layout.scss` — New `.lernhive-user-block` CSS block (replaces the now-dead `.usermenu` rules). Targets the template's actual class structure (`__avatar` link + `__action` icon buttons):
    - `__avatar`: 36 × 36 px, `border-radius: 50%`, circular border-color hover (was rectangular `$lh-radius-sm` hover from generic `.nav-link` rule)
    - `.userinitials` (Moodle 5.x no-photo output): `font-size: 0 !important` hides "AU" initials text; `::before` draws a Lucide "user" SVG via CSS mask-image in `$lh-primary` on `$lh-primary-light` background
    - `__action` (settings, logout): 36 × 36 px, `$lh-radius-sm` hover; `__action--danger` gets red hover
  - `scss/lernhive/_dashboard.scss` — `.lh-cta-strip__icon`: changed from solid dark primary tile (`border-radius: 0; background: $lh-primary; color: #fff`) to `.lh-icon-artifact`-style rounded square (`border-radius: 9px; background: $lh-primary-light; color: $lh-primary`). Warning/success variants updated to match.
- [x] 0.9.36 — Admin tab bar visibility fix (Johannes: Screenshot Boost-Referenz `/admin/search.php` zeigt `Allgemein | Nutzer/innen [active] | Kurse | …`, LernHive produziert das HTML aber rendert nichts):
  - `scss/lernhive/_base.scss` — remove the too-broad `nav.moremenu { display: none !important }` selector. Root cause: Boost's `core/moremenu` partial renders BOTH the primary navbar and the secondary admin tab bar as `<nav class="moremenu navigation">`, so the broad hide that was meant to suppress the primary navbar also killed the admin tab bar that 0.9.34 builds via `{{> core/moremenu }}`. The `.secondary-navigation { display: block }` override from 0.9.26 only un-hid the wrapper div; the `<nav>` element inside stayed `display: none !important`. Scoped the hide rule to `.primary-navigation` only, which cleanly suppresses the Boost primary navbar wrapper without affecting the `.secondary-navigation` wrapper used by the admin tab bar.
  - Net: admin tab bar now actually renders on screen — canonical Boost 9-tab sequence (General | Users | Courses | Grades | Plugins | Appearance | Server | Reports | Development) is visible as originally intended in 0.9.34.
- [x] 0.9.35 — Plugin shell card grid + ContentHub refinements (Johannes, commit 74a76a8):
  - `scss/lernhive/_plugin-shell.scss` — `.lh-plugin-grid` gap 8→16px; new `.lh-plugin-content-area` wrapper with responsive horizontal gutter (24/32px desktop, 16px tablet, 8px mobile)
  - `local_lernhive_contenthub/templates/hub_page.mustache` — remove redundant ← Dashboard back button, switch grid from `--cols-2` to `--cols-3`, replace `container-fluid py-4` with `lh-plugin-content-area` for consistency across plugin shell pages
- [x] 0.9.34 — Admin nav: delegate to core secondary_navigation (Johannes: "orientiere Dich komplett an der Boost Darstellung"):
  - `layout/admin.php` — build `secondarymoremenu` via `\core\navigation\output\more_menu($PAGE->secondarynav, 'nav-tabs', …)` exactly like `theme_boost/layout/drawers.php`. Also forwards `overflow` via `$PAGE->secondarynav->get_overflow_menu_data()`.
  - `templates/admin.mustache` — replaces the custom two-level `{{#hasadmintopnav}}…{{/hasadmintopnav}}` / `{{#hasadminsecondnav}}…{{/hasadminsecondnav}}` blocks with the canonical Boost pattern: `{{#secondarymoremenu}} … {{> core/moremenu}} {{/secondarymoremenu}}` wrapped in a thin `.lernhive-secondary-navigation` container. Also renders the `tertiary-navigation / url_select` overflow block when present.
  - `lib.php` — `theme_lernhive_get_admin_topnav()` function deleted (no longer called; the custom `admin_get_root()` walk produced an L1/L2-mixed tab list on `/admin/index.php`).
  - `scss/lernhive/_layout.scss` — `.lernhive-admin-topnav` + `.lernhive-admin-topnav--secondary` rules removed; replaced with a thin `.lernhive-secondary-navigation` wrapper that only handles horizontal gutter + bottom margin so the core `nav.nav-tabs` sits flush with the flush-layout shell from 0.9.33.
  - Net: admins now see the exact same canonical tab bar as Boost (General | Users | Courses | Grades | Plugins | Appearance | Server | Reports | Development), including Moodle core's own overflow-menu handling.
- [x] 0.9.33 — Layout shell flush edges (Johannes: "vollflächige Hintergründe, nur Kacheln als Abgrenzung"):
  - `.lernhive-page` — `padding: 0`, `gap: 0` (was `$lh-spacing-lg` + `$lh-spacing-md`); page column is now a flush full-width canvas
  - `.lernhive-page-header` — tile chrome dropped: `background: transparent`, `border: none`, `border-radius: 0`, `box-shadow: none`; pulled flush to viewport top + flush to sidebar right edge; internal padding preserved so icon cluster keeps breathing room
  - `.lernhive-page-content` — horizontal + bottom gutter added locally (`padding: 0 $lh-spacing-lg $lh-spacing-lg`) so regular Moodle pages still have breathing room around `.lernhive-main`; plugin-shell pages override this to `padding: 0` via `:has(.lh-plugin-header)` so Zone A sits edge-to-edge under the top header bar
  - Small-screen responsive block rewired: `<375px` tightens `.lernhive-page-content` horizontal padding instead of the (now zero-padding) `.lernhive-page`
  - No changes to `.lh-plugin-header` / `.lh-plugin-infobar` — the two strips (title + message) remain visually distinct as designed
  - Applies to both `drawers.mustache` and `admin.mustache` because they share the same class hierarchy

## Open — R1 scope

- [ ] **Smoke-test 0.9.37** — on `dev.lernhive.de`:
  - Nav icons (notifications, launcher, avatar) sit at the very top of the content column — no large top gap
  - Avatar with no uploaded photo shows a person icon (not "AU" text, not a rectangle hover)
  - CTA strip icon is a soft rounded square (not solid dark tile)
  - Admin tab bar (General | Users | Courses | …) is visible; active tab highlights; overflow renders
  - Primary navbar is still suppressed (no duplicate nav bars)
  - ContentHub renders 3-column card grid with `lh-plugin-content-area` gutter
- [ ] **Smoke-test 0.9.34/0.9.36** — superseded by the 0.9.37 smoke-test above
- [ ] **Smoke-test 0.9.33** — superseded by the 0.9.37 smoke-test above
- [ ] `regionmainsettingsmenu` must stay — Teachers use it to add blocks to courses (block positions still unclear after right-hand drawer removal). Keep until an explicit block-placement UX replaces it.
- [ ] Student dock items: progress overview shortcut, continue-learning button (post-flavour integration)
- [ ] PHPUnit @covers deprecations in non-onboarding plugins (contenthub, copy, flavour, library — 41 remaining)
- [ ] PHPUnit failure: `flavour_manager_test::test_apply_on_fresh_site_does_not_flag_overrides` (assertFalse fails, overrides_detected = true on fresh site)
- [ ] Behat init_failed on Hetzner (wwwroot / Selenium config issue)

## Deferred — post-R1

- [ ] Manager dock items (course management, user enrolment, reporting shortcuts)
- [ ] Dock progressive disclosure: migrate from localStorage to Moodle user preference (M.util.set_user_preference) for persistence across devices
- [ ] format_lernhive_snack: move snack_*.mustache out of theme (ADR-01, target 0.10.0)
- [ ] format_lernhive_community: community feed rendering (ADR-01, target 0.11.0)
- [ ] Decide whether format_lernhive_classic is needed or if format_topics suffices
