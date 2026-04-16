# local_lernhive_onboarding - Developer Documentation

## Architecture status

### Current architecture (branch state)

- Progress model:
  - `tour_manager::get_level_progress($level, $userid)`
  - level-filtered categories from `local_lhonb_cats.level`, then per-tour registry visibility filtering
- Mapping model:
  - `local_lhonb_map(categoryid, tourid, feature_id, sortorder, timecreated)`
  - Level-1 pack is fully feature-addressable (10/10 JSON definitions carry `lernhive_feature`)
  - Level-2 runtime categories seeded and pack imported on install/upgrade
- Runtime start flow:
  - catalog link -> `starttour.php`
  - `starttour_flow::prepare_redirect_url()`
  - resolve `lh_start_url` via `start_url_resolver`
  - set replay preference via `\tool_usertours\tour::TOUR_REQUESTED_BY_USER`
  - clear completion preference via `\tool_usertours\tour::TOUR_LAST_COMPLETED_BY_USER`
  - clear legacy `_completed` / `_lastStep` fallback keys for backward compatibility
  - write one-shot session state (`local_lhonb_forced_tour_launch`) for deterministic launch
  - consume the state via `tool_usertours` server-side filter hook:
    - remove role filter for that one request
    - enforce exact-tour filter (`local_lernhive_onboarding\local\filter\forced_tour`)
  - queue one-shot JS completion overlay on forced starts (`tool_usertours/tourEnded`)
  - completion confirmation uses Moodle core modal stack (`core/modal_factory` save/cancel modal) with LernHive-scoped dialog class (`.lh-onboarding-completion-modal`)
- Tour step import normalization:
  - canonical Moodle mapping is `selector=0`, `block=1`, `unattached=2`
  - importer normalizes legacy swapped selector/unattached values
  - upgrade step `2026041501` backfills existing onboarding tour rows
  - upgrade step `2026041504` backfills stale Level-1 pathmatch values from JSON definitions
- Dashboard banner injection via output hook callback
- Visibility cache lifecycle:
  - in-process caches in `tour_manager` for categories and tours
  - cache reset via mapping writes and `feature_override_changed` event observer

### Planned architecture (0.3.x)

- Add chain metadata (`prereq`) and successor activation on tour end

## Data model

### Current

- `local_lhonb_cats`
- `local_lhonb_map`

### Next increment

- chain metadata and runtime successor activation

## Integration points

Current:
- `local_lernhive\level_manager` for user level retrieval
- `tool_usertours` as delivery engine
- `core\hook\output\before_standard_top_of_body_html_generation` for banner injection
- `tool_usertours\hook\before_serverside_filter_fetch` for one-request forced-tour launch wiring

Planned:
- tour-ended event/hook for chain successor activation

## Testing status

Implemented PHPUnit coverage:
- `tests/trainer_role_test.php`
- `tests/banner_gate_test.php`
- `tests/start_url_resolver_test.php`
- `tests/starttour_flow_test.php`
- `tests/sandbox_course_test.php`
- `tests/tour_importer_test.php`
- `tests/hook_callbacks_test.php`
- `tests/tour_visibility_test.php`

Coverage note:
- `tour_importer_test.php` now asserts that every Level-1 source tour JSON declares a non-empty `lernhive_feature` key (guards FR-05b against regression).

Open test gaps:
- Behat for sesskey and start-flow browser behavior
- Chain activation tests (`tour_chain_test.php`)
