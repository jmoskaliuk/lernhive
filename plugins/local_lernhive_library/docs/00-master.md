# local_lernhive_library — Master Document

**Plugin type:** local plugin
**Release target:** R2 (phase 2)

## Purpose

Managed catalog access and staged import workflow for LernHive customers.

## Role in LernHive

This plugin is part of the LernHive ecosystem and should fit the core rules:
- Moodle stays the core
- English-first terminology
- use Moodle core strings wherever possible
- no business logic in the theme
- must stay understandable for UX, docs, marketing and sales

## Main dependencies
local_lernhive_contenthub

## Integration notes

- catalog entries can optionally expose `sourcecourseid`
- `local_lernhive_copy` consumes that mapping for template-based copy flows
- Library remains read-only in this phase (no import execution yet)

## Main features
- library catalog
- managed .mbz source
- version metadata
- import workflow

## Current status
- R2 phase 2 is implemented with remote managed feed as primary source
- local JSON manifest remains as fallback source when no feed URL is configured
- import execution remains planned for a later R2 phase

## DevFlow files
- 00-master.md
- 01-features.md
- 02-user-doc.md
- 03-dev-doc.md
- 04-tasks.md
- 05-quality.md
