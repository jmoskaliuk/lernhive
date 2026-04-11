# local_lernhive_copy — Developer Documentation

## Architecture note

Unified entry page for the two copy-based content paths on the ContentHub:
**Copy** (clone an existing course) and **Template** (instantiate a course
template). Both paths share one index.php and one wizard_page renderable; the
`source` value object normalises the `?source=` query parameter and drives the
page title and intro text.

ContentHub links to this plugin for both cards:
- Copy card → `/local/lernhive_copy/index.php` (no query param → course mode)
- Template card → `/local/lernhive_copy/index.php?source=template`

## R1 scope

R1 ships an **entry-page stub**. It renders the correct heading and an intro
paragraph (via lang strings `page_title_course`, `page_title_template`,
`page_intro_course`, `page_intro_template`) but does not yet wire up the actual
Moodle backup/restore API. The wizard_page mustache template has placeholder
action areas for the R2 form integration.

## File layout

```
local_lernhive_copy/
├── version.php                 component + deps (local_lernhive_contenthub)
├── lib.php                     empty hook slot
├── index.php                   entry page — dual admin/direct access
├── settings.php                admin_externalpage: local_lernhive_copy_wizard
├── styles.css                  scoped .lh-copy-* only
├── README.md
├── db/access.php               local/lernhive_copy:use capability
├── lang/en/local_lernhive_copy.php
├── classes/
│   ├── source.php              source value object (course | template)
│   ├── output/wizard_page.php  renderable / templatable
│   ├── output/renderer.php     plugin renderer → render_wizard_page()
│   └── privacy/provider.php    null_provider
├── templates/wizard_page.mustache
└── tests/source_test.php       PHPUnit: source routing contract
```

## Key classes

### `source` (classes/source.php)
Final value object. `source::from_request($raw)` converts the raw
`?source=` string into one of two constants (`TYPE_COURSE`, `TYPE_TEMPLATE`);
anything other than `'template'` falls back to course. Provides
`is_template(): bool` and `string_suffix(): string` (used to build dynamic
lang-string keys like `'page_title_' . $source->string_suffix()`).

### `wizard_page` (classes/output/wizard_page.php)
Renderable + templatable. Constructor receives a `source` instance and
exports it to the mustache context via `export_for_template()`.

### `renderer` (classes/output/renderer.php)
Extends `plugin_renderer_base`. Single method `render_wizard_page()`.

## Access control

`index.php` uses the dual admin/direct pattern:
- Site admin → `admin_externalpage_setup('local_lernhive_copy_wizard')`
  (picks up the admin breadcrumb + layout automatically)
- Others → `require_login()` + `require_capability('local/lernhive_copy:use')`
  against `core\context\system::instance()`

The `:use` capability is declared in `db/access.php` with
`archetypes: [coursecreator => CAP_ALLOW]`.

## Dependencies

- `local_lernhive_contenthub` (hard, declared in version.php, so Moodle
  enforces install order)
- Moodle core backup/restore API — **planned for R2**, not yet used in R1

## Privacy

`null_provider`. If R2 adds state (draft save, last-used category preference),
the provider must be upgraded before that state lands.

## CI & deployment

Same gate as all R1 LernHive plugins: `deploy-hetzner.yml` (see
contenthub `03-dev-doc.md`). Local dev via `moodle-deploy` skill.
