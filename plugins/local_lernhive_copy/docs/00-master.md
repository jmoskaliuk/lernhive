# local_lernhive_copy - Master Document

**Plugin type:** local plugin
**Release target:** R1 (iterative slices)

## Purpose

Provide a guided wizard to create a new course by copying an existing course,
reusing Moodle core backup/restore instead of custom clone logic.

## Role in LernHive

This plugin belongs to the ContentHub action layer and follows core LernHive
principles:
- Moodle stays the core (no fork, no custom copy engine)
- English-first terminology
- Moodle core strings reused where semantically correct
- no business logic in the theme
- calm, guided UX for course creators

## Current status (0.3.0)

- Course source path (`/local/lernhive_copy/index.php`) ships the Simple copy flow
- Expert mode is live and opens Moodle core copy options
- Template source path (`?source=template`) is wired to Library catalog templates
- Page always uses the standard LernHive shell layout (no admin layout override)

## Main dependencies

- `local_lernhive_contenthub`
- Moodle backup/restore APIs (`copy_helper`)
- optional integration: `local_lernhive_library` catalog backend for templates

## Main feature scope

- Simple mode (live): copy course with guided fields
- Copy without participants/progress as default (live)
- Expert mode toggle (live, hand-off to `/backup/copy.php`)
- Per-user default category (live via user preference)
- Template catalogue integration (live, two-step flow)

## DevFlow files

- `00-master.md`
- `01-features.md`
- `02-user-doc.md`
- `03-dev-doc.md`
- `04-tasks.md`
- `05-quality.md`
