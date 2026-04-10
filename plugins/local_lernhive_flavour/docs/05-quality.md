# local_lernhive_flavour — Quality

## Quality goals
- Terminology is consistent with the LernHive language guide
- UX stays simple: one picker page, one confirm dialog, no deep navigation
- Strings are reusable and localisable (DE + EN ship with the plugin)
- Picker works on desktop and smaller screens (cards reflow at 280px)
- No unnecessary duplication of Moodle core logic — we use `set_config()`,
  not direct `mdl_config_plugins` writes
- Audit trail is immutable so `local_lernhive_configuration` (R2) can
  rely on its integrity

## Automated checks

### PHPUnit
Three test files under `tests/`, covering:
- Registry invariants and profile counts
- Manager `apply`, `diff`, override detection, event triggering
- Audit writer, JSON encoding, ordering

Run with:
```bash
vendor/bin/phpunit local/lernhive_flavour/tests/
```

### Prechecks
- PHPCS (Moodle coding standard)
- PHPDoc coverage on all public APIs
- `xmldb` savepoint in `upgrade.php` matches `version.php`
- Mustache templates lint cleanly

## Manual smoke test

Run after every meaningful change:

1. Deploy via `deploy.sh local_lernhive_flavour`, run site upgrade
2. As admin, visit **Site administration → Plugins → Local plugins →
   LernHive Flavour → Flavour Setup**
3. Verify four cards are shown in order: School, LXP, Higher Education,
   Corporate Academy
4. Verify School card is marked "Active"
5. Verify Higher Education and Corporate Academy cards have the dashed
   border and "Experimental" badge
6. Click "Apply flavour" on LXP → should apply directly (no diff,
   fresh install)
7. Verify LXP card is now active, School card is clickable again
8. Visit `/admin/settings.php?section=local_lernhive_settings` and
   confirm that:
   - `allow_teacher_course_creation` is now 0
   - `show_levelbar` is now 0
9. Manually change `show_levelbar` back to 1
10. Return to Flavour Setup, click "Apply flavour" on School → confirm
    dialog should appear with `show_levelbar` diff highlighted
11. Click "Apply School anyway" → verify `show_levelbar` flips back to 1
12. Check `mdl_local_lernhive_flavour_apps` — three rows, newest has
    `overrides_detected = 1`

## Known issues
None after the 2026-04-10 refactor.

## Accessibility notes
- Flavour card is a semantic `<div role="listitem">`
- Apply button is a native `<button type="submit">`
- Card icon is marked `aria-hidden="true"` so screen readers get to the
  title and description directly
- Badges use solid background colours, not colour-only differentiation
  (dashed border reinforces experimental state)
- Still to do: full audit with `webui-accessibility-auditor` once the
  rest of the R1 plugins land and we can assess cross-plugin consistency
