# local_lernhive_onboarding — Tasks

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

## Open questions

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