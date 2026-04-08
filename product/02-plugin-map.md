# Plugin Map

## Implemented / already documented
- `local_lernhive`
- `theme_lernhive`

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
| `local_lernhive_contenthub` | local | R1 | content entry UI |
| `local_lernhive_copy` | local | R1 | course copy from existing courses and templates |
| `local_lernhive_library` | local | R1 | external content library import |
| `local_lernhive_discovery` | local | R1 for LXP | Explore feed and discovery |
| `local_lernhive_follow` | local | R1 for LXP | follow + bookmark |
| `local_lernhive_audience` | local | R1 | audiences and dynamic rules |
| `local_lernhive_notifications` | local | R1 | digest layer over Moodle notifications |
| `local_lernhive_reporting` | local | R1 | simplified report dashboards |
| `local_lernhive_skills` | local | R2 | UX layer for competencies and learning plans |

## Dependencies

- `theme_lernhive` should remain independently usable
- `local_lernhive` is the shared base for several LernHive plugins
- `local_lernhive_discovery` depends on:
  - `local_lernhive_follow`
  - `local_lernhive_audience`
  - `local_lernhive_notifications`
- `local_lernhive_contenthub` orchestrates:
  - `local_lernhive_copy`
  - `local_lernhive_library`
  - optional AI integration later

## Product-level relationship

- Launcher is for actions
- Navigation is for reaching content
- Context Helper is for what to do next
- Discovery belongs only to the LXP Flavour
