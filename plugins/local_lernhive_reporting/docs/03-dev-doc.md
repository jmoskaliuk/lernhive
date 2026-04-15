# local_lernhive_reporting — Developer Documentation

## Architecture

R1 implementation is intentionally lightweight and Moodle-native.

### Main files

- `index.php` — dashboard entry page
- `users.php` — users-in-course drilldown page
- `popular.php` — popular-courses drilldown page
- `completion.php` — completion drilldown page
- `classes/report_service.php` — KPI data access from Moodle core tables
- `classes/output/dashboard_page.php` — template context builder
- `classes/output/users_page.php` — users drilldown context builder
- `classes/output/popular_page.php` — popular drilldown context builder
- `classes/output/completion_page.php` — completion drilldown context builder
- `classes/output/renderer.php` — template rendering
- `templates/dashboard_page.mustache` — UI output
- `templates/users_page.mustache` — users drilldown UI
- `templates/popular_page.mustache` — popular drilldown UI
- `templates/completion_page.mustache` — completion drilldown UI
- `styles.css` — reporting-specific style layer aligned with the LernHive design system
- `db/access.php` — capability model
- `classes/privacy/provider.php` — null privacy provider

## Technical direction

- use Moodle APIs where possible
- use read-only SQL against core reporting/completion tables
- avoid custom schema in R1
- keep plugin logic independent from theme
- follow the LernHive plugin-shell visual pattern in templates (`lh-plugin-header`, `lh-plugin-infobar`, `lh-plugin-card`)

## Current data sources

- `{course}`
- `{enrol}`
- `{user_enrolments}`
- `{user}`
- `{course_completions}`

## Capability model

- `local/lernhive_reporting:view`
- cloned from `moodle/site:viewreports`

## Open technical follow-ups

- optional export strategy decision (R2)
