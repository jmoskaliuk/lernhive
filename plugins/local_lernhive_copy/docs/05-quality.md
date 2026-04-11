# local_lernhive_copy — Quality

## Quality goals

- terminology is consistent (Copy, Template — not "Backup", "Restore")
- UX stays simple and mobile-friendly
- strings are reusable and localizable
- no unnecessary duplication of Moodle core logic
- privacy-by-default: `null_provider` until user data is stored

## Checks

- accessibility: form controls and headings use proper labelling
- responsive: wizard layout readable below 600px
- role/permission checks: `:use` capability enforced on direct URL,
  admin layout used when accessed via admin tree
- language string review: page_title_* and page_intro_* keys cover both
  source modes; no duplicates of Moodle core strings

## CI gate

Same as all R1 LernHive plugins: `deploy-hetzner.yml` is the only
automated gate (rsync + CLI upgrade + purge). A dedicated moodle-plugin-ci
matrix is deferred until the plugin stops churning — see contenthub
`05-quality.md` for the full rationale.
