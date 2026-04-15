# local_lernhive_reporting — Tasks

## Completed

- [x] create Moodle plugin scaffold (`version.php`, `settings.php`, `db/access.php`, privacy)
- [x] implement dashboard page with 3 R1 tiles
- [x] add course filter and drilldown tables
- [x] add initial PHPUnit coverage for KPI service
- [x] refine role-aware course visibility (capability-based course filtering)
- [x] add dedicated drilldown pages (`users.php`, `popular.php`, `completion.php`)
- [x] extend PHPUnit coverage for ranking and visibility behavior

## Next tasks

- [ ] refine role-aware course visibility rules
- [ ] add explicit UX empty states for edge cases (no enrolments, no completion records)
- [ ] add additional PHPUnit tests for popular/completion table ranking
- [ ] define whether R1 needs CSV export or keeps on-screen only

## Open questions

- Should teachers only see courses where they have teaching roles, or all accessible courses?
- Is export explicitly out of R1 or a deferred option within R1?
