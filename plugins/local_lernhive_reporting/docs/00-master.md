# local_lernhive_reporting — Master Document

**Plugin type:** local plugin  
**Release target:** R1  
**Current implementation status:** v0.1.0 scaffold (dashboard + 3 tiles + drilldowns)

## Purpose

Provide a simple, tile-based reporting dashboard on top of Moodle core reporting and completion data.

## Role in LernHive

`local_lernhive_reporting` implements the R1 reporting scope defined in product docs:
- no Moodle fork
- no custom reporting platform
- Moodle-first data reuse
- simple and guided UX
- clear separation between plugin logic and theme styling

## Release 1 scope

- 3 primary report tiles:
  1. users in selected course
  2. most popular courses
  3. course completion
- course filter
- lightweight drilldowns for popularity and completion

## Main dependencies

- `local_lernhive`
- Moodle core reporting/completion data model

## DevFlow files

- 00-master.md
- 01-features.md
- 02-user-doc.md
- 03-dev-doc.md
- 04-tasks.md
- 05-quality.md
