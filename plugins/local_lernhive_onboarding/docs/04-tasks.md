# local_lernhive_onboarding — Tasks

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

## Open questions

- **Level 2 trigger.** When should a trainer see the next-level
  banner — immediately on Level 1 completion, or after a separate
  admin confirmation step?
- **Flavour interaction.** Should the Trainer Learning Path pack be
  swapped per active LernHive flavour, or is it flavour-agnostic?
- **Reporting.** Telemetry on banner CTA clicks would require
  replacing the null privacy provider; revisit once the click does
  work worth measuring.

## Next step

1. Deploy 0.2.0 to dev.lernhive.de (Hetzner pipeline picks it up on
   merge to `main`).
2. Manually assign `lernhive_trainer` to a test user, visit `/my/`
   and verify the banner renders with the eLeDia palette.
3. Decide on the auto-assignment follow-up based on how the manual
   assignment flow feels in practice.
