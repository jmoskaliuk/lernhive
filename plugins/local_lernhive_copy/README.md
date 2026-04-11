# local_lernhive_copy

**LernHive Copy** — the course copy wizard. In Release 1 this is a UI stub: the page renders the mode choices and an explicit "not yet implemented" notice. The actual wizard will delegate to Moodle core backup/restore in a later milestone.

The plugin serves two content paths from the [ContentHub](../local_lernhive_contenthub/):

| Entry URL                                         | Source            | Heading                |
|---------------------------------------------------|-------------------|------------------------|
| `/local/lernhive_copy/index.php`                  | course (default)  | *Copy a course*        |
| `/local/lernhive_copy/index.php?source=template`  | template          | *Start from a template*|

Both paths share the same wizard code. `classes/source.php` normalises the query parameter and guards against stray values.

## Modes
- **Simple** — copy structure and activities, skip participants / grades / attempt data
- **Expert** — hand off to Moodle's core backup/restore screen with all options

Both CTAs are disabled in the R1 stub — see `docs/04-tasks.md` for the wire-up plan.

## Access

Capability: `local/lernhive_copy:use`, cloned from `moodle/course:create`. Default archetypes: editingteacher, coursecreator, manager.

## Architecture

```
local_lernhive_copy/
├── version.php                 depends on local_lernhive_contenthub
├── lib.php
├── index.php                   entry page (dual admin/standard)
├── settings.php                admin_externalpage registration
├── styles.css                  scoped .lh-copy-* only
├── README.md
├── db/access.php
├── lang/en/*.php
├── classes/
│   ├── source.php              normalises ?source=course|template
│   ├── output/
│   │   ├── wizard_page.php     renderable / templatable
│   │   └── renderer.php
│   └── privacy/provider.php    null_provider (delegates to backup/restore)
├── templates/wizard_page.mustache
├── tests/source_test.php       unit tests for source normalisation
└── docs/                       DevFlow
```

## CI & deployment

Runs through the shared `.github/workflows/moodle-plugin-ci.yml` workflow (matrix-extended). Deployment to Hetzner is handled by the repo-wide `deploy-hetzner.yml`.
