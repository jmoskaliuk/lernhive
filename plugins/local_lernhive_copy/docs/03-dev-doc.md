# local_lernhive_copy - Developer Documentation

## Architecture note

Unified entry page for the two copy-based content paths on the ContentHub:
**Copy** (clone an existing course) and **Template** (instantiate a course
template). Both paths share one `index.php` and one `wizard_page` renderable;
the `source` value object normalises the `?source=` query parameter and drives
the page title, intro text, and which branch of the page is shown.

ContentHub links to this plugin for both cards:
- Copy card -> `/local/lernhive_copy/index.php` (no query param -> course mode)
- Template card -> `/local/lernhive_copy/index.php?source=template`

## Current scope

R2.2 (`0.3.0`, 2026-04-15) ships:
- **Simple copy mode** for direct course copy
- **Expert mode** (source picker + redirect to Moodle core `/backup/copy.php`)
- **Template mode** connected to Library catalog entries (two-step flow)

Simple copy flow:
1. User opens Copy (or selects a template first).
2. Wizard form collects target course data and defaults userdata to "no".
3. `index.php` validates payload and capabilities.
4. `\copy_helper::process_formdata()` + `\copy_helper::create_copy()` queue the job.
5. User is redirected to `/backup/copyprogress.php?id={sourcecourseid}`.

Expert flow:
1. User toggles to Expert.
2. Wizard collects source course only (or uses the template-mapped source).
3. User is redirected to Moodle core `/backup/copy.php?id={sourcecourseid}`.

Template flow:
1. `?source=template` loads entries from `\local_lernhive_library\catalog`.
2. User picks a template card (`templateid`).
3. Wizard resolves mapped `sourcecourseid` and continues in Simple or Expert mode.

## File layout

```
local_lernhive_copy/
├── version.php                    component + deps (local_lernhive_contenthub)
├── lib.php                        empty hook slot
├── index.php                      standard-page entry + form handler
├── settings.php                   admin_externalpage: local_lernhive_copy_wizard
├── styles.css                     scoped .lh-copy-* only
├── README.md
├── db/access.php                  local/lernhive_copy:use capability
├── lang/en/local_lernhive_copy.php
├── classes/
│   ├── source.php                 source value object (course | template)
│   ├── form/copy_form.php         moodleform - simple copy flow
│   ├── form/expert_source_form.php source picker for expert flow
│   ├── output/wizard_page.php     renderable / templatable
│   ├── output/renderer.php        plugin renderer -> render_wizard_page()
│   └── privacy/provider.php       metadata + user preference provider
├── templates/wizard_page.mustache
└── tests/
    ├── source_test.php             PHPUnit: source routing contract
    ├── form/copy_form_test.php     PHPUnit: form validation contract
    ├── output/wizard_page_test.php PHPUnit: renderable context coverage
    └── behat/copy_flow.feature     Behat: launcher -> copy -> progress
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

Unlike `\core_backup\output\copy_form`, this form can either:
- pick a source course directly (course path), or
- use a fixed source course mapped from a selected template.

The form stores the selected category in a user preference so the next copy
run can preselect it.

### `form\expert_source_form` (classes/form/expert_source_form.php)
Minimal source-picker for Expert mode. After submission, `index.php` redirects
to Moodle core `/backup/copy.php` so advanced copy options stay in core UI.

### `output\wizard_page` (classes/output/wizard_page.php)
Renderable + templatable. Constructor takes:
- `source`
- optional pre-rendered form HTML
- view-state array (mode links, template cards, warnings, return URL)

### `output\renderer` (classes/output/renderer.php)
Extends `plugin_renderer_base`. Single method `render_wizard_page()`.

## Access control

`index.php` uses one access model for all users:
- `require_login()`
- `require_capability('local/lernhive_copy:use', system context)`
- `set_pagelayout('standard')` (always, including site admins)

The plugin stays discoverable in the admin tree via `settings.php`, but the
runtime page does not call `admin_externalpage_setup()`.

Before calling `copy_helper::create_copy()`, `index.php` re-checks
`moodle/backup:backupcourse` and `moodle/restore:restorecourse` on the chosen
**source course context**. This server-side check protects against tampered
POST data and does not trust the client-side picker alone.

## Dependencies

- `local_lernhive_contenthub` (hard dependency, declared in `version.php`)
- Moodle core backup/restore API via `\copy_helper`
- Explicit include: `/backup/util/helper/copy_helper.class.php`
- Optional runtime integration: `local_lernhive_library\catalog` for templates

## Privacy

Plugin stores one user preference:
- `local_lernhive_copy_default_category`

Copy operations remain delegated to Moodle core backup/restore, which provide
their own privacy metadata.

## CI & deployment

Current shared gate is `deploy-hetzner.yml` (repo-level deploy + upgrade run).
Local dev is done via the `moodle-deploy` workflow.
