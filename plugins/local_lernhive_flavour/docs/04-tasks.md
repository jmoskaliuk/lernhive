# local_lernhive_flavour — Tasks

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
