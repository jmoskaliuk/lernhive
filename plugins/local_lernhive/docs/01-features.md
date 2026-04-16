# local_lernhive — Features

## Feature summary
Base plugin for the LernHive level system and cross-plugin helpers. Owns the feature registry, `capability_mapper`, the `lernhive_filter` role and the per-user level records.

## Planned feature set

- **Level system (Explorer → Creator → Pro → Expert → Master).** Five cumulative levels; level change triggers a capability re-apply.
- **Feature registry.** *(new in 0.3.0, ADR-01)* Single declarative source for feature → default-level → required-capability mapping, with a site-wide override table and flavor presets. Every feature is addressable by a stable `feature_id` (e.g. `mod_assign.create`, `core.grade.view`, `core.backup.course`).
- **Admin override UI.** *(new in 0.3.0, ADR-01)* Settings page under `Site administration → LernHive → Level configuration`. Admins see a table `Feature | Default-Level | Override | Required capability | Status`, change the effective level of a feature per site, or disable a feature outright.
- **Flavor presets.** *(new in 0.3.0, ADR-01)* `local_lernhive_flavour` registers per-flavor override packs at activation time. Manual admin overrides win over flavor presets — re-activating a flavor never silently overwrites an admin choice.
- **Capability mapper.** Translates the effective (registry + override) level map into `CAP_PROHIBIT` role overrides on the `lernhive_filter` role. After ADR-01 this is a pure consumer of the registry; it no longer owns the feature list.
- **Level banner.** Dashboard banner showing the current level and a link to the Onboarding Learning Path.
- **Shared helper services.** Small utilities consumed by other `local_lernhive_*` plugins: level lookup, level-change events, progress accessors.

## Acceptance direction

- Feature behaviour is explicit: every feature has exactly one `feature_id`, one default level, one required capability.
- Admin overrides must survive plugin upgrades — captured in a dedicated table, not in code constants.
- Flavor activation is idempotent and never destructive to manual overrides.
- Strings reuse Moodle core where possible; feature descriptions in the registry use plugin-scoped lang strings.
- UX stays simple: the admin UI lists features in their natural order (core → modules → grades → backup), not in alphabetical `feature_id` order.

## Feature-to-Level default map *(ADR-01, current proposal — may be adjusted)*

The full table lives in `local_lernhive_onboarding/docs/level-tour-matrix.md` (review v2). High-level summary:

- **Level 1 (Explorer).** Basic content modules (Resource, Page, Label, URL, Folder, announcement-only Forum), Kurs anlegen, Kurs-Einstellungen (Format + Abschlussverfolgung), Einschreiben, Nachrichten senden. `create_users` is a *configurable default-on* feature so the Schul-Flavor keeps it visible out of the box.
- **Level 2 (Creator).** Assignment, full forum, BigBlueButton. Plus `grade:view` for grading assignments (no gradebook-setup yet).
- **Level 3 (Pro).** Quiz, H5P, Lesson. Plus group management.
- **Level 4 (Expert).** Wiki, Glossary, Database, Workshop. Plus gradebook setup, reports, enrolment method configuration.
- **Level 5 (Master).** SCORM, LTI, Feedback, Choice, Survey, Book, IMS CP, Subsection. Plus backup/restore/import.

## Release note

- **0.3.0 target** — Feature registry core + override table + admin UI + consumer rewrite of `capability_mapper`. Ships together with `local_lernhive_onboarding` 0.3.0 which migrates the level-1 assignment tour and adds the new level-2..5 tour packs.
- **R1 envelope** — Everything above is in scope for Release 1 because it is a prerequisite for any flavor-specific deployment (Schul-Flavor uses the Schule preset, LXP-Flavor uses the LXP preset).
