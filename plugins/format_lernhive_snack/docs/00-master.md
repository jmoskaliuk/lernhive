# format_lernhive_snack — Master Document

**Plugin type:** course format plugin (`format`)
**Release target:** R1 (technical boundary alignment)

## Purpose

Own the short-form Snack course presentation as a Moodle course format, instead of keeping Snack rendering inside `theme_lernhive`.

## Role in LernHive

This plugin is part of the LernHive ecosystem and should fit the core rules:
- Moodle stays the core
- English-first terminology
- use Moodle core strings wherever possible
- no business logic in the theme
- must stay understandable for UX, docs, marketing and sales

## Main dependencies
- Moodle course format APIs (`core_courseformat`)
- Moodle course structure (courses, sections, activities)
- LernHive terminology and UX guidance from `product/`

## Main features
- Snack course format base class (`format_lernhive_snack`)
- format renderer for section title rendering
- ownership of Snack template partials (`snack_*.mustache`)
- Snack-specific language strings (previously in `theme_lernhive`)

## Scope boundary

### In scope
- course-format-level Snack presentation
- format-level templates and strings
- format-level styling hooks (`styles.css`)

### Out of scope
- global app shell chrome (sidebar, top header, dock) — belongs to `theme_lernhive`
- Follow/Bookmark domain logic — belongs to dedicated functional plugins
- Explore feed ranking and projections — belongs to `local_lernhive_discovery`

## DevFlow files
- 00-master.md
- 01-features.md
- 02-user-doc.md
- 03-dev-doc.md
- 04-tasks.md
- 05-quality.md
