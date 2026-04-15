# local_lernhive_launcher — Developer Documentation

## Architecture note
Global action launcher that orchestrates access to existing Moodle and LernHive flows.

## Technical direction
- keep boundaries clean
- use Moodle APIs where possible
- prefer existing core strings over plugin-specific duplicates
- keep data model minimal for release 1
- document release 2 complexity separately
- treat the launcher primarily as orchestration and permission-aware routing

## Current dependencies
local_lernhive

Optional sibling integrations:
- local_lernhive_contenthub
- local_lernhive_reporting
- local_lernhive_discovery

## Integration points
- Moodle core APIs
- LernHive shared services as needed
- theme integration only for styling, not for business logic

## Implementation direction

### Functional responsibility
- determine which actions should be visible for the current user
- provide launcher metadata such as action id, label, description, icon, and target
- route to the target URL or flow
- avoid owning the business workflow behind each destination

### Non-responsibilities
- no implementation of content creation workflows inside this plugin
- no duplication of `ContentHub`, `Snack`, or `Community` logic
- no replacement of Moodle navigation trees

### Suggested internal structure
- capability-aware action registry or provider layer
- renderer and template layer for the visible launcher UI
- minimal integration hook for a global launcher trigger
- local language strings only for launcher-specific wording

## Expected API touchpoints
- Moodle capability checks
- Moodle URL routing and page context handling
- Moodle output rendering APIs
- plugin availability checks for optional LernHive integrations

## String plan
- reuse Moodle core strings for generic verbs and objects where possible
- keep `Launcher` as a launcher-owned string
- reuse canonical LernHive terms from the owning plugin when the action refers to `ContentHub`, `Reports`, `Snack`, or `Community`
- avoid introducing synonym strings for the same action

## Release 1 technical constraints
- no persistent personalization data required
- no action analytics dependency required for initial rollout
- no hard dependency on the LXP Flavour for the base launcher

## Implemented visibility rules (0.1.2)

The Release 1 action provider currently evaluates visibility in this
order. Every action must return `null` from its builder rather than
fall through to a rendered dead-end entry.

### `Create course`
1. `local_lernhive\course_manager` must exist and `::is_enabled()` must
   return true.
2. The current user must have a `level_manager` record — beginner
   course creation is gated behind the existing LernHive level flow.
3. URL is resolved via `course_manager::get_create_course_url($userid)`.

### `ContentHub`
1. `local_lernhive_contenthub` must be installed (detected via
   `core_component`). The launcher does not declare a hard version
   dependency on it — the action silently disappears when the plugin
   is missing.
2. The current user must hold the system-context capability
   `local/lernhive_contenthub:view`. This mirrors the gate enforced
   inside `local/lernhive_contenthub/index.php` and keeps the launcher
   honest about who can actually enter the hub.
3. At least one downstream card must report `STATUS_AVAILABLE`. The
   launcher delegates this check to
   `local_lernhive_contenthub\card_registry::has_available_cards()` so
   the rule lives in the owning plugin. The method is guarded by
   `method_exists()` for forward compatibility with older ContentHub
   versions that predate the helper.
4. The `level_manager` gate intentionally does **not** apply here:
   `local/lernhive_contenthub:view` is cloned from
   `moodle/course:create`, so course creators and teachers see the
   launcher entry regardless of whether their level record has been
   written yet.

### `Reports`
1. `local_lernhive_reporting` must be installed (detected via
   `core_component`). If it is missing, the launcher hides the action.
2. The current user must hold the system-context capability
   `local/lernhive_reporting:view`. This mirrors
   `local_lernhive_reporting/index.php` and prevents dead-end entries.
3. URL resolves to `/local/lernhive_reporting/index.php`.

### `Create snack` / `Create community`
- Hidden until a concrete creation route is exposed by the owning
  discovery plugin. The current `resolve_local_plugin_url()` checks
  only file existence and must be tightened once the Snack/Community
  flows land.

## Cross-plugin integration contract

`local_lernhive_launcher` depends on the following public surface in
sibling plugins. Any change to these signatures is a breaking change
for the launcher and must be coordinated:

| Sibling | Public symbol | Used by |
|---|---|---|
| `local_lernhive` | `course_manager::is_enabled()` | `build_create_course_action()` |
| `local_lernhive` | `course_manager::get_create_course_url(int $userid)` | `build_create_course_action()` |
| `local_lernhive` | `level_manager::get_level_record(int $userid)` | `build_create_course_action()` |
| `local_lernhive_contenthub` | capability `local/lernhive_contenthub:view` | `build_contenthub_action()` |
| `local_lernhive_contenthub` | `card_registry::has_available_cards()` | `build_contenthub_action()` |
| `local_lernhive_reporting` | capability `local/lernhive_reporting:view` | `build_reports_action()` |
