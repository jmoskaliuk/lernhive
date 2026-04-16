# local_lernhive_library

**LernHive Library** — the managed catalog of ready-to-use courses curated by eLeDia. R2 phase 2 adds a read-only remote feed source with strict fail-closed parsing. Import actions remain intentionally disabled until the backup/restore workflow lands.

The plugin is reached from the [ContentHub](../local_lernhive_contenthub/) via the Library card, and from the admin tree under *Site administration → Plugins → Local plugins → LernHive Library → Open LernHive Library*.

## Current scope (R2 phase 2)

- Catalog page renders via renderer + mustache template (shared UX with ContentHub / Copy)
- Catalog source supports:
  - seeded in-memory entries (test mode)
  - remote managed feed (`catalog_feed_url`, optional bearer token)
  - local JSON manifest fallback (`catalog_manifest_json`)
- Catalog entry is an immutable value object (`classes/catalog_entry.php`) defining the contract the eventual managed backend must satisfy
- Shared parser accepts:
  - top-level JSON array of entries
  - object with `entries` array
- Optional `sourcecourseid` entry field enables template hand-off in
  `local_lernhive_copy`
- Invalid rows fail closed and are ignored (developer debugging notice); valid rows still render
- Null privacy provider — the plugin stores no personal data in this phase
- No web services, no scheduled tasks, no DB tables

The import button is still rendered but disabled. Wiring it up to Moodle core backup/restore is tracked in `docs/04-tasks.md`.

## Architecture

```
local_lernhive_library/
├── version.php                 depends on local_lernhive_contenthub
├── lib.php
├── index.php                   entry page (standard layout + capability gate)
├── settings.php                admin category + open page + remote + fallback settings
├── styles.css                  scoped .lh-library-* only
├── README.md
├── db/access.php               local/lernhive_library:import
├── lang/en/*.php
├── classes/
│   ├── catalog.php             source selector + injectable test source
│   ├── catalog_source.php      source contract
│   ├── catalog_manifest_parser.php shared JSON parser
│   ├── manifest_catalog_source.php manifest source implementation
│   ├── remote_catalog_source.php remote feed source implementation
│   ├── catalog_entry.php       immutable value object
│   ├── output/
│   │   ├── catalog_page.php    renderable / templatable
│   │   └── renderer.php
│   └── privacy/provider.php    null_provider
├── templates/catalog_page.mustache
├── tests/
│   ├── catalog_test.php        unit tests for catalog + catalog_entry
│   ├── catalog_page_test.php   unit tests for catalog_page context export
│   └── behat/
│       ├── library_page.feature
│       └── behat_local_lernhive_library.php
└── docs/                       DevFlow
```

## Access

Capability: `local/lernhive_library:import`, cloned from `moodle/course:create`. Default archetypes: editingteacher, coursecreator, manager.

## Dependencies

`local_lernhive_contenthub ≥ 2026041002` — the Library card in the Hub needs a target URL that resolves to this plugin, so the hub's detection logic uses `local_lernhive_library` as a signal.

Downstream integration:
- `local_lernhive_copy` can consume catalog `sourcecourseid` mappings in template mode.

## CI & deployment

Repository-level workflows:
- `.github/workflows/deploy-hetzner.yml` deploys to Hetzner (push to `main` + manual dispatch)
- `.github/workflows/test-hetzner.yml` runs PHPUnit/Behat on Hetzner (nightly + manual dispatch)

No dedicated `moodle-plugin-ci` matrix is currently wired for this plugin.

## Roadmap

- **R2 phase 3** — import selected entries via Moodle core backup/restore
- **R2+** — per-user "recently imported" history (replaces the null privacy provider with a real metadata provider)
- **R3** — flavour-aware filtering of the catalog based on the tenant's active LernHive flavour
