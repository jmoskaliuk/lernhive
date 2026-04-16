# local_lernhive_onboarding - Quality

## Quality goals

- predictable, deterministic start-tour behavior
- stable and explainable visibility rules
- no duplicated core-tour engine logic
- role/capability gating is auditable
- onboarding UX remains simple on desktop and mobile

## Current automated coverage

PHPUnit:
- `trainer_role_test.php`
- `banner_gate_test.php`
- `start_url_resolver_test.php`
- `starttour_flow_test.php`
- `sandbox_course_test.php`
- `tour_importer_test.php`
- `hook_callbacks_test.php`

## Manual checks

- verify banner only appears on dashboard and only for eligible users
- verify each start button redirects to intended target URL
- verify completed tours replay from step 1 after restart
- verify step progression after welcome step (selector targets resolve, next-step flow works)
- verify completion overlay appears after catalog-started tour end and both actions work
- verify completion dialog is design-conform (`Stay here` + `Back to onboarding`, no modal close `X`, no global focus ring around dialog container)
- verify enrolment tours match on their redirected pages (`/user/index.php%`, `/enrol/instances.php%`)
- verify sandbox course exists and stays hidden by default

## Known gaps

- missing Behat coverage for sesskey/start flow and chaining behavior
- no automated UI regression checks for tour overview rendering
- registry-driven visibility tests still pending until FR-03
- no full runtime E2E assertion yet that each mapped `feature_id` resolves to an effective registry visibility gate

## Release gate (0.3.0)

- FR-01/FR-02 merged with upgrade-safe migration
- registry visibility path implemented and tested
- Level-2 pack wired and smoke-tested
- start-flow Behat baseline in place
