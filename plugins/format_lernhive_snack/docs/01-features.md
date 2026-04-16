# format_lernhive_snack — Features

## Purpose
Provide a dedicated Moodle course format for short-form Snack experiences.

## Product rule alignment
- Snack is a stable LernHive term
- Snack remains lightweight and guided
- theme renders shell/chrome, format renders course content surfaces

## Release 1 feature set
- format plugin scaffold with Moodle-required files
- format base class with section support
- format renderer for section title output
- migration of Snack partial templates from `theme_lernhive` to this plugin
- migration of Snack-specific strings from `theme_lernhive` to this plugin

## Expected Snack constraints
- wizard-oriented creation flow
- lightweight structure
- no drift into full course complexity by default

## Acceptance criteria
- `plugins/format_lernhive_snack/` is deployable through the existing deploy pipeline
- `theme_lernhive` no longer ships `snack_*.mustache`
- Snack partial templates resolve with `format_lernhive_snack/...` partial paths
- Snack-specific strings resolve from `format_lernhive_snack` language packs
