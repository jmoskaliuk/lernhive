# format_lernhive_snack — Developer Documentation

## Architecture note
Snack presentation belongs to a course format plugin, not the theme.

## Technical direction
- keep Moodle course-format boundaries clean
- keep shell/chrome in `theme_lernhive`
- keep Snack content presentation in `format_lernhive_snack`
- use Moodle course format APIs and renderer conventions

## Implemented baseline
- `version.php` defines component metadata (`format_lernhive_snack`)
- `lib.php` provides the format base class
- `format.php` delegates course rendering through the course-format output pipeline
- `classes/output/renderer.php` provides the format renderer
- `classes/privacy/provider.php` declares null-provider privacy metadata
- `lang/en` and `lang/de` provide Snack format strings
- `templates/snack_*.mustache` now live in the format plugin

## Migration note (from theme)
- moved from `theme_lernhive/templates/`:
  - `snack_shell.mustache`
  - `snack_header.mustache`
  - `snack_flow.mustache`
  - `snack_step.mustache`
- moved snack-specific strings from `theme_lernhive/lang/*` into this format

## Integration points
- Moodle core course format lifecycle (`course/view.php` -> `format.php`)
- Moodle course output classes and section renderer
- LernHive product docs for Snack constraints and terminology

## Known limitations (current baseline)
- template migration is complete, but deeper Snack-specific output class overrides are not implemented yet
- styles remain intentionally minimal until dedicated Snack UI refinements are specified
