# local_lernhive_onboarding — Developer Documentation

## Architecture note

Consumer plugin for the LernHive level system. Delivers the trainer/participant learning path: a dashboard banner, a level-aware tours dashboard, and a set of Moodle user-tour JSONs grouped into categories per level. All feature → level lookups go through `local_lernhive\feature\registry` (ADR-01 in `../../local_lernhive/docs/00-master.md`); this plugin does not own any level-mapping state.

## Technical direction

- Keep boundaries clean: this plugin is a **read-only consumer** of the feature registry. No local override table, no feature-to-level constants.
- Use Moodle core user-tours (`tool_usertours`) — do not invent a second tour engine.
- **Respect the single-page rule of `tool_usertours`.** Every tour binds exactly one `pathmatch` and plays on exactly one URL. Multi-page features are modelled as chains of single-page tours (see "Tour chaining" below). This is not a LernHive invention — it's how Moodle core itself ships tours (verified against `311_activity_information_*`, `40_tour_navigation_course_*`, `40_tour_navigation_mycourse`: all single-`pathmatch`, all `shipped_tour:true`).
- Prefer existing core strings; plugin-specific strings only for banner copy and category names.
- Keep the data model minimal: two tables (`local_lhonb_cats`, `local_lhonb_map`) — unchanged in 0.3.0. Feature addressability is added via a JSON key on the tour import path, not a new column. Tour chaining metadata (`start_url`, `prereq`) rides along in `tool_usertours_tours.configdata` — no new columns.
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

## Deterministic tour start (0.3.0)

### Problem

`tool_usertours` is a passive engine: it auto-plays a matching tour *when* the user happens to visit a URL that matches the tour's `pathmatch`. For the LernHive catalog UX we need the opposite direction — the user clicks "Start Tour" in the catalog and wants to *be taken to* the right page, where the tour then plays. The 0.2.x `starttour.php` derived the redirect URL from `rtrim($pathmatch, '%')`, which is fragile: `pathmatch` is a filter pattern, not a URL, and many target pages need query parameters (`?id={USERID}`, `?id={DEMOCOURSEID}`, `?category=…`) that a wildcard strip cannot recover.

### Solution shape

Every tour JSON grows an optional `start_url` field:

```json
{
  "name": "LernHive: Nutzer/in anlegen",
  "pathmatch": "/user/editadvanced.php%",
  "start_url": "/user/editadvanced.php?id={USERID}",
  ...
}
```

`tour_importer::import_tour()` merges this into the tour's `configdata` as `lh_start_url` — no schema change:

```json
{"filtervalues":{"role":["editingteacher"]},"lh_start_url":"/user/editadvanced.php?id={USERID}"}
```

### Placeholder resolver

A new class `local_lernhive_onboarding\start_url_resolver` substitutes runtime values:

| Placeholder | Value |
|---|---|
| `{USERID}` | Current `$USER->id` |
| `{SYSCONTEXTID}` | `context_system::instance()->id` |
| `{SITEID}` | `$SITE->id` (Frontpage course) |
| `{DEMOCOURSEID}` | `get_config('local_lernhive_onboarding', 'democourseid')` — the onboarding sandbox course ID |

Resolver is pure, unit-tested, returns a `moodle_url`. Unknown placeholders stay as literal text (safe default for forward compatibility with tours that assume placeholders a future release will add).

### `starttour.php` new flow

1. `required_param('tourid', PARAM_INT)` + sesskey check
2. Load tour record, decode `configdata`, pull `lh_start_url`
3. If `lh_start_url` is empty → fallback to `rtrim($tour->pathmatch, '%')` (keeps 0.2.x behaviour for un-migrated tours)
4. Resolve placeholders via `start_url_resolver::resolve()`
5. **Set `tool_usertours_{id}_requested = 1`** — this is the Moodle-native "force replay" flag, the same one the admin "Reset Tour on this page" button sets. Works even for tours the user has already completed.
6. Unset `tool_usertours_{id}_completed`
7. Unset `tool_usertours_{id}_lastStep`
8. `redirect($resolvedurl)`

On the target page, tool_usertours sees the matching tour, sees `_requested=1`, plays from step 1. We never touch the tour player's internals, and we never redirect mid-tour.

## Tour chaining (0.3.0)

### Authoring model

Multi-page features are authored as N single-page tours inside the same category, linked by an optional `prereq` field:

```json
{
  "name": "LernHive: Kurs anlegen – Schritt 2 – Einstellungen",
  "pathmatch": "/course/edit.php%",
  "start_url": "/course/edit.php?category={DEMOCATID}",
  "prereq": "LernHive: Kurs anlegen – Schritt 1 – Katalog öffnen",
  ...
}
```

`prereq` is a tour **name** (same namespace as `tool_usertours_tours.name`), resolved at runtime to a tour ID. Like `start_url`, `prereq` is persisted into `configdata` as `lh_prereq_tour_id` at import time (after name→id resolution, so the filesystem authoring key stays human-readable).

### Activation model

Chains are **head-primed**: `starttour.php` sets `_requested=1` only on the head tour of the chain (the one without a `prereq`). Follow-up tours stay dormant until their predecessor completes. This avoids the "user casually visits course edit page before finishing step 1 and gets tour B popping up unexpectedly" problem.

A tour-end observer (`\tool_usertours\event\tour_ended`, or in 5.x the `\core_user_tours\hook\after_tour_ended` hook — whichever is stable) runs `tour_manager::activate_successors($tourid, $userid)`:

```
1. Query all tours where configdata->lh_prereq_tour_id = $tourid.
2. For each successor, set tool_usertours_{successorid}_requested = 1.
3. Clear tool_usertours_{successorid}_completed.
```

The successor then auto-plays next time the user visits its `pathmatch`. Crucially the successor's `start_url` is *also* advertised in the predecessor's `endtourlabel` as a "Next: …" button — the user can click straight through, or abandon and return later via the catalog, both paths work because `_requested` persists until consumed.

### Catalog UI representation

The tour overview renders a chain as a single "Learning Unit" with N step dots. Category progress already aggregates per-tour completion, so a chained unit's progress bar naturally shows partial completion without any new aggregation logic — the only change is that the catalog knows to display the N tours as a sequence instead of a flat list. The existing `tour_manager::get_level_progress()` stays untouched; a small post-processing pass in `tours.php` groups tours by `prereq` chain for rendering.

### One tour per page — proof from Moodle core

A sanity check against shipped Moodle core tours (`tool_usertours_tours.shipped_tour = true`) confirms the rule:

| Shipped tour file | `pathmatch` | Step count |
|---|---|---|
| `311_activity_information_activity_page_teacher.json` | `/mod/%/view.php%` | 1 |
| `311_activity_information_course_page_teacher.json` | `/course/view.php%` | 1 |
| `40_tour_navigation_course_teacher.json` | `/course/view.php%` | 3 (all on the same page) |
| `40_tour_navigation_mycourse.json` | `/my/courses.php` | 1 |
| `40_tour_navigation_course_student.json` | `/course/view.php%` | 1 |

No shipped tour ever spans two `pathmatch` values. The "Activity information" feature is conceptually one tour but is shipped as **two separate tour records** (one per target page). We follow the exact same pattern.

### Placeholder substitution and `{DEMOCOURSEID}`

Tours that need a course context must not run against real production courses (a novice trainer fumbling through a guided course-setup tour inside a live course is a support ticket waiting to happen). The install step provisions a hidden "Onboarding Sandbox" course, stores its ID in `config_plugins` under `local_lernhive_onboarding.democourseid`, and any tour JSON that needs a course context uses `{DEMOCOURSEID}` in its `start_url`. Deleted/broken democourse triggers a fresh seed on next `admin/cli/upgrade.php`.

## Data model changes in 0.3.0

```
local_lhonb_map + feature_id VARCHAR(128) NULL
                + index on feature_id (non-unique, many maps can point at the same feature)
```

`local_lhonb_cats.level` stays for backward compat but is ignored by the new lookup path.

## Testing strategy

- **Unit**: `tour_visibility_test.php` — fakes a `registry` with a controlled override map and asserts `tour_manager::get_categories()` returns exactly the expected set for a user at level N.
- **Unit**: `tour_importer_test.php` — exercises the `lernhive_feature` JSON key parsing and the upgrade-migration path for the assignment tour. Also covers merging `start_url` and `prereq` into `configdata` without dropping pre-existing `filtervalues`.
- **Unit**: `start_url_resolver_test.php` — one case per placeholder (`{USERID}`, `{SYSCONTEXTID}`, `{SITEID}`, `{DEMOCOURSEID}`), plus an "unknown placeholder stays literal" case and an "empty template falls back to pathmatch strip" case.
- **Unit**: `starttour_flow_test.php` — uses Moodle's test DB to assert that calling the start flow for a given tourid writes `_requested=1`, clears `_completed` and `_lastStep`, and returns the resolved URL. Run against a completed-tour fixture to prove replay works.
- **Unit**: `tour_chain_test.php` — given a two-tour chain (A → B), assert that (a) starting the chain primes only A's `_requested`, (b) calling `tour_manager::activate_successors(A, user)` primes B's `_requested` and clears B's `_completed`, (c) un-chained tours are untouched.
- **Integration**: `banner_gate_test.php` (already in place) — verify the banner still hides itself once Level 1 is complete under the new feature-driven lookup.
- **Behat**: `trainer_learning_path.feature` — trainer logs in, sees only the tours whose feature is available; an admin override flips one tour to another level, the tour follows without any reimport.
- **Behat**: `tour_start_from_catalog.feature` — trainer clicks "Start" on a catalog tour, lands on the resolved target URL, sees the first tour step render; completes step 1 of a chained tour, navigates to the next page via the end-tour button, sees step 2 start automatically.

## Consumers outside this plugin

None — this plugin is itself a consumer. Theme-side rendering stays in `theme_lernhive` via tokens, no direct cross-plugin calls.
