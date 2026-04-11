# local_lernhive_copy — Tasks

## R1 status: shipped

R1 scaffold was deployed to `dev.lernhive.de` on 2026-04-11 (commit
`9475cf5`, version `2026041002`). The entry page rendered the mode
tiles for both source modes (course, template) and was reachable from
the ContentHub cards and the admin tree.

## R2.0 status: shipped

Simple copy flow is live as of 2026-04-11, version `2026041104`
(release `0.2.0`). The course source now ships a working moodleform
that picks source course + target category and hands off to
`\copy_helper::create_copy()` — i.e. a real course gets duplicated in
the background. Template source still shows the R1 stub.

## Open R2.0 issues

_None known. The admin-tree entry currently drops the `?source=` query
param because `admin_externalpage_setup()` sets its own URL; index.php
compensates by re-applying `$PAGE->set_url()` after setup. Worth a
smoke test once the launcher plugin lands._

## R2.1 backlog

- Add a "cancel" button that returns to ContentHub instead of dumping
  to the Moodle dashboard
- Expert mode: swap in the full Moodle core `\core_backup\output\copy_form`
  via a toggle, so power users get roles + relative-dates controls
- "Copy without participants" is already the simple-mode default — add
  an explicit callout next to the `userdata` field so trainers don't
  miss it
- Per-user default category (persisted in user_preferences)
- Behat: course creator reaches wizard via launcher, submits, lands on
  copyprogress
- Extend PHPUnit coverage to the wizard_page renderable (mode routing
  with/without formhtml)

## R2.2 backlog (template source)

- Wire the template card to the library catalogue (depends on
  `local_lernhive_library` having its backend source connected)
- Decide whether templates are rendered via the same moodleform or
  via a "pick template → confirm" two-step flow
