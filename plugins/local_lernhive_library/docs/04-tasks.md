# local_lernhive_library — Tasks

## R1.1 status: shipped (0.1.1 — 2026-04-11)

- **LH-LIB-LAYOUT-01** — `index.php`: drop `if (is_siteadmin()) { admin_externalpage_setup('local_lernhive_library_catalog') }` dual-mode branch. Library is a content creation tool, not a site configuration page, so it always renders with the `standard` pagelayout and the LernHive Plugin Shell — regardless of whether the visitor is a siteadmin or a course creator with the import capability. `admin_externalpage_setup()` forced `pagelayout='admin'`, which (since `theme_lernhive` 0.9.34 / 0.9.36) layers the Moodle admin secondary tab bar (General | Users | Courses | …) on top of the catalog. The plugin still registers `local_lernhive_library_catalog` in the admin tree via `settings.php` so admins can discover it via the site-admin search, but the page is unconditionally `standard` layout. Removed now-unused `require_once($CFG->libdir . '/adminlib.php')`.

## R1 status: shipped

R1 scaffold is deployed to `dev.lernhive.de` as of 2026-04-11 (commit
`9475cf5`, version `2026041002`). The catalog page renders the empty
state correctly and is reachable from the ContentHub Library card and
the admin tree. No catalog entries are returned yet — the backend source
is not connected.

## Open R1 issues

_None known._

## R2 backlog

- Connect `catalog` to eLeDia's managed catalog backend
- `.mbz` download + Moodle backup/restore import flow
- Version metadata: show available vs installed version per entry
- Safe update: import new `.mbz` version without destructive overwrite
- Update decision workflow (compare changelogs, confirm)
- Behat scenario: course creator browses catalog and imports a course
- PHPUnit: extend coverage to catalog_page renderable
