# local_lernhive_onboarding — Developer Documentation

## Architecture note

Consumer plugin for the LernHive level system. Delivers the trainer/participant learning path: a dashboard banner, a level-aware tours dashboard, and a set of Moodle user-tour JSONs grouped into categories per level. All feature → level lookups go through `local_lernhive\feature\registry` (ADR-01 in `../../local_lernhive/docs/00-master.md`); this plugin does not own any level-mapping state.

## Technical direction

- Keep boundaries clean: this plugin is a **read-only consumer** of the feature registry. No local override table, no feature-to-level constants.
- Use Moodle core user-tours (`tool_usertours`) — do not invent a second tour engine.
- Prefer existing core strings; plugin-specific strings only for banner copy and category names.
- Keep the data model minimal: two tables (`local_lhonb_cats`, `local_lhonb_map`) — unchanged in 0.3.0. Feature addressability is added via a JSON key on the tour import path, not a new column.
- Document release-2 complexity (telemetry, dismiss state, per-cohort scoping) separately from R1.

## Current dependencies

- `local_lernhive >= 2026040321` — hard. Needed for `level_manager` and (0.3.0+) `feature\registry`.
- `tool_usertours` — Moodle core.

## Integration points

- **`local_lernhive\feature\registry`** — the registry is queried by `tour_manager::get_categories($userid)` and by `tour_importer::import_level($level)`. See the flow diagram below.
- **`local_lernhive\level_manager`** — still consulted for the user's current level to compute progress and to decide which next-level teaser to show.
- **`local_lernhive\event\feature_override_changed`** — listened to for tour-cache invalidation. An admin moves `mod_assign.create` from L2 to L1 → this plugin re-maps the assignments tour from the Level-2 pack to the Level-1 pack in-memory on the next dashboard load.
- **`core\hook\output\before_standard_top_of_body_html_generation`** — banner injection, unchanged from 0.2.0.
- **`local_lernhive_flavour`** *(LXP only)* — grants `local/lernhive_onboarding:receivelearningpath` to participant-type roles via flavor preset.

## Feature-addressable tour lookup (0.3.0, consumer of ADR-01)

### Tour JSON shape

Every tour JSON gets a new top-level key:

```json
{
  "name": "LernHive: Aufgabe erstellen",
  "description": "…",
  "lernhive_feature": "mod_assign.create",
  "pathmatch": "/course/modedit.php%",
  "steps": [ … ]
}
```

`tour_importer` reads this key during `import_level()` and stores it on the `local_lhonb_map` row as `feature_id` (new column in 0.3.0, nullable for backward compat).

### `tour_manager::get_categories($userid)` — new flow

```
1. Fetch user's current level from level_manager.
2. Fetch all local_lhonb_cats rows (all levels — we no longer filter by level column).
3. For each category, fetch its tour mappings (local_lhonb_map).
4. For each mapping:
     if feature_id IS NULL:
         fallback — use category.level from the old schema
     else:
         effective_level = registry::effective_level(feature_id)
         if effective_level > userlevel: skip
         if not registry::is_available_for_user(feature_id, userid): skip
5. Aggregate surviving tours per category; drop categories with zero visible tours.
6. Return in sortorder.
```

The old "tour is visible if your level ≥ category.level" rule is reduced to a fallback for un-migrated tours and will be removed in 0.4.0.

### Directory structure vs. runtime level

After ADR-01, `tours/level1/`, `tours/level2/`, … directories are **authoring convention only**. They express the *default* level of the contained tours (via each tour's `lernhive_feature` → registry default). An admin override can make a tour from `tours/level2/` appear on Level 1 without touching the filesystem.

### Migration of the existing Level-1 assignment tour

- Move `tours/level1/create_activities/01_assignment.json` → `tours/level2/assignments/01_create.json`.
- Add `"lernhive_feature": "mod_assign.create"` to the JSON.
- `db/upgrade.php` step at 0.3.0 savepoint: remove the old mapping in `local_lhonb_map`, insert the new mapping in the `assignments` category, seed the `assignments` category if missing.
- Tour content (steps) stays identical — same UX, new location.

## Data model changes in 0.3.0

```
local_lhonb_map + feature_id VARCHAR(128) NULL
                + index on feature_id (non-unique, many maps can point at the same feature)
```

`local_lhonb_cats.level` stays for backward compat but is ignored by the new lookup path.

## Testing strategy

- **Unit**: `tour_visibility_test.php` — fakes a `registry` with a controlled override map and asserts `tour_manager::get_categories()` returns exactly the expected set for a user at level N.
- **Unit**: `tour_importer_test.php` — exercises the `lernhive_feature` JSON key parsing and the upgrade-migration path for the assignment tour.
- **Integration**: `banner_gate_test.php` (already in place) — verify the banner still hides itself once Level 1 is complete under the new feature-driven lookup.
- **Behat**: `trainer_learning_path.feature` — trainer logs in, sees only the tours whose feature is available; an admin override flips one tour to another level, the tour follows without any reimport.

## Consumers outside this plugin

None — this plugin is itself a consumer. Theme-side rendering stays in `theme_lernhive` via tokens, no direct cross-plugin calls.
