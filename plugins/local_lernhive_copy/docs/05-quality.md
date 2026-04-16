# local_lernhive_copy - Quality

## Quality goals

- terminology is consistent (Copy, Template - not Backup/Restore labels in UI)
- UX stays simple, guided, and mobile-friendly
- strings are reusable and localizable
- plugin logic reuses Moodle core backup/restore instead of duplicating it
- privacy metadata stays accurate when user preferences are stored

## Checks

- accessibility: form controls and headings use proper labelling
- responsive: wizard layout remains readable below 600px
- role/permission checks:
  `local/lernhive_copy:use` on system context plus source-course capability
  checks for backup/restore before queueing
- layout consistency:
  page always renders in standard LernHive shell layout (also for site admins)
  and now exposes the full shell stack (Zone A + Zone B) without
  duplicate intro cards
- language string review:
  `page_title_*` and `page_intro_*` keys cover course/template source modes
- icon/button consistency:
  mode switch buttons and template/mode info actions follow the shared
  LernHive button taxonomy (`lh-btn-outline`, `lh-btn-open`, `lh-btn-action`)
- regression checks:
  `source_test.php`, `copy_form_test.php`, and `wizard_page_test.php` stay green
- template-catalog checks:
  template entries without source mapping stay non-clickable

## Known quality gaps

- Behat happy path exists but still needs regular CI execution in this repo
- template-edge-case automation coverage is still limited
- no dedicated performance checks yet for large template catalogs

## CI gate

Current automated gate is `deploy-hetzner.yml` (deploy + CLI upgrade + purge).
A dedicated moodle-plugin-ci matrix remains deferred while the plugin is still
in active scope iteration.
