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
  admin layout used when accessed via admin tree
- language string review: empty-state string, page title — no duplicates
  of Moodle core strings

## CI gate

Same as all R1 LernHive plugins: `deploy-hetzner.yml` is the only
automated gate (rsync + CLI upgrade + purge). A dedicated moodle-plugin-ci
matrix is deferred until the plugin stops churning — see contenthub
`05-quality.md` for the full rationale.
