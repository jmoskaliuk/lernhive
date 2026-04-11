# local_lernhive_contenthub — Quality

## Quality goals
- terminology is consistent
- UX stays simple
- strings are reusable and localizable
- feature works on desktop and smaller screens
- no unnecessary duplication of Moodle core logic
- privacy-by-default: the plugin must stay a `null_provider` for as
  long as it does not store user data

## Checks
- accessibility basics
  - cards have `role="listitem"` inside a `role="list"` container
  - status badges are plain text (no icon-only signals)
  - disabled call-to-actions use both `disabled` and `aria-disabled`
- responsive checks
  - cards collapse to a single column below 600px (see styles.css
    media query)
- role/permission checks
  - `local/lernhive_contenthub:view` is cloned from
    `moodle/course:create` so sites that already customised the
    course-create capability inherit the same rule set
- language string review
  - all strings are plugin-local but intentionally cover the LernHive
    product terms (ContentHub, Template, Library). Any string that
    duplicates a Moodle core concept must be removed during review.

## CI gates
The only automated gate in R1 is `.github/workflows/deploy-hetzner.yml`,
which runs on every push to `main` that touches `plugins/**`. It rsyncs
the plugin into the `lernhive-webserver-1` container, runs
`admin/cli/upgrade.php` and `admin/cli/purge_caches.php`, and fails the
run if either exits non-zero. That covers the bar for R1: the plugin
installs into a real Moodle 5.x tree, the upgrade script accepts the new
version.php, and caches stay purgeable. A dedicated `moodle-plugin-ci`
matrix (phplint, phpcs, phpdoc, phpunit, behat) is deliberately
postponed until the plugin stops churning, so that we only introduce the
strict checks once violations can be fixed without blocking the roadmap.

Until the matrix exists, authors are expected to run phpunit and behat
locally via the `moodle-deploy` skill before pushing anything non-trivial
(see `04-tasks.md`).

## Known limitations
- the AI card is hardcoded as "coming soon" in R1, hidden behind an
  admin setting by default
- Template is a sub-action of `local_lernhive_copy` rather than a
  separate plugin (see `03-dev-doc.md` for the rationale)
- the direct-URL Behat scenario for course creators is deferred until
  the launcher plugin provides the navigation path (see the header
  comment in `tests/behat/hub_page.feature`)
