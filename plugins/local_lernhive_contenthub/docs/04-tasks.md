# local_lernhive_contenthub — Tasks

## Done (0.1.3 — 2026-04-11)

- **LH-HUB-SHELL-01** — `templates/hub_page.mustache` refined for plugin-shell consistency:
  - Removed `← Dashboard` back button (redundant with sidebar; would appear on every plugin page)
  - Container changed from `<div class="container-fluid py-4">` → `<div class="lh-plugin-content-area">` (picks up the 24/32 px responsive horizontal gutter defined in `theme_lernhive/_plugin-shell.scss`)
  - Grid changed from `lh-plugin-grid--cols-2` → `lh-plugin-grid--cols-3` (3-column layout is now the standard for LernHive content card grids on desktop)
- **LH-HUB-CARD-01** — `classes/card.php`: `default` branch of icon_color switch changed from `''` → `'generic'` so cards without an explicit type get the `--generic` blue-gray artifact icon fallback instead of no colour class

## Done (0.1.0–0.1.2 — 2026-04-10)
- scaffolded the plugin (version.php, lib.php, db/access.php, lang/en,
  settings.php, styles.css, README, null privacy provider)
- built the card orchestration layer (`card`, `card_registry`) with
  an **injectable plugin detector** so unit tests do not depend on
  which sibling plugins are installed in the test environment
- built the renderable + renderer + mustache template
- wired the entry page (dual admin / standard layout)
- added `.github/workflows/moodle-plugin-ci.yml` so every push / PR
  touching the plugin runs the full moodle-plugin-ci matrix
- the existing repo-wide `deploy-hetzner.yml` already picks the plugin
  up via its `plugins/**` path filter on merge to `main`
- PHPUnit tests: `card_test.php` (value object shape + interactivity
  gating) and `card_registry_test.php` (install-detection matrix,
  AI card toggle, stable ordering) — all via the injected detector
- Behat feature `hub_page.feature` — admin via admin tree, course
  creator direct access, AI card toggle via admin setting
- **AI card is now gated** behind an admin setting
  (`local_lernhive_contenthub/show_ai_card`, default off). The
  settings page lives alongside the hub externalpage under a new
  `local_lernhive_contenthub_cat` admin category
- scaffolded `local_lernhive_copy` and `local_lernhive_library` as
  sibling plugins (stub + R1 skeleton) so the Copy, Template, and
  Library cards resolve to a real page instead of "Unavailable"
- extended the CI workflow matrix to cover all three plugins

## Confirmed decisions (2026-04-10)
- **Copy plugin entry URL**: `/local/lernhive_copy/index.php` (default)
  and `/local/lernhive_copy/index.php?source=template` for the
  Template card. The two share the same wizard code and are disambiguated
  by `classes/source.php`, which guards against stray query values.
- **Library plugin entry URL**: `/local/lernhive_library/index.php`
- **Template stays a sub-action** of the copy plugin via `?source=template`
  for R1 — the wizard code is the same, only the heading and intro differ.
- **AI card rendering**: hidden by default, opt-in via admin setting.
  Keeps R1 focused while still letting interested admins preview the
  R2 direction.

## Current tasks
*(none — Release 1 scope is code-complete for the hub orchestration)*

## Open questions
- release 2: telemetry on which card was clicked would require
  replacing the null privacy provider — revisit once the card click
  actually does work worth measuring
- exact Moodle API touchpoints: do we want a `core\hook` implementation
  to let the launcher plugin advertise the hub entry, or a static call
  from the launcher? Defer until the launcher plugin lands.
- should the Library catalog be filtered by the tenant's active
  LernHive flavour in R2, or only in R3? (tracked in the Library plugin's
  own task list)

## Next step
Wire the real actions behind the disabled CTAs:
1. Copy wizard — hand off to Moodle core backup/restore with the
   right defaults for the Simple mode
2. Library catalog — connect `classes/catalog.php` to the eLeDia
   managed catalog feed and implement the import button
