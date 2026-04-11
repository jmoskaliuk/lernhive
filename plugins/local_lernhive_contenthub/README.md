# local_lernhive_contenthub

**LernHive ContentHub** — the unified content entry UI for Copy, Template, Library and (later) AI-backed creation.

ContentHub is **orchestration only**. It does not own any content, does not touch the database, and does not duplicate sibling-plugin logic. Each card on the hub page delegates to the plugin that actually implements the flow:

| Card     | Delegates to                                   | R1 status     |
|----------|-------------------------------------------------|---------------|
| Copy     | `local_lernhive_copy`                           | Available     |
| Template | `local_lernhive_copy` (with `source=template`)  | Available     |
| Library  | `local_lernhive_library`                        | Available     |
| AI       | —                                               | Coming soon   |

If a sibling plugin is not installed on the site, the matching card renders as **Unavailable** — the hub page itself stays operational.

## Access

The hub is reachable by anyone with `local/lernhive_contenthub:view`. That capability is cloned from `moodle/course:create`, so the default archetypes that match are `editingteacher`, `coursecreator`, and `manager`. Site admins reach the page through *Site administration → Plugins → Local plugins → LernHive ContentHub*.

Entry URL: `/local/lernhive_contenthub/index.php`

## Architecture

```
local_lernhive_contenthub/
├── version.php                 — component, deps (local_lernhive)
├── lib.php                     — empty hook slot
├── index.php                   — entry page (dual admin/standard layout)
├── settings.php                — admin_externalpage registration
├── styles.css                  — scoped .lh-contenthub-* styles only
├── db/access.php               — :view capability
├── lang/en/*.php               — English strings (LernHive product terms)
├── classes/
│   ├── card.php                — immutable card value object
│   ├── card_registry.php       — orchestration (installed-plugin detection)
│   ├── output/
│   │   ├── hub_page.php        — renderable / templatable
│   │   └── renderer.php        — plugin renderer
│   └── privacy/provider.php    — null_provider (no personal data)
└── templates/hub_page.mustache — single mustache template
```

Key design rules (see `AGENTS.md` in the repo root):

- No business logic in this plugin. Cards are a pure projection of installed sibling plugins + static strings.
- English-first strings. Each LernHive term (ContentHub, Template, Library) maps 1:1 to a string key.
- All CSS is scoped to `.lh-contenthub-*`. The plugin intentionally cannot leak styles into the theme.
- Null privacy provider: the plugin stores nothing.

## Release scope

- **R1** — four cards (Copy, Template, Library, AI-coming-soon), mobile-friendly layout, no DB writes.
- **R2+** — AI creation path, possible audience-aware card visibility, telemetry about which card was chosen (requires a non-null privacy provider).

See `docs/` for the full DevFlow (00-master → 05-quality).

## Dependencies

- `local_lernhive` ≥ 2026040901 (shared product base)
- Soft runtime dependencies (detected at render time, not enforced in `version.php`):
  - `local_lernhive_copy`
  - `local_lernhive_library`
- The `local_lernhive_launcher` plugin (planned) will link to ContentHub as its primary content-creation entry point once it ships.

## CI

The monorepo runs [`moodle-plugin-ci`](https://github.com/moodlehq/moodle-plugin-ci) against this plugin via `.github/workflows/moodle-plugin-ci.yml` on every push and pull request that touches `plugins/local_lernhive_contenthub/**`. Deployment to the Hetzner staging server is handled by `deploy-hetzner.yml` on pushes to `main`.
