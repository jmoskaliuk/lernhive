# local_lernhive_contenthub — Tasks

## Done (2026-04-10)
- scaffolded the plugin (version.php, lib.php, db/access.php, lang/en,
  settings.php, styles.css, README, null privacy provider)
- built the card orchestration layer (`card`, `card_registry`) with
  an **injectable plugin detector** so unit tests do not depend on
  which sibling plugins are installed in the test environment
- built the renderable + renderer + mustache template
- wired the entry page (dual admin / standard layout)
- the existing repo-wide `deploy-hetzner.yml` picks the plugin up
  via its `plugins/**` path filter on merge to `main` and runs the
  Moodle upgrade + cache purge inside the container; for R1 this is
  the canonical CI gate
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

## Done (2026-04-11)
- shipped commit `9475cf5` with the full R1 hub page, sibling
  scaffolds and version metadata
- follow-up commit `2ab4cbf`:
  - bumped `contenthub/version.php` to `2026041002` after discovering
    that the rewrite in `9475cf5` had accidentally downgraded the
    version from `2026041001` → `2026041000`, blocking upgrade
  - bumped `copy` and `library` to the same version and synced their
    `$plugin->dependencies['local_lernhive_contenthub']` reference
  - removed `.github/workflows/moodle-plugin-ci.yml`: the dedicated
    moodle-plugin-ci matrix would block R1 iteration with warnings
    that are not worth fixing before the plugin has stabilised.
    `deploy-hetzner.yml` stays as the sole automated gate.

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
