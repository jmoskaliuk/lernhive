# local_lernhive — Quality

## Quality goals
- terminology is consistent
- UX stays simple
- strings are reusable and localizable
- feature works on desktop and smaller screens
- no unnecessary duplication of Moodle core logic

## Checks
- accessibility basics
- responsive checks
- role/permission checks where relevant
- language string review
- PHPUnit coverage for registry/override/capability mapper integration (`tests/feature/registry_test.php`, `tests/feature/override_store_test.php`, `tests/capability_mapper_test.php`)
