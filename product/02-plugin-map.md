# Plugin Map

## Current documented plugin set

All plugins listed below are documented in this repository. Release labels indicate intended product scope, not implementation status.

## Planned core plugins

| Plugin | Type | Release | Purpose |
|---|---|---:|---|
| `local_lernhive` | local | R1 | levels, shared hooks, base helpers |
| `theme_lernhive` | theme | R1 | design system and responsive UX |
| `local_lernhive_flavour` | local | R1 | flavour setup and defaults |
| `local_lernhive_configuration` | local | R1 | config history and override tracking |
| `local_lernhive_launcher` | local | R1 | global action launcher |
| `local_lernhive_contexthelper` | local | R1 | contextual next actions |
| `local_lernhive_onboarding` | local | R1 | tours and level-linked onboarding |
| `local_lernhive_contenthub` | local | R1 | unified content entry UI |
| `local_lernhive_copy` | local | R1 | course copy from existing courses and templates |
| `local_lernhive_library` | local | R1 | external content library import |
| `local_lernhive_discovery` | local | R1 for LXP | Explore feed and projections |
| `local_lernhive_follow` | local | R1 for LXP | Follow and Bookmark relationships |
| `local_lernhive_audience` | local | R1 | audiences and dynamic rules |
| `local_lernhive_notifications` | local | R1 | digest layer over Moodle core notifications |
| `local_lernhive_reporting` | local | R1 | simplified report dashboards |
| `local_lernhive_skills` | local | R2 | UX layer for competencies and learning plans |

## Planned course format plugins (per ADR-P01 in 01-architecture.md)

| Plugin | Type | Release | Purpose |
|---|---|---:|---|
| `format_lernhive_snack` | format | 0.10.0 | Snack experience rendering (10–30 min short form); migrates from `theme_lernhive/templates/snack_*.mustache` |
| `format_lernhive_community` | format | 0.11.0 | Community feed rendering as a Moodle course format (LXP) |
| `format_lernhive_classic` | format | optional | Fallback classic-course format only if `format_topics` proves insufficient |

## Dependencies

- `theme_lernhive` should remain independently usable
- `local_lernhive` is the shared base for several LernHive plugins
- `local_lernhive_discovery` integrates with:
  - `local_lernhive_follow`
  - `local_lernhive_audience`
  - `local_lernhive_notifications`
- `local_lernhive_contenthub` orchestrates:
  - `local_lernhive_copy`
  - `local_lernhive_library`
  - optional AI integration later
- `local_lernhive_audience` builds on Moodle groups, cohorts, profile fields, and activity data
- `local_lernhive_notifications` reuses Moodle core providers, channels, defaults, and preferences

## Product-level relationship

- Launcher is for actions
- Navigation is for reaching content
- Context Helper is for what to do next
- Explore belongs only to the LXP Flavour
- Follow uses a star icon in the UX
