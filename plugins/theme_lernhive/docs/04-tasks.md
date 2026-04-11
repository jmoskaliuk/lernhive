# theme_lernhive — Tasks

## Done (shipped)

- [x] 0.9.3 — Six fixed block regions (content-top, content-bottom, sidebar-bottom, footer-left/center/right); remove side-pre drawer
- [x] 0.9.9 — Fix login form invisible (#maincontent height:1px trap); add lernhive-skip-anchor
- [x] 0.9.18 — drawers.mustache admin navigation: add Site Admin link to sidebar for siteadmin users
- [x] 0.9.19 — Fix #maincontent in drawers.mustache: move skip-anchor out of content section wrapper
- [x] 0.9.20 — Route 'admin' layout to admin.php (standard Moodle admin nav tree); no block regions on admin pages (ADR-04)
- [x] 0.9.21 — Context Dock: floating action strip for Teacher/Trainer (course edit mode, participants, gradebook, course settings) + Admin (site admin shortcut); CSS tooltips with progressive disclosure (ADR-03)
- [x] 0.9.22 — Context Dock: add block editing toggle (all pages where user_can_edit_blocks); lang strings dockblockson/dockblocksoff

## Open — R1 scope

- [ ] Hide `regionmainsettingsmenu` ("Blocks editing on" text button above content) once Dock is confirmed as the primary mechanism — avoids duplicate controls
- [ ] Student dock items: progress overview shortcut, continue-learning button (post-flavour integration)
- [ ] Smoke-test steps 3–6 (Fresh Apply LXP, config key verification, override test, audit table)
- [ ] PHPUnit @covers deprecations in non-onboarding plugins (contenthub, copy, flavour, library — 41 remaining)
- [ ] PHPUnit failure: flavour_manager_test::test_apply_on_fresh_site_does_not_flag_overrides (assertFalse fails, overrides_detected = true on fresh site)
- [ ] Behat init_failed on Hetzner (wwwroot / Selenium config issue)

## Deferred — post-R1

- [ ] Manager dock items (course management, user enrolment, reporting shortcuts)
- [ ] Dock progressive disclosure: migrate from localStorage to Moodle user preference (M.util.set_user_preference) for persistence across devices
- [ ] format_lernhive_snack: move snack_*.mustache out of theme (ADR-01, target 0.10.0)
- [ ] format_lernhive_community: community feed rendering (ADR-01, target 0.11.0)
- [ ] Decide whether format_lernhive_classic is needed or if format_topics suffices

## Open questions

- Should the Dock replace `regionmainsettingsmenu` entirely (i.e. suppress Moodle's block-editing text button), or keep both for now? Current answer: keep both until Teacher feedback confirms Dock is sufficient.
- Dock icon for block editing: `fa-th` (grid) good enough, or look for a better FA4 icon?
