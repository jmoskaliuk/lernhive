# local_lernhive_library — Developer Documentation

## Architecture note

Entry page for browsing eLeDia's managed content catalog. R2 phase 2 ships
a **read-only remote feed source** with local manifest fallback. The `.mbz`
import pipeline is still not connected. The data model for a catalog entry
(`catalog_entry`) remains a strict value object so the contract with the
managed backend stays explicit.

## R2 phase 2 scope: source abstraction + remote feed

R2 phase 2 ships:
- `catalog_source` abstraction for pluggable catalog providers
- `remote_catalog_source` as primary source when `catalog_feed_url` is configured
- `manifest_catalog_source` as fallback when no remote feed URL is configured
- shared `catalog_manifest_parser` for strict value-object decoding
- invalid rows are ignored (fail closed), valid rows still render
- constructor injection of `catalog_entry[]` and `catalog_source` remains available for tests
- optional `sourcecourseid` enables template hand-off to `local_lernhive_copy`
- `catalog_page` still renders empty state when no valid entries exist

`.mbz` delivery approach, version comparison against installed content, and
update execution UX are still deferred to later R2 phases (see below).

## File layout

```
local_lernhive_library/
├── version.php                 component + deps (local_lernhive_contenthub)
├── lib.php                     empty hook slot
├── index.php                   entry page — standard layout + capability gate
├── settings.php                admin category + open page + feed/fallback settings
├── styles.css                  scoped .lh-library-* only
├── README.md
├── db/access.php               local/lernhive_library:import capability
├── lang/en/local_lernhive_library.php
├── classes/
│   ├── catalog.php             source selection + injectable in-memory provider
│   ├── catalog_source.php      source interface
│   ├── catalog_manifest_parser.php shared strict parser
│   ├── manifest_catalog_source.php local manifest source
│   ├── remote_catalog_source.php remote feed source
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
(semver-like string), `updated` (unix timestamp), `language` (ISO code),
optional `sourcecourseid` (mapped Moodle source course id).
`to_template_context()` formats `updated` via Moodle's `userdate()` and
normalises `language` to trimmed upper-case. This class defines what the R2
backend source must return — entries are created only inside source/parsing
classes (`catalog_manifest_parser` and source implementations).
Constructor guards validate required fields (`id`, `title`, `version`,
`language`) and reject negative `updated` timestamps with `coding_exception`.

### `catalog` (classes/catalog.php)
Provider with three modes:
- constructor-seeded entries (`catalog_entry[]`) for deterministic tests
- explicit source injection (`catalog_source`) for deterministic tests/integration
- production source selection:
  - explicit manifest override argument
  - remote feed when `catalog_feed_url` is configured
  - local manifest fallback from `catalog_manifest_json`

`all(): catalog_entry[]` and `is_empty(): bool`.
Lookup helper: `find_by_id(string $id): ?catalog_entry`.

Parsing behaviour:
- accepts top-level array or object with `entries`
- supports unix timestamps and parseable date strings in `updated`
- supports optional positive integer `sourcecourseid`
- invalid rows are skipped with developer debug notice

Seeded constructor data is validated: non-`catalog_entry` elements raise
`coding_exception` so contract violations fail fast in tests/dev.

### `remote_catalog_source` (classes/remote_catalog_source.php)
Read-only remote source for managed catalog feeds. Reads:
- `catalog_feed_url` (required for remote mode)
- `catalog_feed_token` (optional bearer token)

HTTP failures or invalid payloads fail closed and return an empty set
instead of rendering broken entries.

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
- Managed feed source (phase 2): remote feed URL + optional token
- Local manifest fallback: admin-configured JSON in plugin settings

## R2 direction

Execution order for next increments:
1. Define import-service boundaries before wiring Moodle backup/restore
2. Add `.mbz` download + import via Moodle's backup/restore API
3. Version metadata: show available vs installed version per entry
4. Update workflow: safe import of a new `.mbz` without destructive overwrite
5. Richer update UX: lifecycle comparison, update decision dialog

## Privacy

`null_provider`. If import history or per-user preferences are ever stored,
the provider must be upgraded before that state ships.

## CI & deployment

Repository-level workflows:
- `deploy-hetzner.yml` (push to `main` + manual dispatch) deploys to Hetzner
- `test-hetzner.yml` (nightly + manual dispatch) runs PHPUnit and Behat on Hetzner

There is no dedicated `moodle-plugin-ci` matrix for this plugin in the current phase.
Local dev via `moodle-deploy` skill.
