# local_lernhive_reporting — Tasks

## Completed

- [x] create Moodle plugin scaffold (`version.php`, `settings.php`, `db/access.php`, privacy)
- [x] implement dashboard page with 3 R1 tiles
- [x] add course filter and drilldown tables
- [x] add initial PHPUnit coverage for KPI service
- [x] refine role-aware course visibility (capability-based course filtering)
- [x] add dedicated drilldown pages (`users.php`, `popular.php`, `completion.php`)
- [x] extend PHPUnit coverage for ranking and visibility behavior
- [x] add explicit UX empty states for no participants/no completion data
- [x] strengthen global reporting visibility (show all courses for global roles)
- [x] align dashboard and drilldowns to the LernHive design-system shell and card patterns
- [x] add lightweight CSV export actions for users/popular/completion drilldowns (sesskey-protected)
- [x] add automated tests for export endpoint security and routing

## Next tasks

- [ ] review row limits for large instances (users: 200, popular/completion: 25)
- [ ] decide if completion export should include only top list or an optional selected-course summary block

## Open questions

- Should completion export remain a top-list snapshot or include selected-course summary rows by default?
