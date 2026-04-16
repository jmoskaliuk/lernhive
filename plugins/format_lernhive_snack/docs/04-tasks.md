# format_lernhive_snack — Tasks

## Completed
- scaffolded `format_lernhive_snack` as a Moodle course format plugin
- migrated `snack_*.mustache` from `theme_lernhive` to `format_lernhive_snack`
- migrated snack-specific strings from `theme_lernhive` language files

## Next tasks
- define and implement Snack-specific output overrides (`classes/output/courseformat/...`) where needed
- move any future Snack-only styling from theme partials into this format plugin
- align format-level rendering behavior with final Snack UX decisions for Release 1
- add integration tests once Snack format rendering behavior is finalized

## Open questions
- which parts of the current Snack surface should be rendered as format output classes vs template-only partials
- whether section behavior should be further constrained for Snack courses (for example, simplified section handling)
- how much Snack-specific UI should remain theme-driven versus format-driven in Release 1
