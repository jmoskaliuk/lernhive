# local_lernhive_contenthub — Developer Documentation

## Architecture note
Unified content entry UI for copy, template, library and AI.

ContentHub is **orchestration only**: it owns no content, writes no
database rows, and stores no user data. Every visible card delegates
to a sibling plugin that implements the actual flow.

## Technical direction
- keep boundaries clean
- use Moodle APIs where possible
- prefer existing core strings over plugin-specific duplicates
- keep data model minimal for release 1
- document release 2 complexity separately

## Current dependencies
- `local_lernhive` (hard, declared in version.php)
- `local_lernhive_copy` (soft — card renders as "Unavailable" if missing;
  shipped as R1 stub, entry URL `/local/lernhive_copy/index.php`,
  template card uses `?source=template` on the same plugin)
- `local_lernhive_library` (soft — same pattern; shipped as R1 stub,
  entry URL `/local/lernhive_library/index.php`)
- `local_lernhive_launcher` (planned — the launcher will link here as
  its primary content-creation entry point)

## Integration points
- Moodle core APIs (renderer, mustache, capabilities, privacy null_provider)
- `core_component::get_plugin_directory()` for soft-dependency detection
- theme integration only for styling, not for business logic

## R1 file layout
```
local_lernhive_contenthub/
├── version.php                 component + deps (local_lernhive)
├── lib.php                     empty hook slot
├── index.php                   entry page (dual admin/standard)
├── settings.php                admin_externalpage registration
├── styles.css                  scoped .lh-contenthub-* only
├── README.md                   plugin overview
├── db/access.php               :view capability (cloned from course:create)
├── lang/en/*.php               English strings
├── classes/
│   ├── card.php                immutable card value object
│   ├── card_registry.php       orchestration / plugin detection
│   ├── output/hub_page.php     renderable / templatable
│   ├── output/renderer.php     plugin renderer
│   └── privacy/provider.php    null_provider
├── templates/hub_page.mustache single template
└── docs/                       DevFlow (00-master … 05-quality)
```

## Card orchestration rules
- Card order is fixed in `card_registry::get_cards`:
  Copy → Template → Library → (optional) AI. Changing the order
  requires updating the feature doc, the mockup and this file together.
- The registry accepts an **injectable plugin detector** (`callable`)
  so unit tests can assert the install-detection matrix without
  actually installing or removing sibling plugins. Production code
  passes `null` and gets a default detector backed by
  `core_component::get_plugin_directory()`.
- A card's `status` is a pure function of:
  1. whether the sibling plugin is installed (via the detector)
  2. whether the current user holds the relevant capability
  (ContentHub itself does not re-check the sibling's capabilities —
  the sibling plugin enforces them on its own entry page.)
- The AI card is **gated behind an admin setting**
  (`local_lernhive_contenthub/show_ai_card`, default off). When the
  setting is on, the card is always rendered as `STATUS_COMING_SOON`
  in R1 because no AI-backed creation plugin exists yet.
- Template is not its own sibling plugin — it is a card that links
  to `local/lernhive_copy/index.php?source=template`. The copy plugin
  normalises the query param via `classes/source.php`.

## Privacy
`local_lernhive_contenthub\privacy\provider` is a `null_provider`.
If ContentHub ever starts tracking which card a user clicked (for
telemetry or personalization), the provider must be upgraded to a
real metadata provider **before** the tracking code ships.

## CI & deployment
- `.github/workflows/moodle-plugin-ci.yml` runs moodle-plugin-ci
  against this plugin on every push / PR touching
  `plugins/local_lernhive_contenthub/**`.
- `.github/workflows/deploy-hetzner.yml` (existing, repo-wide) deploys
  the plugin to the Hetzner staging server on push to `main`.
- There is intentionally no local Docker / Orb deploy step for this
  plugin — GitHub Actions is the canonical deploy path.
