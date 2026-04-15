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

## Open R1 issues

_None known._

## R2 backlog

- Connect `catalog` to eLeDia's managed catalog backend
- `.mbz` download + Moodle backup/restore import flow
- Version metadata: show available vs installed version per entry
- Safe update: import new `.mbz` version without destructive overwrite
- Update decision workflow (compare changelogs, confirm)
- Behat scenario: course creator imports a library course once import flow exists
