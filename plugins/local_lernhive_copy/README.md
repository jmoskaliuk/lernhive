# local_lernhive_copy

**LernHive Copy** is the course-copy wizard used from ContentHub. It reuses
Moodle core backup/restore (`copy_helper`) instead of implementing custom copy
logic.

Current version (`0.3.0`) ships:
- working Simple copy flow
- Expert mode hand-off to Moodle core copy UI
- template selection backed by the LernHive Library catalog
- per-user default target-category preference

## Entry paths

| Entry URL | Source | Heading |
|---|---|---|
| `/local/lernhive_copy/index.php` | course (default) | *Copy a course* |
| `/local/lernhive_copy/index.php?source=template` | template | *Start from a template* |

Both paths share one wizard page. `classes/source.php` normalises `?source=`
and defaults unknown values to `course`.

## Modes

- **Simple**: guided copy form, defaults to clean copy without participants/progress.
- **Expert**: source-picker hand-off to Moodle's full `/backup/copy.php` flow.

## Template flow

`?source=template` is a two-step flow:
1. pick a template from the Library-backed catalog
2. continue with Simple or Expert mode using the mapped source course

## Runtime behavior

- always requires login + `local/lernhive_copy:use`
- always renders with `pagelayout=standard` (including site admins)
- validates source-course backup/restore capabilities before queueing copy
- cancel path always returns to `/local/lernhive_contenthub/index.php`
- stores the selected target category as user preference
- redirects to `/backup/copyprogress.php?id={sourcecourseid}` after submit

## Access

Capability: `local/lernhive_copy:use`, cloned from `moodle/course:create`.
Default archetypes: `editingteacher`, `coursecreator`, `manager`.

## Architecture

```
local_lernhive_copy/
├── version.php                 depends on local_lernhive_contenthub
├── lib.php
├── index.php                   entry page + form handling
├── settings.php                admin_externalpage registration
├── styles.css                  scoped .lh-copy-* only
├── README.md
├── db/access.php
├── lang/en/*.php
├── classes/
│   ├── source.php              normalises ?source=course|template
│   ├── form/copy_form.php      simple-mode form
│   ├── form/expert_source_form.php
│   ├── output/
│   │   ├── wizard_page.php     renderable / templatable
│   │   └── renderer.php
│   └── privacy/provider.php    metadata + user preferences
├── templates/wizard_page.mustache
├── tests/source_test.php       source routing contract
├── tests/form/copy_form_test.php
├── tests/output/wizard_page_test.php
├── tests/behat/copy_flow.feature
└── docs/                       DevFlow
```

## DevFlow

- `docs/00-master.md`
- `docs/01-features.md`
- `docs/02-user-doc.md`
- `docs/03-dev-doc.md`
- `docs/04-tasks.md`
- `docs/05-quality.md`

## CI & deployment

Current automated gate is the repo-level `deploy-hetzner.yml`.
