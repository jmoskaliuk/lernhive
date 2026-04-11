# theme_lernhive — Tasks

## Done (shipped)

- [x] 0.9.3 — Six fixed block regions (content-top, content-bottom, sidebar-bottom, footer-left/center/right); remove side-pre drawer
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

- [ ] **Smoke-test 0.9.34** — on `/admin/index.php` verify the tab bar shows the canonical Boost sequence (General | Users | Courses | Grades | Plugins | Appearance | Server | Reports | Development), active tab highlights correctly as you drill into categories, overflow menu renders when tabs don't fit
- [ ] **Smoke-test 0.9.33** — verify on local Moodle: ContentHub page has no outer tile, top icon bar is flush to viewport, regular Moodle pages still render `.lernhive-main` as a centered tile with side gutters
- [ ] **Smoke-test 0.9.27** — obsolete (superseded by 0.9.33 + 0.9.34 smoke-tests above)
- [ ] `regionmainsettingsmenu` must stay — Teachers use it to add blocks to courses (block positions still unclear after right-hand drawer removal). Keep until an explicit block-placement UX replaces it.
- [ ] Student dock items: progress overview shortcut, continue-learning button (post-flavour integration)
- [ ] Smoke-test steps 3–6 (Fresh Apply LXP, config key verification, override test, audit table)
- [ ] PHPUnit @covers deprecations in non-onboarding plugins (contenthub, copy, flavour, library — 41 remaining)
- [ ] PHPUnit failure: `flavour_manager_test::test_apply_on_fresh_site_does_not_flag_overrides` (assertFalse fails, overrides_detected = true on fresh site)
- [ ] Behat init_failed on Hetzner (wwwroot / Selenium config issue)
- [ ] Smoke-test steps 3–6 (Fresh Apply LXP, config key verification, override test, audit table)
- [ ] PHPUnit @covers deprecations in non-onboarding plugins (contenthub, copy, flavour, library — 41 remaining)
- [ ] PHPUnit failure: `flavour_manager_test::test_apply_on_fresh_site_does_not_flag_overrides` (assertFalse fails, overrides_detected = true on fresh site)
- [ ] Behat init_failed on Hetzner (wwwroot / Selenium config issue)

## Deferred — post-R1

- [ ] Manager dock items (course management, user enrolment, reporting shortcuts)
- [ ] Dock progressive disclosure: migrate from localStorage to Moodle user preference (M.util.set_user_preference) for persistence across devices
- [ ] format_lernhive_snack: move snack_*.mustache out of theme (ADR-01, target 0.10.0)
- [ ] format_lernhive_community: community feed rendering (ADR-01, target 0.11.0)
- [ ] Decide whether format_lernhive_classic is needed or if format_topics suffices
