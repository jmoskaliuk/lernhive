# local_lernhive_library — Quality

## Quality goals

- terminology is consistent (Library, catalog, import — not "backup/restore")
- UX stays simple and mobile-friendly
- strings are reusable and localizable
- no unnecessary duplication of Moodle core logic
- privacy-by-default: `null_provider` until import history is stored

## Checks

- accessibility: catalog list uses semantic list markup; empty state is
  clearly communicated, not just a blank page
- responsive: catalog grid collapses gracefully on smaller screens
- role/permission checks: `:import` capability enforced on direct URL,
  standard layout used consistently for all users (including admin-tree entry)
- language string review: empty-state string, page title — no duplicates
  of Moodle core strings
- PHPUnit contract checks:
  - `tests/catalog_test.php` (catalog + catalog_entry value contract)
  - `tests/catalog_page_test.php` (catalog_page mustache context contract)
  - constructor guard: seeded catalog data must be `catalog_entry[]`
    (invalid element types fail fast with `coding_exception`)
  - constructor guard: `catalog_entry` required fields must be non-blank
    and `updated` must be non-negative
  - presentation normalisation: language display value is `trim + uppercase`
  - context consistency: `catalog_page` derives `empty` from exported entries
- Behat smoke check:
  - `tests/behat/library_page.feature` validates admin navigation and empty-state visibility

## CI gate

Repository-level gates for R1:
- `deploy-hetzner.yml` (deploy automation)
- `test-hetzner.yml` (nightly/manual PHPUnit + Behat runs)

A dedicated `moodle-plugin-ci` matrix is deferred until plugin/API churn
stabilises — see contenthub `05-quality.md` for rationale.
