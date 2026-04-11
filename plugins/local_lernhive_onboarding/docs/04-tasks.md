# local_lernhive_onboarding — Tasks

## Done (0.2.4 — 2026-04-11)

- **LH-ONB-START-01** — New class `local_lernhive_onboarding\start_url_resolver`
  with a pure `resolve(string $template, int $userid): \moodle_url` method.
  Substitutes `{USERID}`, `{SYSCONTEXTID}`, `{SITEID}`, `{DEMOCOURSEID}`
  (from `get_config('local_lernhive_onboarding', 'democourseid')`, defaults
  to `0` when the sandbox course has not been seeded yet). Unknown
  placeholders stay literal so older plugin versions can still parse
  tours authored against a later release. Empty / whitespace-only
  templates throw `\coding_exception` — callers are expected to fall
  back to the pathmatch strip before invoking the resolver.
  Unit tests in `tests/start_url_resolver_test.php` cover: one case per
  placeholder, `{DEMOCOURSEID}` defaulting to `0` when config is absent,
  empty-template exception, whitespace-only-template exception, and the
  "unknown placeholder stays literal while known ones still resolve"
  case.

- **LH-ONB-START-02** — `tour_importer::import_tour()` now reads the
  top-level `start_url` key from the tour JSON and merges it into the
  tour's `configdata` as `lh_start_url`. Implemented via a private
  `merge_start_url_into_configdata()` helper that json-decodes the
  existing configdata, preserves every pre-existing key
  (`filtervalues`, `placement`, `orphan`, …), writes the new key and
  re-encodes. No-op when `start_url` is empty, so un-migrated Level-1
  tours are byte-identical before and after. Malformed input configdata
  is coerced to an empty object rather than crashing the import path.
  Fixture JSONs live in `tests/fixtures/tour_with_start_url.json` and
  `tests/fixtures/tour_without_start_url.json`; `tests/tour_importer_test.php`
  drives two cases: (a) merge preserves `filtervalues.role` and
  `placement` while adding `lh_start_url`, (b) absence of `start_url`
  leaves configdata intact (no phantom `lh_start_url` key).
  **Scope boundary:** existing-tour branch in `import_tour()` is left
  unchanged — re-imports against already-present tours do not yet
  backfill `lh_start_url`. That upgrade path is explicitly the job of
  LH-ONB-START-05 (backfill migration).

## Done (0.2.1 — 2026-04-11)

- **LH-ONB-TMPL-01** — `templates/dashboard_banner.mustache` rewritten from Bootstrap card to `.lh-cta-strip` component:
  - Old: `<div class="card card-body">` with inline `<style>` block, `border-radius: 12px`, custom colours hardcoded
  - New: `<aside class="lh-cta-strip lh-cta-strip--trainer">` with `__icon`, `__body` (title + intro + progress), `__cta` sub-elements; zero inline styles; all styling comes from `theme_lernhive` `_dashboard.scss`
  - Behaviour unchanged: progress bar renders when `{{hasprogress}}`; CTA links to `{{toursurl}}`; `aria-labelledby` points to `lh-onboarding-banner-title`

## Done (0.2.0 — 2026-04-11)

- **LH-ONB-01** Dedicated `lernhive_trainer` role — capability
  `local/lernhive_onboarding:receivelearningpath` (no archetype grant),
  role provisioned idempotently via `trainer_role::ensure()` from
  `db/install.php` and a `2026041200` upgrade step.
- **LH-ONB-02** Dashboard banner — renderable
  `output\dashboard_banner` + renderer `output\renderer` +
  `templates/dashboard_banner.mustache`. Banner theme tokens
  (`--lernhive-blue-dark`, `--lernhive-orange-dark`) tie it to the
  0.9.11+ `theme_lernhive` palette with sensible fallbacks.
- **LH-ONB-03** Visibility gate — `banner_gate::should_show($userid)`
  folds three checks in a testable static: logged-in/non-guest →
  trainer capability → Level 1 not yet complete. Pure function, no
  hook plumbing.
- **LH-ONB-04** Dashboard injection — `db/hooks.php` +
  `hook_callbacks::inject_dashboard_banner` bound to
  `core\hook\output\before_standard_top_of_body_html_generation` with
  priority `400` (runs after `local_lernhive`'s core level banner at
  500). Scoped to `pagelayout === 'mydashboard' || pagetype === 'my-index'`
  so we only pay the DB round trip on `/my/`.
- **LH-ONB-05** PHPUnit coverage —
  `tests/trainer_role_test.php` (install side-effect, idempotency,
  assigned-user has cap, unassigned user does not) and
  `tests/banner_gate_test.php` (5 cases: userid 0, guest, logged-in
  without role, trainer with incomplete level, trainer with complete
  level).
- **LH-ONB-06** Lang strings — `trainer_role_name`,
  `trainer_role_description`, `banner_*` family in both EN and DE.

## Follow-up called out by the 0.2.0 change

- **Dismiss state.** R1 hides the banner automatically once Level 1
  is complete. R2 may want an explicit "don't show again" link that
  writes a user preference; skipped for now to avoid a second
  visibility axis on top of level progress.
- **Auto-assignment.** Admins currently have to assign the
  `lernhive_trainer` role manually via
  Site administration → Users → Permissions → Assign system roles.
  A follow-up could auto-assign on first login for users who already
  have a `local_lernhive_levels` record, using an
  `auth\user_loggedin` event observer. Intentionally deferred because
  (a) admin-only first run lets us QA the feature cleanly on
  dev.lernhive.de and (b) it keeps the trainer audience auditable.
- **Snack/Community categories.** The category matrix (`tours/level1`)
  currently hardcodes Moodle core flows. Once `format_lernhive_snack`
  lands, the onboarding flow needs a sibling category pack.
- **Behat.** No UI coverage yet — add a feature that logs in as a
  trainer, visits `/my/`, and asserts the banner is present and
  routes to `/local/lernhive_onboarding/tours.php`.
- **Docs/code gap.** Docs 00–05 are still DevFlow stubs. Next
  non-code sweep should fill them with the actual plugin shape.

## ADR-01 follow-ups — Feature Registry consumer work (target 0.3.0)

Depends on `local_lernhive` **LH-CORE-FR-01..FR-04** landing first.

- [ ] **LH-ONB-FR-01** — Add `feature_id VARCHAR(128) NULL` column to `local_lhonb_map` via `db/upgrade.php` at the 0.3.0 savepoint. Non-unique index on `feature_id`.
- [ ] **LH-ONB-FR-02** — Teach `tour_importer::import_tour()` to read the `lernhive_feature` top-level key from the tour JSON and persist it onto the mapping row.
- [ ] **LH-ONB-FR-03** — Rewrite `tour_manager::get_categories()` to consult `local_lernhive\feature\registry::is_available_for_user()` per tour instead of the `level` column on `local_lhonb_cats`. Keep a fallback path for mappings with `feature_id IS NULL` so un-migrated content does not disappear mid-upgrade.
- [ ] **LH-ONB-FR-04** — Migrate the existing Level-1 assignment tour to Level 2:
  - move `tours/level1/create_activities/01_assignment.json` → `tours/level2/assignments/01_create.json`,
  - add `"lernhive_feature": "mod_assign.create"`,
  - seed the `assignments` category in `tour_importer::seed_categories()`,
  - write a `db/upgrade.php` step that rewires existing installations' `local_lhonb_map` rows.
- [ ] **LH-ONB-FR-05** — Backfill `lernhive_feature` on every existing Level-1 tour JSON. This makes the 0.3.0 cut the last time tours rely on the directory-level fallback.
- [ ] **LH-ONB-FR-06** — Author Level 2 tour JSONs per `level-tour-matrix.md` v2: `assignments/01_create.json` (migrated), `assignments/02_grade.json`, `forum_advanced/01_types.json`, `forum_advanced/02_subscriptions.json`, `bigbluebutton/01_create.json`, `bigbluebutton/02_record.json`. All with `lernhive_feature` set.
- [ ] **LH-ONB-FR-07** — Author Level 3..5 tour JSONs per `level-tour-matrix.md` v2 (7 + 7 + 7 tours). Can ship in three sub-increments (0.3.1, 0.3.2, 0.3.3) if content authoring takes longer than the core work.
- [ ] **LH-ONB-FR-08** — Listen to `local_lernhive\event\feature_override_changed` and invalidate any in-memory tour cache so admin overrides take effect on the next dashboard load.
- [ ] **LH-ONB-FR-09** — LXP-Flavor audience: wire `local_lernhive_flavour` LXP preset to grant `local/lernhive_onboarding:receivelearningpath` to the participant role. Add a Behat test covering the LXP path.
- [ ] **LH-ONB-FR-10** — Update `tests/trainer_role_test.php` and `tests/banner_gate_test.php` for the registry-driven lookup. Add a new `tests/tour_visibility_test.php` that asserts override-driven visibility changes end-to-end.
- [ ] **LH-ONB-FR-11** — BigBlueButton soft-dependency: skip the `bigbluebutton` category at seed time if `mod_bigbluebuttonbn` is not installed; log a `debugging()` note for admins.

## Deterministic tour start + chaining (target 0.3.0)

Independent from the registry work above — can land before or in parallel. Unblocks the catalog "Start" UX and lays the foundation for Level-2 multi-page journeys.

- [x] **LH-ONB-START-01** — *(landed 0.2.4)* New class `local_lernhive_onboarding\start_url_resolver` with a pure `resolve(string $template, int $userid): moodle_url` method. Placeholders: `{USERID}`, `{SYSCONTEXTID}`, `{SITEID}`, `{DEMOCOURSEID}`. Unknown placeholders stay literal. Unit tests: one per placeholder, plus "empty template → exception", plus "unknown placeholder → literal".
- [x] **LH-ONB-START-02** — *(landed 0.2.4)* Teach `tour_importer::import_tour()` to read the top-level `start_url` key from tour JSON and merge it into `configdata` as `lh_start_url`. Must preserve any existing `filtervalues`/`placement`/etc. Unit test against a fixture JSON that already has a non-empty `configdata`.
- [ ] **LH-ONB-START-03** — Rewrite `starttour.php` per the flow in `03-dev-doc.md`: load tour → pull `lh_start_url` from configdata → resolve placeholders → set `_requested=1` → clear `_completed` + `_lastStep` → redirect. Keep the 0.2.x pathmatch-strip as a fallback for tours without `lh_start_url`. Delete the fallback in 0.4.0 once all tours are migrated.
- [ ] **LH-ONB-START-04** — PHPUnit: `starttour_flow_test.php` covering fresh-start, replay-after-completion, fallback-when-no-start_url, and sesskey enforcement.
- [ ] **LH-ONB-START-05** — Backfill `start_url` on all 12 existing Level-1 tour JSONs. Concrete mapping (Johannes to confirm during review):
  - `create_users/01_single.json` → `/user/editadvanced.php?id={USERID}` (verify: does editadvanced accept current user's id as a self-edit landing, or do we need a new-user URL?)
  - `create_users/02_csv.json` → `/admin/tool/uploaduser/index.php`
  - `enrol_users/*` → `/enrol/users.php?id={DEMOCOURSEID}` or similar — TBD during authoring review
  - remaining 9 — TBD during the Level-1 review sweep, tracked here as a sub-list.
- [ ] **LH-ONB-START-06** — Install/upgrade step: provision the "Onboarding Sandbox" course (category: Miscellaneous or a new hidden `lh-onboarding` category), store its ID in `config_plugins` under `local_lernhive_onboarding.democourseid`. Idempotent; recreates if the stored ID points at a deleted course. Add a cleanup uninstaller that keeps the course by default (admins may have added real content) and only removes the config key.
- [ ] **LH-ONB-CHAIN-01** — Add `prereq` support to `tour_importer::import_tour()`. Read top-level `prereq` (string, tour name) → resolve to tour ID at import time → persist as `lh_prereq_tour_id` in `configdata`. Two-pass import required: first pass imports all tours without resolving prereqs, second pass back-fills prereq IDs once all names are known. Fail loudly (debugging + skip chain activation for that tour) if the prereq name cannot be resolved.
- [ ] **LH-ONB-CHAIN-02** — New method `tour_manager::activate_successors(int $tourid, int $userid): void`. Queries tours with `lh_prereq_tour_id = $tourid` in `configdata`, sets `_requested=1` and clears `_completed` for each matching successor, for the given user. DB lookup must use `JSON_EXTRACT` where available and fall back to a PHP-side filter for DBs that lack JSON support. Write down the decision in the method docblock.
- [ ] **LH-ONB-CHAIN-03** — Event observer `\tool_usertours\event\tour_ended` → `hook_callbacks::on_tour_ended` → `tour_manager::activate_successors($event->objectid, $event->userid)`. Register in `db/events.php`. If Moodle 5.x uses a hook instead of an event for tour end, switch to `db/hooks.php` and document the choice. Pick the one that actually fires in 5.2beta — validate on dev.lernhive.de.
- [ ] **LH-ONB-CHAIN-04** — `starttour.php` logic: when the target tour has a `lh_prereq_tour_id`, refuse to prime it directly (would skip the chain). Instead walk the prereq chain back to the head and prime only the head. Catalog UI should also render chained successors as "unlocks after step N".
- [ ] **LH-ONB-CHAIN-05** — Extend `tours.php` + `tour_overview.mustache` to group tours by chain: render a chained category as a Learning Unit with N numbered step dots, each linking to `starttour.php?tourid=<head>` (starting the whole chain) or — for individual tours that are already unlocked via completed prereq — directly to that tour's start. Keep the flat-list rendering for un-chained categories.
- [ ] **LH-ONB-CHAIN-06** — PHPUnit: `tour_chain_test.php` per the strategy in `03-dev-doc.md` (head-priming only, activate_successors correctness, non-chained tours untouched).
- [ ] **LH-ONB-CHAIN-07** — Behat: `tour_start_from_catalog.feature` covering (a) single-page tour starts from catalog and plays on target page, (b) chained tour: complete step 1, click next-page CTA, step 2 auto-plays, (c) replay a completed tour via catalog.
- [ ] **LH-ONB-CHAIN-08** — Docs: update `02-user-doc.md` with a short "how a Learning Unit flows across pages" explainer so UX/marketing/support have a single reference.

## Open questions

- **Chain event vs. hook in Moodle 5.x.** Moodle 5.x is still shifting observer code from legacy events to the new hook manager. Tour completion may be exposed via `\tool_usertours\event\tour_ended` *and/or* a new `\core_user_tours\hook\after_tour_ended`. `LH-ONB-CHAIN-03` needs a quick spike on dev.lernhive.de (5.2beta) to pick the one that actually fires. Prefer hook if both are available; document the call.
- **`start_url` for `enrol_users` tours.** Enrol-users page requires a course ID. Either point at `{DEMOCOURSEID}` (clean, but the sandbox course is empty of users) or at a richer fixture course seeded alongside the sandbox. Decide during the `LH-ONB-START-05` review sweep.
- **Level 2 trigger.** Decided in matrix review round 1: **auto after Level 1 complete OR manual admin override** — even when tours are unfinished. Implementation ticket still open, tracked as **LH-ONB-FR-12** *(to be created)*.
- **Dismiss state.** R1 hides the banner automatically once Level 1 is complete. R2 may want an explicit "don't show again" link that writes a user preference; skipped for now to avoid a second visibility axis on top of level progress.
- **Auto-assignment.** Admins currently have to assign the `lernhive_trainer` role manually via Site administration → Users → Permissions → Assign system roles. A follow-up could auto-assign on first login for users who already have a `local_lernhive_levels` record, using an `auth\user_loggedin` event observer. Intentionally deferred because (a) admin-only first run lets us QA the feature cleanly on dev.lernhive.de and (b) it keeps the trainer audience auditable.
- **Reporting.** Telemetry on banner CTA clicks would require replacing the null privacy provider; revisit once the click does work worth measuring.
- **Behat.** No UI coverage yet — add a feature that logs in as a trainer, visits `/my/`, and asserts the banner is present and routes to `/local/lernhive_onboarding/tours.php`.

## Next step

1. Accept ADR-01 in review round 2 (Johannes).
OK, bitte umsetzen! 
2. Wait for **LH-CORE-FR-01..FR-04** to land in `local_lernhive` 0.3.0.
3. In parallel, start **LH-ONB-FR-01** (DB column) and **LH-ONB-FR-05** (backfill existing Level-1 tour JSONs) — both are independent of the registry core and unblock the rest.
4. Review `level-tour-matrix.md` v2 in round 2, then author Level 2 tour JSONs under **LH-ONB-FR-06**.
Ja, ok! Bitte dranarbeiten.