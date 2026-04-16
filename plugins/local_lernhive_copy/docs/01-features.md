# local_lernhive_copy - Features

## Feature summary

Wizard for copying courses from existing Moodle content, launched from
ContentHub. The plugin orchestrates input and permission checks, then hands off
copy execution to Moodle core.

## Delivered feature set (0.3.0)

- Course source path with working Simple copy form
- Fields for source course, target metadata, visibility, dates, and optional idnumber
- "Include participants and progress" toggle (default: no)
- Asynchronous hand-off to Moodle `copy_helper` + redirect to copy progress page
- Explicit clean-copy callout for the userdata default
- Expert mode that opens Moodle core copy options
- Template source path with Library-catalog template picker
- Per-user default target category preference

## Planned feature set

- Template import execution from managed `.mbz` bundles (Library-owned backend)
- Additional Behat/PHPUnit breadth for edge cases and permissions

## Acceptance direction

- Feature behavior is explicit and explainable
- Strings reuse Moodle core where semantically correct
- UX stays simple, guided, and mobile-friendly
- No duplicate copy logic outside Moodle core backup/restore
- Terminology remains stable: "Copy", "Template", "ContentHub"

## Release note

Target product scope: R1 (iterative slices)
