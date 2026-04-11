# local_lernhive_onboarding — Master Document

**Plugin type:** local plugin
**Release target:** R1

## Purpose

Tours and level-linked onboarding journeys.

## Role in LernHive

This plugin is part of the LernHive ecosystem and should fit the core rules:
- Moodle stays the core
- English-first terminology
- use Moodle core strings wherever possible
- no business logic in the theme
- must stay understandable for UX, docs, marketing and sales

## Main dependencies
local_lernhive

## Main features
- tour catalog
- level-based progress
- Grow area
- admin override support — consumed from `local_lernhive`'s feature registry (see ADR-01 in `../../local_lernhive/docs/00-master.md`)

## Related architecture decisions

- **ADR-01 (local_lernhive) — Feature Registry & konfigurierbare Level-Rechte.** This plugin is a consumer: tour visibility depends on the registry's `effective_level` and `is_available_for_user` checks. See `level-tour-matrix.md` (v2) for the feature-to-level default proposal and `../../local_lernhive/docs/00-master.md` for the full ADR.

## DevFlow files
- 00-master.md
- 01-features.md
- 02-user-doc.md
- 03-dev-doc.md
- 04-tasks.md
- 05-quality.md
