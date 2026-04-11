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
- `moodle-plugin-ci phplint / phpcs --max-warnings 0 / phpdoc`
- `moodle-plugin-ci validate / savepoints / mustache / grunt`
- `moodle-plugin-ci phpunit --fail-on-warning`
- `moodle-plugin-ci behat --profile chrome`

## Known limitations
- the AI card is hardcoded as "coming soon" in R1, hidden behind an
  admin setting by default
- Template is a sub-action of `local_lernhive_copy` rather than a
  separate plugin (see `03-dev-doc.md` for the rationale)
- the direct-URL Behat scenario for course creators is deferred until
  the launcher plugin provides the navigation path (see the header
  comment in `tests/behat/hub_page.feature`)
