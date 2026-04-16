# local_lernhive_onboarding - Tasks

## Done (0.2.x)

- [x] LH-ONB-01..06: trainer role, banner, hook wiring, base language strings, PHPUnit baseline
- [x] LH-ONB-START-01: `start_url_resolver`
- [x] LH-ONB-START-02: merge `start_url` into `configdata` (`lh_start_url`)
- [x] LH-ONB-START-03: `starttour.php` thin wrapper + `starttour_flow`
- [x] LH-ONB-START-04: start-flow PHPUnit coverage
- [x] LH-ONB-START-05: Level-1 start_url backfill + trainer category placeholder/setting
- [x] LH-ONB-START-06: onboarding sandbox course provisioning + uninstall-safe cleanup
- [x] LH-ONB-START-08 (partial infra move): announcements tour removed from Level-1 mapping

## Done (0.3.0-dev, current branch)

- [x] **LH-ONB-FR-01** Added `feature_id` (`VARCHAR(128)`, nullable) to `local_lhonb_map` with non-unique index `ix_featureid` (install + upgrade path).
- [x] **LH-ONB-FR-02** `tour_importer` now reads top-level `lernhive_feature` and persists it to `local_lhonb_map.feature_id` (including existing-tour remap path).
- [x] **LH-ONB-FR-05 (phase 1)** Backfilled `lernhive_feature` on every Level-1 tour JSON with a currently canonical registry mapping (7/10 files). Remaining fallback files require new registry feature definitions: `course_settings/01_format.json`, `course_settings/02_completion.json`, `communication/02_messaging.json`.
- [x] **LH-ONB-HOTFIX-01** Normalized Moodle tour `targettype` mapping (`selector=0`, `unattached=2`) in JSON fixtures, importer normalization, and DB upgrade migration for already imported tours.
- [x] **LH-ONB-HOTFIX-02** Align start/replay + completion preference keys with Moodle 5.x `tool_usertours\tour` constants (`tour_reset_time_*`, `tour_completion_time_*`) with legacy fallback support.

## In progress / next (0.3.x)

### Feature registry consumer track

- [ ] **LH-ONB-FR-03** Registry-aware visibility in `tour_manager` with fallback for null `feature_id`
- [ ] **LH-ONB-FR-04** Assignment tour migration wiring (legacy remap on upgrade)
- [ ] **LH-ONB-FR-05b** Define missing registry features for Level-1 `course_settings` + `messaging`, then remove remaining fallback JSONs without `lernhive_feature`
- [ ] **LH-ONB-FR-06** Activate and map Level-2 pack runtime categories
- [ ] **LH-ONB-FR-07** Author and integrate Level-3..5 packs
- [ ] **LH-ONB-FR-08** Consume `feature_override_changed` for cache invalidation
- [ ] **LH-ONB-FR-09** LXP flavour audience grant + acceptance test
- [ ] **LH-ONB-FR-10** Add `tour_visibility_test.php`, adjust existing tests for registry model
- [ ] **LH-ONB-FR-11** BigBlueButton soft-dependency handling at import/seed

### Start flow and chaining track

- [ ] **LH-ONB-START-07** Behat coverage for sesskey gating and redirect flow
- [ ] **LH-ONB-START-08** Complete announcements re-registration with sandbox forum placeholder
- [ ] **LH-ONB-CHAIN-01** Import `prereq` and persist `lh_prereq_tour_id`
- [ ] **LH-ONB-CHAIN-02** Implement `tour_manager::activate_successors()`
- [ ] **LH-ONB-CHAIN-03** Wire tour-ended event/hook callback
- [ ] **LH-ONB-CHAIN-04** Head-only chain priming in start flow
- [ ] **LH-ONB-CHAIN-05** Render chained learning units in tours UI
- [ ] **LH-ONB-CHAIN-06** Add `tour_chain_test.php`
- [ ] **LH-ONB-CHAIN-07** Behat for chained UX flow
- [ ] **LH-ONB-CHAIN-08** Update user-doc for cross-page learning units

## Dependencies and order

- `LH-ONB-FR-03` depends on `local_lernhive` milestones `LH-CORE-FR-02..04`
- `LH-ONB-FR-06` should follow `FR-01/02` so mappings are feature-addressable from day one
- Chaining milestones can run in parallel with registry milestones

## Immediate next actions

1. Deliver `LH-ONB-FR-03` once `local_lernhive` `LH-CORE-FR-02..04` are available
2. Deliver `LH-ONB-FR-05b` (registry additions for remaining Level-1 JSONs without canonical mapping)
3. Finish `LH-ONB-START-07` (Behat)
4. Close `LH-ONB-START-08` (sandbox announcements forum + remap)
