# local_lernhive — Developer Documentation

## Architecture note

Base plugin for the LernHive level system. Owns the feature registry (ADR-01), `capability_mapper`, the `lernhive_filter` role, and per-user level records. After ADR-01, the plugin is the **single source of truth** for "which feature lives on which level, and who can see it".

## Technical direction

- Keep plugin boundaries clean: consumers (`local_lernhive_onboarding`, theme elements, flavor packs) read through the registry and never duplicate feature lists.
- Use Moodle APIs where possible; settle on the hook API for cross-plugin events.
- Prefer existing core strings over plugin-specific duplicates.
- Keep the data model minimal: one record per user in `local_lernhive_levels`, one row per overridden feature in `local_lernhive_feature_overrides`.
- Document release-2 complexity (per-course overrides, per-cohort scoping, telemetry) separately — **not** in R1.

## Current dependencies

—

## Integration points

- Moodle core APIs (Roles, Capabilities, Events, Hooks, Settings, DB/XMLDB).
- LernHive consumer plugins:
  - `local_lernhive_onboarding` — reads the registry for tour visibility and level-gated tour packs.
  - `local_lernhive_flavour` — registers flavor presets that seed the override table on flavor activation.
  - `theme_lernhive` — consumes level info for banner styling only, no business logic.

## Feature Registry — architecture sketch (ADR-01, planned for 0.3.0)

### Class layout

```
local_lernhive/classes/feature/
├── definition.php     # value object: feature_id, default_level, required_capability, lang_key, category_hint, flavor_hint
├── registry.php       # canonical list + override lookup + flavor-preset application
└── override_store.php # DB adapter for local_lernhive_feature_overrides
```

`registry::get_features(): array<string, definition>` returns the canonical list. `registry::effective_level(string $feature_id): int` folds defaults + overrides and returns the level a user must reach to unlock the feature.

### Canonical feature IDs (R1)

Authoritative source lives in code:

- `local_lernhive/classes/feature/registry.php` → `registry::build_features()`

Docs deliberately keep only a summary. Any change to feature IDs, default levels, capability bindings, category hints or flavour hints must be made in `registry.php` first, then reflected in docs/tasks as a changelog note.

### Override storage

New table `local_lernhive_feature_overrides`:

```
| id           | BIGINT(10)   | PK, auto-increment                              |
| feature_id   | VARCHAR(128) | NOT NULL, unique                                |
| override_level | TINYINT(1) | NULL — 1..5, NULL means "disabled"              |
| source       | VARCHAR(32)  | NOT NULL — 'admin' | 'flavor_preset'            |
| flavor_id    | VARCHAR(64)  | NULL — set if source='flavor_preset'            |
| timemodified | BIGINT(10)   | NOT NULL                                        |
| updated_by   | BIGINT(10)   | NULL — userid of admin, NULL for flavor presets |
```

Precedence on read: `admin` wins over `flavor_preset`. Flavor preset writes use `INSERT ... ON CONFLICT DO NOTHING` so a manual admin override is never silently overwritten.

### Apply-level pipeline (after ADR-01)

```
level_manager::set_level($userid, $level)
  └── capability_mapper::apply_level($userid, $level)
        ├── registry::get_features()
        ├── for each feature:
        │     effective = registry::effective_level(feature_id)
        │     if effective <= $level:  unassign_capability(...)  on lernhive_filter role
        │     else:                    assign_capability(CAP_PROHIBIT, ...) on lernhive_filter role
        └── accesslib cache flush happens inside assign_capability()
```

### Events

`local_lernhive\event\feature_override_changed` — fired on any insert/update/delete in `local_lernhive_feature_overrides`. Payload: `feature_id`, `old_level`, `new_level`, `source`. Consumers (onboarding tour cache, UI badges) listen and invalidate local state.

### Testing strategy

- **Unit**: `registry_test.php` — pure-function coverage of `effective_level` against a fixture override map.
- **Integration**: `override_store_test.php` — exercises precedence rules (admin vs. flavor), idempotency of flavor-preset application, upgrade-safe seeding.
- **End-to-end**: `capability_mapper_test.php` — user at level N, verify `lernhive_filter` role has exactly the expected prohibit set after an admin moves `core.user.create` from L1 to L3.
- **Onboarding-side**: `tour_visibility_test.php` in `local_lernhive_onboarding` — asserts a tour with `lernhive_feature: mod_assign.create` becomes invisible when the admin disables that feature or pushes it above the user's level.

## Consumers outside `local_lernhive`

- **`local_lernhive_onboarding`.** Tour JSONs gain a `lernhive_feature` key. `tour_manager::get_category_tours($categoryid, $level)` filters mappings via `registry::effective_level()` and keeps null legacy mappings visible. Directory structure `tours/levelN/<category>/` remains an authoring convention only.
- **`local_lernhive_flavour`.** On flavor activation, calls `registry::apply_flavor_preset($flavor_id, $overrides)`. Shipping presets for R1: `flavor_schule`, `flavor_lxp`, `flavor_academy`.
- **`theme_lernhive`.** Reads current level and effective feature list only for UI hints (e.g., locked-feature affordances in the activity chooser). Never writes.
