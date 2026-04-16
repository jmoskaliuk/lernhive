# local_lernhive_reporting — Quality

## Quality goals

- terms and labels stay consistent with product language
- dashboard stays simple and guided
- no duplication of Moodle core reporting logic
- responsive usability on desktop and smaller screens
- reporting values are explainable and reproducible

## Current test coverage

- `report_service_test` verifies:
  - active participant counting
  - completion metrics calculation
  - empty completion metrics for courses without participants
  - popular-course ranking order
  - completion table ranking and percentage values
  - role-aware selectable-course filtering
  - global reporting access visibility
- `output/export_links_test` verifies:
  - users/popular/completion drilldowns export CSV route links with `sesskey`
  - course-specific export link routing for users drilldown
- `export_endpoint_wiring_test` verifies:
  - `users.php`, `popular.php`, and `completion.php` keep `export=csv` wiring
  - export branches remain guarded by reporting capability and `require_sesskey()`

## Manual QA checklist

- course filter updates all three tiles consistently
- popular courses list matches enrolment reality
- completion drilldown values are plausible (completed <= participants)
- capability guard blocks unauthorized access
- layout remains readable on narrow screens
- reporting pages follow the same design-system shell structure as other LernHive plugins
- CSV exports download correctly for users, popular, and completion drilldowns
- export links include `sesskey` and reject invalid/missing sesskey requests

## Known risks

- role-based course visibility may need tighter rules
- completion semantics depend on Moodle completion configuration in each course
- export snapshots currently use fixed row limits (users 200; popular/completion 25)
