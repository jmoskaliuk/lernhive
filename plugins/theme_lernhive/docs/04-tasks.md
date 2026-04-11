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

## Open — R1 scope

- [ ] `regionmainsettingsmenu` must stay — Teachers use it to add blocks to courses (block positions still unclear after right-hand drawer removal). Keep until an explicit block-placement UX replaces it.
- [ ] Student dock items: progress overview shortcut, continue-learning button (post-flavour integration)
- [ ]  Das Menu oben bei admin.php wird angezeigt, es ist aber das falsch. Das ist das erst Untermenu im Adminbereich. Es müsste ein zweites übergeordnetes Menu geben, das dort angezeigt werden soll. 
- [ ] Dropdown neben Profilbild oben rechts sollte weg. Die Funktionen "Einstellungen" und "Ausloggen" sollten als Icons neben dem Profilbild stehen. Edit Profil kann weg, das ist ja jetzt mit dem profilbild verknüfpt
 [ ] Es solle noch ein Icon für die Sprachauswahl geben. 
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
