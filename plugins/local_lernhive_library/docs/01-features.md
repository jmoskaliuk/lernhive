# local_lernhive_library — Features

## Feature summary
External managed content import for LernHive customers.

## Planned feature set
- library catalog
- managed .mbz source
- version metadata
- import workflow

## Current implementation slice (R2 phase 1)
- catalog entries can be loaded from a configured JSON manifest
- catalog remains read-only (import action still disabled)
- parser is strict and skips invalid rows instead of rendering broken cards

## Acceptance direction
- feature behaviour should be explicit and understandable
- strings should reuse Moodle core when possible
- UX should stay simple and mobile-friendly
- flavour-specific wording should be used only if really necessary

## Release note
Target release: R2 (phase 1)
