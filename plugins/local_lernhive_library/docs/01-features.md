# local_lernhive_library — Features

## Feature summary
External managed content import for LernHive customers.

## Planned feature set
- library catalog
- managed .mbz source
- version metadata
- import workflow

## Current implementation slice (R2 phase 2)
- catalog entries are loaded from a configured remote managed feed URL
- local JSON manifest remains available as fallback when no feed URL is configured
- catalog remains read-only (import action still disabled)
- parser is strict and skips invalid rows instead of rendering broken cards
- optional `sourcecourseid` field enables template hand-off in `local_lernhive_copy`

## Next feature increment (R2 phase 3)
- import execution via Moodle backup/restore
- version comparison (available vs installed)
- guided update decision flow

## Acceptance direction
- feature behaviour should be explicit and understandable
- strings should reuse Moodle core when possible
- UX should stay simple and mobile-friendly
- flavour-specific wording should be used only if really necessary

## Release note
Target release: R2 (phase 2)
