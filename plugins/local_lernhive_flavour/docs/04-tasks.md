# local_lernhive_flavour — Tasks

## Done in v0.2.1 (fresh-apply fix, 2026-04-15)
- [x] `flavour_manager::has_pending_overrides()` no longer returns `true` on a fresh site.
  - Root cause: the method iterated `diff()` and flagged every row whose target differed from current, including rows where `current === null` (i.e. the admin had never configured the key). On a never-touched install that meant "first-ever apply" was wrongly reported as "overwriting existing state", which in turn triggered the confirm-diff dialog on what should have been a clean first apply.
  - Fix: only count a diff row as an override when `$entry['current'] !== null && $entry['changes']`. Mirrors the pre-existing logic in `detect_overrides()` (the admin-visible audit) so the two paths agree on what counts as "override".
  - Covered by `flavour_manager_test::test_apply_on_fresh_site_does_not_flag_overrides` — last full PHPUnit run on main (sha ded40269, 2026-04-16) is green.
  - Smoke-test pending on `dev.lernhive.de`: fresh apply of the LXP flavour should go straight through without showing the diff dialog.

## Done in v0.2.0 (R1 refactor, 2026-04-10)
- [x] Split monolithic `flavour_manager` into registry + manager + audit + profiles
- [x] Fix config key mismatch against `local_lernhive/settings.php`
  (`allow_course_creation` → `allow_teacher_course_creation` etc.)
- [x] Add `flavour_definition` abstract base + four profiles
- [x] Add `flavour_audit` with `local_lernhive_flavour_apps` table
- [x] Extract inline CSS from `admin_flavour.php` into `styles.css`
- [x] Replace inline HTML with Mustache templates and plugin renderer
- [x] Add confirm-diff dialog for applies that would overwrite existing settings
- [x] Introduce `flavour_applied` event
- [x] Upgrade privacy provider from `null_provider` to full provider
- [x] PHPUnit coverage for registry, manager and audit
- [x] DevFlow docs sync

## Open for R2
- [ ] Define real Higher Education defaults (`highered_profile::get_defaults`)
- [ ] Define real Corporate Academy defaults (`corporate_profile::get_defaults`)
- [ ] Extend picker with LXP-specific keys from `local_lernhive_discovery`
      once that plugin exists
- [ ] Provide an audit history view inside `local_lernhive_configuration`
      that consumes `flavour_applied` events
- [ ] Flavour governance model: should admins be locked out of certain
      changes after applying a flavour, or is every override always allowed?
- [ ] Subplugin hook for customer-specific flavours

## Open product decisions referenced here
- Higher Ed / Corporate Academy specifics → `product/07-next-steps-and-decisions.md`
- Flavour governance boundaries → same file, decision still open

## Quick test loop
```bash
# run unit tests
vendor/bin/phpunit local/lernhive_flavour/tests/

# redeploy to Orb and purge caches
bash ./deploy.sh local_lernhive_flavour
```
