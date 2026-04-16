# local_lernhive_library — Developer Documentation

## Architecture note

Entry page for browsing eLeDia's managed content catalog. R2 phase 1 ships
a **read-only JSON manifest feed** that can be configured in plugin
settings. The `.mbz` import pipeline is still not connected. The data model
for a catalog entry (`catalog_entry`) remains a strict value object so the
contract with the eventual managed backend stays explicit.

## R2 phase 1 scope: read-only manifest feed

R2 phase 1 ships:
- `catalog` can parse manifest JSON from plugin settings (`catalog_manifest_json`)
- parser accepts top-level array or `{ "entries": [...] }`
- invalid rows are ignored (fail closed), valid rows still render
- constructor injection of `catalog_entry[]` remains available for tests
- `catalog_page` still renders empty state when no valid entries exist

`.mbz` delivery approach, version comparison against installed content, and
update execution UX are still deferred to later R2 phases (see below).

## File layout

```
local_lernhive_library/
├── version.php                 component + deps (local_lernhive_contenthub)
├── lib.php                     empty hook slot
├── index.php                   entry page — standard layout + capability gate
├── settings.php                admin category + open page + manifest setting
├── styles.css                  scoped .lh-library-* only
├── README.md
├── db/access.php               local/lernhive_library:import capability
├── lang/en/local_lernhive_library.php
├── classes/
│   ├── catalog.php             manifest parser + injectable in-memory provider
│   ├── catalog_entry.php       immutable value object — defines backend contract
│   ├── output/catalog_page.php renderable / templatable
│   ├── output/renderer.php     plugin renderer → render_catalog_page()
│   └── privacy/provider.php    null_provider
├── templates/catalog_page.mustache
└── tests/
    ├── catalog_test.php        PHPUnit: catalog + catalog_entry contract
    ├── catalog_page_test.php   PHPUnit: catalog_page template context contract
    └── behat/
        ├── library_page.feature
        └── behat_local_lernhive_library.php
```

## Key classes

### `catalog_entry` (classes/catalog_entry.php)
Immutable value object. Fields: `id`, `title`, `description`, `version`
(semver-like string), `updated` (unix timestamp), `language` (ISO code).
`to_template_context()` formats `updated` via Moodle's `userdate()` and
normalises `language` to trimmed upper-case. This class defines what the R2 backend source must
return — no code outside `catalog.php` should construct entries.
Constructor guards validate required fields (`id`, `title`, `version`,
`language`) and reject negative `updated` timestamps with `coding_exception`.

### `catalog` (classes/catalog.php)
Provider with two modes:
- constructor-seeded entries (`catalog_entry[]`) for deterministic tests
- config-backed manifest parsing from `local_lernhive_library/catalog_manifest_json`

`all(): catalog_entry[]` and `is_empty(): bool`.

Manifest behaviour:
- accepts top-level array or object with `entries`
- supports unix timestamps and parseable date strings in `updated`
- invalid rows are skipped with developer debug notice

Seeded constructor data is validated: non-`catalog_entry` elements raise
`coding_exception` so contract violations fail fast in tests/dev.

### `catalog_page` (classes/output/catalog_page.php)
Renderable + templatable. Constructor receives a `catalog` instance;
`export_for_template()` maps entries through `to_template_context()`.
The context contract is locked by `tests/catalog_page_test.php`.
The `empty` flag is derived from exported `entries` to keep both values in sync.

### `renderer` (classes/output/renderer.php)
Extends `plugin_renderer_base`. Single method `render_catalog_page()`.

## Testing

- PHPUnit:
  - `tests/catalog_test.php`
  - `tests/catalog_page_test.php`
- Behat:
  - `tests/behat/library_page.feature` (admin-tree smoke test for page reachability and baseline copy)

## Access control

`index.php` uses one access path for all users:
- `require_login()`
- `require_capability('local/lernhive_library:import', core\context\system::instance())`
- `pagelayout='standard'` for everyone (no `admin_externalpage_setup()`)

`settings.php` still registers `local_lernhive_library_catalog` as an
admin external page so site admins can discover the entry via admin search.
Opening that link still lands on the same standard-layout page.

The `:import` capability is declared in `db/access.php` with
`archetypes: [editingteacher => CAP_ALLOW, coursecreator => CAP_ALLOW, manager => CAP_ALLOW]`.

## Dependencies

- `local_lernhive_contenthub` (hard, declared in version.php)
- Managed feed source (phase 1): admin-configured JSON manifest in plugin settings
- eLeDia managed remote API client: planned for next R2 phase

## R2 direction

- Replace pasted manifest JSON with managed remote feed retrieval
- `.mbz` download + import via Moodle's backup/restore API
- Version metadata: show available vs installed version per entry
- Update workflow: safe import of a new `.mbz` without destructive overwrite
- Richer update UX: lifecycle comparison, update decision dialog

## Privacy

`null_provider`. If import history or per-user preferences are ever stored,
the provider must be upgraded before that state ships.

## CI & deployment

Repository-level workflows:
- `deploy-hetzner.yml` (push to `main` + manual dispatch) deploys to Hetzner
- `test-hetzner.yml` (nightly + manual dispatch) runs PHPUnit and Behat on Hetzner

There is no dedicated `moodle-plugin-ci` matrix for this plugin in the current phase.
Local dev via `moodle-deploy` skill.
