# local_lernhive_library — Tasks

## R1.1 status: shipped (0.1.1 — 2026-04-11)

- **LH-LIB-LAYOUT-01** — `index.php`: drop `if (is_siteadmin()) { admin_externalpage_setup('local_lernhive_library_catalog') }` dual-mode branch. Library is a content creation tool, not a site configuration page, so it always renders with the `standard` pagelayout and the LernHive Plugin Shell — regardless of whether the visitor is a siteadmin or a course creator with the import capability. `admin_externalpage_setup()` forced `pagelayout='admin'`, which (since `theme_lernhive` 0.9.34 / 0.9.36) layers the Moodle admin secondary tab bar (General | Users | Courses | …) on top of the catalog. The plugin still registers `local_lernhive_library_catalog` in the admin tree via `settings.php` so admins can discover it via the site-admin search, but the page is unconditionally `standard` layout. Removed now-unused `require_once($CFG->libdir . '/adminlib.php')`.

## R1 status: shipped

R1 scaffold is deployed to `dev.lernhive.de` as of 2026-04-11 (commit
`9475cf5`, version `2026041002`). The catalog page renders the empty
state correctly and is reachable from the ContentHub Library card and
the admin tree. No catalog entries are returned yet — the backend source
is not connected.

R1.1 layout fix is shipped as version `2026041103` (`0.1.1`), keeping the
same functional scope while enforcing standard pagelayout for all users.

## Quality follow-up (repo, not deployed yet)

- **LH-LIB-QA-01** — PHPUnit coverage extended to
  `output/catalog_page::export_for_template()` via
  `tests/catalog_page_test.php` (empty state + seeded entries + labels).
- **LH-LIB-QA-02** — Behat smoke scenario added via
  `tests/behat/library_page.feature` to assert admin-tree access and
  R1 empty-state rendering.
- **LH-LIB-QA-03** — Catalog seed contract hardened in `classes/catalog.php`:
  constructor now rejects non-`catalog_entry` elements with a fast-fail
  `coding_exception`; covered by `tests/catalog_test.php`.
- **LH-LIB-QA-04** — `catalog_entry` contract hardened in
  `classes/catalog_entry.php`: required fields (`id`, `title`, `version`,
  `language`) must not be blank and `updated` must be non-negative;
  covered by data-provider cases in `tests/catalog_test.php`.
- **LH-LIB-QA-05** — Template-context consistency hardening:
  `output/catalog_page::export_for_template()` now derives `empty` from
  exported `entries`, and `catalog_entry` normalises display language with
  `trim + strtoupper`; covered by `tests/catalog_page_test.php` and
  `tests/catalog_test.php`.

## R2 progress (repo, not deployed yet)

- **LH-LIB-R2-01** — Managed catalog feed phase 1 delivered:
  `classes/catalog.php` now parses a configured JSON manifest
  (`local_lernhive_library/catalog_manifest_json`) into `catalog_entry`
  objects; supports top-level array or `{ "entries": [...] }`, tolerates
  unix timestamps and parseable date strings for `updated`, and skips
  invalid rows fail-closed. Admin setting is exposed in `settings.php`.
- **LH-LIB-R2-02** — Copy-template handoff support delivered:
  optional `sourcecourseid` in catalog entries is now parsed and validated,
  and `catalog::find_by_id()` supports downstream resolution by
  `local_lernhive_copy` template mode.
- **LH-LIB-R2-03** — Managed feed source abstraction delivered:
  catalog loading now uses `catalog_source` with dedicated source classes
  (`remote_catalog_source`, `manifest_catalog_source`) and shared parser
  (`catalog_manifest_parser`). Existing seeded-entry mode remains intact.
- **LH-LIB-R2-04** — Remote feed retrieval MVP delivered:
  plugin settings now provide `catalog_feed_url` and optional
  `catalog_feed_token`; remote feed is used as primary source in production,
  while local manifest remains fallback when no feed URL is configured.
  Remote fetch/parsing failures are fail-closed.
- **LH-LIB-R2-05** — Copy-template handoff path hardened:
  explicit PHPUnit scenarios now lock template-mode lookups used by
  `local_lernhive_copy`:
  - `find_by_id()` resolves mapped template ids
  - `has_source_course()` + `sourcecourseid` expose actionable mappings
  - unknown template ids fail closed (`null`)
  - lookup behaviour remains stable for remote-feed sourced entries

## Next execution plan (R2 phase 3 prep)

1. **LH-LIB-R2-06** — Prepare import-flow boundary (still no execution yet):
   define import service contracts and UI handoff points so phase 3 can wire
   Moodle backup/restore without breaking existing catalog UX.
2. **LH-LIB-R2-07** — Import UX + safety checks:
   add preflight validation and explicit confirmation flow before first
   executable import wiring.

## Open R1 issues

_None known._

## R2 backlog

- `.mbz` download + Moodle backup/restore import flow
- Version metadata: show available vs installed version per entry
- Safe update: import new `.mbz` version without destructive overwrite
- Update decision workflow (compare changelogs, confirm)
- Behat scenario: course creator imports a library course once import flow exists
