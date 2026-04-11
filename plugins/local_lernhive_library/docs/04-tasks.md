# local_lernhive_library — Tasks

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
