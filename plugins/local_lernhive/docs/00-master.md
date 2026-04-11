# local_lernhive — Master Document

**Plugin type:** local plugin
**Release target:** R1

## Purpose

Base plugin for levels, shared hooks and common LernHive helpers.

## Role in LernHive

This plugin is part of the LernHive ecosystem and should fit the core rules:
- Moodle stays the core
- English-first terminology
- use Moodle core strings wherever possible
- no business logic in the theme
- must stay understandable for UX, docs, marketing and sales

## Main dependencies
—

## Main features
- Level system (Explorer to Master)
- Feature registry — single source of truth for feature → level mapping, admin overrides, flavor presets *(ADR-01, planned for 0.3.0)*
- shared helper services
- common hooks and UI filtering
- base capability and role handling

## DevFlow files
- 00-master.md
- 01-features.md
- 02-user-doc.md
- 03-dev-doc.md
- 04-tasks.md
- 05-quality.md

## Architecture decision ADR-01 — Feature Registry & konfigurierbare Level-Rechte (2026-04-11)

**Decision.** Replace the hard-coded `capability_mapper::get_level_modules()` and `get_level_capabilities()` with a **feature registry** that (a) defines each LernHive feature with a default level, a required Moodle capability, and human-readable metadata, (b) supports per-site admin overrides of a feature's effective level, and (c) supports flavor-level presets that ship with `local_lernhive_flavour`. Consumers — `capability_mapper`, `local_lernhive_onboarding/tour_manager`, Trainer-UI affordances — all read through the registry instead of duplicating lookup logic.

### Why

1. **Matrix review-round 1 (2026-04-11)** surfaced that the feature → level mapping needs to be *per-site configurable*. Example: in the Schul-Flavor `create_users` must be available on Level 1, in the Academy-Flavor `grade:manage` should also be on Level 1 — both against the current default of „on Level 1 locked, unlocks later". A pure code constant cannot cover this.
2. **Tour follows right.** The Onboarding-Plugin needs to hide tours whose underlying feature is not available to the user (either because the user's level is too low *or* because the admin has deliberately turned the feature off). Today the tour lookup is based on a directory layout (`tours/levelN/<category>/*.json`), which assumes the mapping is immutable. That has to change: tours must be addressable by `feature_id`, not by directory level.
3. **Flavor bindings.** In the LXP-Flavor, course-creation and user-invite capabilities reach participants, not just trainers. The onboarding banner already gates on a capability (`local/lernhive_onboarding:receivelearningpath`), but the level-filter role (`lernhive_filter`) and the feature-to-level mapping do not support that the same feature can be „on Level 1" for trainers and „on Level 1" for participants simultaneously. A registry with a default + override mechanism makes this uniform instead of adding another branch per plugin.
4. **Single source of truth.** Today `capability_mapper` is the only place this mapping lives, but it is invisible to admins, tests, migration scripts and the UI. Pulling it into a registry with metadata gives us a natural place to render an admin settings page, write structured tests, and produce diagnostics ("Which features is this user blocked from? Why?").

### Consequences

- **New class** `local_lernhive\feature\registry` (or similar). Holds the canonical feature list as a `get_features(): array` of `feature_definition` objects keyed by `feature_id` (e.g. `mod_assign.create`, `core.grade.view`, `core.backup.course`). Each entry carries: default level, required capability string, category-default (for the tours pack), lang-string key, optional `flavor_hint`.
- **New overrides table** `local_lernhive_feature_overrides` with columns `feature_id`, `effective_level`, `enabled`, `timemodified`, `updated_by`. Empty table = pure defaults.
- **`capability_mapper` is rewritten as a consumer** of the registry. `get_allowed_modules($level)` and `get_prohibited_capabilities($level)` both become `registry`-backed queries that honor overrides. The `lernhive_filter` role-assignment mechanics stay unchanged — only the lookup changes.
- **Admin UI** — new settings-page under `Site administration → LernHive → Level configuration` that lists every registered feature as a row `Feature | Default-Level | Override | Required capability | Status`. Override = dropdown 1..5 or "disabled"; status = effective level + reason (default / override / flavor-preset).
- **Flavor presets.** `local_lernhive_flavour` gets a new hook `feature_registry::register_flavor_preset(flavor_id, [feature_id => override])`, called on flavor activation. On flavor change the preset is applied idempotently; manual admin overrides take precedence over presets.
- **Tours become feature-addressable.** Each Moodle user-tour JSON in `local_lernhive_onboarding/tours/` gains a top-level `lernhive_feature` key (e.g. `"lernhive_feature": "mod_assign.create"`). `tour_manager::get_categories($userid)` rebuilds the list by asking the registry for the effective level of each tour's feature; the directory `tours/level1/`, `tours/level2/` … becomes *authoring* convention only and no longer controls runtime visibility.
- **Visibility gate per user** — every tour lookup in `tour_manager` additionally calls `has_capability()` on the tour's required capability (in system context). If the user lacks the capability — no tour, not even hidden-progress tracking. This replaces the "tour is visible if your level >= N" heuristic with "tour is visible if you actually have the feature right now".
- **Migration of existing content.** The existing Level-1 assignment tour is moved from `tours/level1/create_activities/01_assignment.json` to `tours/level2/assignments/01_create.json`, and gets `"lernhive_feature": "mod_assign.create"`. `db/upgrade.php` in 0.3.0 rewires the category mapping on existing installations.
- **Tests.** The registry is unit-testable in isolation — no Moodle DB needed for the pure-function parts. Overrides + flavor presets need DB-backed tests. `capability_mapper_test.php` is retargeted from "hardcoded expected maps" to "registry-driven expectations".
- **Documentation.** Each feature entry carries its own lang-string key for description — admins see human text in the settings UI, not `mod_assign.create` technical IDs.

### Non-goals

- We do not introduce a *per-course* or *per-user* override layer. Overrides are global, site-wide. Per-user effects come purely from level and role assignment.
- We do not try to auto-detect installed modules at runtime. If `mod_bigbluebuttonbn` is missing, its feature entry stays registered but the tour pack gets skipped at seed time. The registry is a declarative config, not a plugin-discovery service.
- We do not version the registry itself as content. Upgrades happen through code (`registry` class) + override table migrations. No YAML, no JSON seed files for the registry core.

### Open questions (track in `04-tasks.md`)

1. Do we model features flat (`mod_assign.create`) or hierarchically (`mod_assign > create`, `mod_assign > grade`)? Impacts the admin UI layout.
2. Does the override UI live in `local_lernhive` or in `local_lernhive_onboarding`? `local_lernhive` wins because that is where `capability_mapper` already lives and the registry is the single source of truth — the onboarding UI only reads from it.
3. Flavor switching: should the preset be applied idempotently (every flavor activation writes the full override set) or diff-wise (only writes entries that are not already admin-overridden)? Leaning **diff-wise** to protect manual admin changes.

### Status

**Accepted.** Implementation begins in 0.3.0. Tracked as milestones `LH-CORE-FR-01` … `LH-CORE-FR-07` in `04-tasks.md`. Consumer work (tour migration, onboarding banner updates) lands in parallel in `local_lernhive_onboarding` 0.3.0.
