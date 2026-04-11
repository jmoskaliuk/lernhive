# local_lernhive_copy — Tasks

## R2.1 status: shipped (0.2.1 — 2026-04-11)

- **LH-COPY-LAYOUT-01** — `index.php`: drop `if (is_siteadmin()) { admin_externalpage_setup('local_lernhive_copy_wizard') }` dual-mode branch. Copy is a content creation tool, not a site configuration page, so it always renders with the `standard` pagelayout and the LernHive Plugin Shell — regardless of whether the visitor is a siteadmin or a course creator. `admin_externalpage_setup()` forced `pagelayout='admin'`, which (since `theme_lernhive` 0.9.34 / 0.9.36) layers the Moodle admin secondary tab bar (General | Users | Courses | …) on top of the wizard. The plugin still registers `local_lernhive_copy_wizard` in the admin tree via `settings.php` so admins can discover it via the site-admin search, but the page is unconditionally `standard` layout. Side-effect: the `?source=` query param now survives on admin visits without needing a `$PAGE->set_url()` re-apply — the admin_externalpage_setup URL-override footnote under "Open R2.0 issues" no longer applies. Removed now-unused `require_once($CFG->libdir . '/adminlib.php')`.

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

_None known. Historical note: before 0.2.1 the admin-tree entry
dropped the `?source=` query param because `admin_externalpage_setup()`
set its own URL, and index.php re-applied `$PAGE->set_url()` after
setup to compensate. Since 0.2.1 the page no longer calls
`admin_externalpage_setup()` at all, so the workaround is gone and
the query param survives naturally._

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
