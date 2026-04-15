# local_lernhive_onboarding - Developer Documentation

## Architecture status

### Current architecture (branch state)

- Progress model:
  - `tour_manager::get_level_progress($level, $userid)`
  - level-filtered categories from `local_lhonb_cats.level`
- Mapping model:
  - `local_lhonb_map(categoryid, tourid, feature_id, sortorder, timecreated)`
- Runtime start flow:
  - catalog link -> `starttour.php`
  - `starttour_flow::prepare_redirect_url()`
  - resolve `lh_start_url` via `start_url_resolver`
  - set `tool_usertours_{id}_requested = 1`
  - clear `_completed` and `_lastStep`
- Tour step import normalization:
  - canonical Moodle mapping is `selector=0`, `block=1`, `unattached=2`
  - importer normalizes legacy swapped selector/unattached values
  - upgrade step `2026041501` backfills existing onboarding tour rows
- Dashboard banner injection via output hook callback

### Planned architecture (0.3.x)

- Migrate category visibility from pure `category.level` to registry-aware per-tour filtering
- Add chain metadata (`prereq`) and successor activation on tour end

## Data model

### Current

- `local_lhonb_cats`
- `local_lhonb_map`

### Next increment

- registry-aware visibility in `tour_manager` using `feature_id` when present
- fallback path for legacy mappings with null `feature_id`

## Integration points

Current:
- `local_lernhive\level_manager` for user level retrieval
- `tool_usertours` as delivery engine
- `core\hook\output\before_standard_top_of_body_html_generation` for banner injection

Planned:
- `local_lernhive\feature\registry` as visibility source
- `local_lernhive\event\feature_override_changed` for cache invalidation
- tour-ended event/hook for chain successor activation

## Testing status

Implemented PHPUnit coverage:
- `tests/trainer_role_test.php`
- `tests/banner_gate_test.php`
- `tests/start_url_resolver_test.php`
- `tests/starttour_flow_test.php`
- `tests/sandbox_course_test.php`
- `tests/tour_importer_test.php`

Open test gaps:
- Behat for sesskey and start-flow browser behavior
- Chain activation tests (`tour_chain_test.php`)
- Registry-driven visibility test (`tour_visibility_test.php`)
