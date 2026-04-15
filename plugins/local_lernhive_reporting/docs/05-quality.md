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

## Manual QA checklist

- course filter updates all three tiles consistently
- popular courses list matches enrolment reality
- completion drilldown values are plausible (completed <= participants)
- capability guard blocks unauthorized access
- layout remains readable on narrow screens

## Known risks

- role-based course visibility may need tighter rules
- completion semantics depend on Moodle completion configuration in each course
