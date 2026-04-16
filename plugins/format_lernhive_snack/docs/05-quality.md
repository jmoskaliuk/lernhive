# format_lernhive_snack — Quality

## Current checks
- PHP syntax lint for plugin PHP files
- repository search check for stale references to `theme_lernhive/snack_*`
- repository search check for moved Snack string ownership

## Manual verification checklist
- plugin is discovered by deploy script (`plugins/<component>/version.php` present)
- format component resolves as `format_lernhive_snack`
- language packs include required baseline format strings
- migrated template partial references resolve inside the format component namespace

## Known risks
- format scaffold is baseline-first; deeper course-format output overrides are still pending
- visual parity of Snack templates depends on future style ownership decisions

## Follow-up quality tasks
- run format plugin on a Moodle instance and verify course rendering in view/edit mode
- add PHPUnit/Behat coverage once rendering behavior is implementation-stable
