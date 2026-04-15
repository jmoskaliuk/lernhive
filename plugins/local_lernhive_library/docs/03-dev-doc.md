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
├── index.php                   entry page — standard layout + capability gate
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
Placeholder provider. Constructor accepts `catalog_entry[]` (empty by
default). `all(): catalog_entry[]` and `is_empty(): bool`. In R2 this
class will be replaced by or delegated to a real source (API client,
file-based manifest, etc.); the `catalog_page` renderable is written
against this interface already so the swap should be contained here.
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
  - `tests/behat/library_page.feature` (admin-tree smoke test for R1 empty state)

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

Repository-level workflows:
- `deploy-hetzner.yml` (push to `main` + manual dispatch) deploys to Hetzner
- `test-hetzner.yml` (nightly + manual dispatch) runs PHPUnit and Behat on Hetzner

There is no dedicated `moodle-plugin-ci` matrix for this plugin in R1.
Local dev via `moodle-deploy` skill.
