# local_lernhive_copy — Tasks

## R1 status: shipped

R1 scaffold is deployed to `dev.lernhive.de` as of 2026-04-11 (commit
`9475cf5`, version `2026041002`). The entry page renders correctly for
both source modes (course, template) and is reachable from the
ContentHub cards and the admin tree.

## Open R1 issues

_None known. Behat scenario for non-admin course-creator access is
deferred to R2 (depends on launcher plugin providing the navigation path)._

## R2 backlog

- Wire up Moodle core backup/restore API for the course-copy flow
- Implement category picker (personal default, recent, browse)
- "Copy without participants" option
- Expert mode (expose more backup settings)
- Behat scenario: course creator reaches wizard via launcher
- PHPUnit: extend coverage to wizard_page renderable
- Consider whether simple / expert mode split warrants a separate R2 doc
