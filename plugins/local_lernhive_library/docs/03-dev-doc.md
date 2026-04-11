# local_lernhive_library — Developer Documentation

## Architecture note

Entry page for browsing eLeDia's managed content catalog. In R1 the page
renders an **empty-state scaffold** — the backend catalog API and the
`.mbz` import pipeline are not yet connected. The data model for a catalog
entry (`catalog_entry`) is defined as a value object so the contract for
the eventual source is stable before the source exists.

## R1 scope: display scaffold only

R1 ships a UI-only placeholder:
- `catalog` returns an empty list (constructor-injected, so tests can seed
  fake entries without touching a network)
- `catalog_page` renders an empty state ("No content available yet")
- **No `.mbz` download, no import, no version comparison** — those are R2+

The `.mbz` delivery approach, versioning logic, and update UX that were
discussed in earlier planning sessions apply to **R2 only** (see below).

## File layout

```
local_lernhive_library/
├── version.php                 component + deps (local_lernhive_contenthub)
├── lib.php                     empty hook slot
├── index.php                   entry page — dual admin/direct access
├── settings.php                admin_externalpage: local_lernhive_library_catalog
├── styles.css                  scoped .lh-library-* only
├── README.md
├── db/access.php               local/lernhive_library:import capability
├── lang/en/local_lernhive_library.php
├── classes/
│   ├── catalog.php             in-memory catalog provider (empty by default)
│   ├── catalog_entry.php       immutable value object — defines backend contract
│   ├── output/catalog_page.php renderable / templatable
│   ├── output/renderer.php     plugin renderer → render_catalog_page()
│   └── privacy/provider.php    null_provider
├── templates/catalog_page.mustache
└── tests/catalog_test.php      PHPUnit: catalog + catalog_entry contract
```

## Key classes

### `catalog_entry` (classes/catalog_entry.php)
Immutable value object. Fields: `id`, `title`, `description`, `version`
(semver-like string), `updated` (unix timestamp), `language` (ISO code).
`to_template_context()` formats `updated` via Moodle's `userdate()` and
uppercases `language`. This class defines what the R2 backend source must
return — no code outside `catalog.php` should construct entries.

### `catalog` (classes/catalog.php)
Placeholder provider. Constructor accepts `catalog_entry[]` (empty by
default). `all(): catalog_entry[]` and `is_empty(): bool`. In R2 this
class will be replaced by or delegated to a real source (API client,
file-based manifest, etc.); the `catalog_page` renderable is written
against this interface already so the swap should be contained here.

### `catalog_page` (classes/output/catalog_page.php)
Renderable + templatable. Constructor receives a `catalog` instance;
`export_for_template()` maps entries through `to_template_context()`.

### `renderer` (classes/output/renderer.php)
Extends `plugin_renderer_base`. Single method `render_catalog_page()`.

## Access control

`index.php` uses the dual admin/direct pattern (same as `local_lernhive_copy`):
- Site admin → `admin_externalpage_setup('local_lernhive_library_catalog')`
- Others → `require_login()` + `require_capability('local/lernhive_library:import')`
  against `core\context\system::instance()`

The `:import` capability is declared in `db/access.php` with
`archetypes: [coursecreator => CAP_ALLOW]`.

## Dependencies

- `local_lernhive_contenthub` (hard, declared in version.php)
- eLeDia managed catalog API — **R2 only**, not used in R1

## R2 direction

- Connect `catalog` to the managed catalog backend (API or file manifest)
- `.mbz` download + import via Moodle's backup/restore API
- Version metadata: show available vs installed version per entry
- Update workflow: safe import of a new `.mbz` without destructive overwrite
- Richer update UX: lifecycle comparison, update decision dialog

## Privacy

`null_provider`. If import history or per-user preferences are ever stored,
the provider must be upgraded before that state ships.

## CI & deployment

Same gate as all R1 LernHive plugins: `deploy-hetzner.yml` (see
contenthub `03-dev-doc.md`). Local dev via `moodle-deploy` skill.
