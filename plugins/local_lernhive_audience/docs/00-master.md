# local_lernhive_audience — Master Document

**Plugin type:** local plugin
**Release target:** R1

## Purpose

Audience abstraction and simple rule engine built on Moodle structures.

## Role in LernHive

This plugin is part of the LernHive ecosystem and should fit the core rules:
- Moodle stays the core
- English-first terminology
- use Moodle core strings wherever possible
- no business logic in the theme
- must stay understandable for UX, docs, marketing and sales

## Main dependencies
- `local_lernhive`
- Moodle groups, cohorts, enrolments, profile fields, and activity data

## Main features
- static audiences
- dynamic audiences
- simple rule logic with AND / OR
- time-based, activity-based, and profile-based conditions

## Release scope

### Release 1
- Audience builds on Moodle structures rather than replacing them
- Audience UX stays simple and list-based
- rule engine supports the initial rule types only:
  - time-based
  - activity-based
  - profile-based
- rules remain explainable and implementation-ready

### Release 2
- Circle UX refinement belongs to Release 2
- richer visual rule composition belongs to Release 2
- Release 2 work must not complicate the simple Release 1 baseline silently

## DevFlow files
- 00-master.md
- 01-features.md
- 02-user-doc.md
- 03-dev-doc.md
- 04-tasks.md
- 05-quality.md
