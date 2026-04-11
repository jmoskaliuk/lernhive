# local_lernhive_copy — Developer Documentation

## Architecture note

Unified entry page for the two copy-based content paths on the ContentHub:
**Copy** (clone an existing course) and **Template** (instantiate a course
template). Both paths share one `index.php` and one `wizard_page` renderable;
the `source` value object normalises the `?source=` query parameter and drives
the page title, intro text, and which branch of the page is shown.

ContentHub links to this plugin for both cards:
- Copy card → `/local/lernhive_copy/index.php` (no query param → course mode)
- Template card → `/local/lernhive_copy/index.php?source=template`

## Current scope

R2.0 ships the **Simple copy flow** for the course source. The template source
still renders the R1 stub — the catalogue backend needs to land first
(tracked in `docs/04-tasks.md`).

The Simple copy flow:
1. Trainer lands on `index.php` from the ContentHub Copy card.
2. Picks a source course via the `course` autocomplete element
   (scoped to courses they can back up via `moodle/backup:backupcourse`).
3. Fills in a target fullname, shortname, category, visibility, start/end
   dates, and optional idnumber.
4. Chooses whether to include participants and progress data (defaults to
   "no" — matches the "clean copy for a new cohort" use case).
5. Submits → `index.php` validates, calls `\copy_helper::process_formdata()`
   and `\copy_helper::create_copy()`, and redirects to
   `/backup/copyprogress.php?id={source}`.

The actual backup/restore runs asynchronously as a
`\core\task\asynchronous_copy_task` — Moodle's cron picks it up and the user
can follow progress on the built-in progress page.

## File layout

```
local_lernhive_copy/
├── version.php                    component + deps (local_lernhive_contenthub)
├── lib.php                        empty hook slot
├── index.php                      entry page — dual admin/direct access + form handler
├── settings.php                   admin_externalpage: local_lernhive_copy_wizard
├── styles.css                     scoped .lh-copy-* only
├── README.md
├── db/access.php                  local/lernhive_copy:use capability
├── lang/en/local_lernhive_copy.php
├── classes/
│   ├── source.php                 source value object (course | template)
│   ├── form/copy_form.php         moodleform — simple copy flow
│   ├── output/wizard_page.php     renderable / templatable
│   ├── output/renderer.php        plugin renderer → render_wizard_page()
│   └── privacy/provider.php       null_provider
├── templates/wizard_page.mustache
└── tests/
    ├── source_test.php            PHPUnit: source routing contract
    └── form/copy_form_test.php    PHPUnit: form validation contract
```

## Key classes

### `source` (classes/source.php)
Final value object. `source::from_request($raw)` converts the raw
`?source=` string into one of two constants (`TYPE_COURSE`, `TYPE_TEMPLATE`);
anything other than `'template'` falls back to course. Provides
`is_template(): bool` and `string_suffix(): string` (used to build dynamic
lang-string keys like `'page_title_' . $source->string_suffix()`).

### `form\copy_form` (classes/form/copy_form.php)
Extends `\moodleform`. Produces a data object with exactly the fields
`\copy_helper::process_formdata()` requires: `courseid`, `fullname`,
`shortname`, `category`, `visible`, `startdate`, `enddate`, `idnumber`,
`userdata`. Server-side validation mirrors Moodle core's copy form:
duplicate shortname, duplicate idnumber, and `course_validate_dates()` for
start/end ordering.

Unlike `\core_backup\output\copy_form`, this form does NOT pre-select a
source course — it owns the course picker itself so it can be reached
directly from the ContentHub without a source context.

### `output\wizard_page` (classes/output/wizard_page.php)
Renderable + templatable. Constructor takes a `source` instance and an
optional pre-rendered form HTML string. `export_for_template()` produces
a mustache context with `hasform`, `formhtml`, and the stub strings used
by the template-mode fallback.

### `output\renderer` (classes/output/renderer.php)
Extends `plugin_renderer_base`. Single method `render_wizard_page()`.

## Access control

`index.php` uses the dual admin/direct pattern:
- Site admin → `admin_externalpage_setup('local_lernhive_copy_wizard')`
  (picks up the admin breadcrumb + layout automatically). We re-apply
  `$PAGE->set_url()` afterwards so the form action keeps the `?source=`
  query param.
- Others → `require_login()` + `require_capability('local/lernhive_copy:use')`
  against `core\context\system::instance()`.

Before handing data to `copy_helper::create_copy()`, `index.php` also
re-checks `moodle/backup:backupcourse` and `moodle/restore:restorecourse`
on the chosen **source course context** — the form's course-picker
already filters for this but server-side re-verification is cheap
insurance against tampered POSTs.

## Dependencies

- `local_lernhive_contenthub` (hard, declared in version.php, so Moodle
  enforces install order)
- Moodle core backup/restore API — used via `\copy_helper` in the
  simple copy flow. The helper lives at
  `lib.php: /backup/util/helper/copy_helper.class.php` and is loaded
  explicitly in `index.php` so `require_once()` doesn't depend on
  `/backup/copy.php` having been visited first.

## Privacy

`null_provider`. The plugin itself does not store personal data. The
actual backup/restore operations are performed by Moodle core, which
has its own privacy providers — our `privacy:metadata` string points
users at those.

## CI & deployment

Same gate as all R1 LernHive plugins: `deploy-hetzner.yml` (see
contenthub `03-dev-doc.md`). Local dev via `moodle-deploy` skill.
