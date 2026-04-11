# local_lernhive_contenthub — Master Document

**Plugin type:** local plugin
**Release target:** R1 — code-complete for orchestration as of 2026-04-11

## Purpose

Unified content entry UI for Copy, Template, Library and (opt-in) AI.
ContentHub is orchestration only: it owns no content and stores no
user data. Every visible card delegates to a sibling plugin.

## Role in LernHive

This plugin is part of the LernHive ecosystem and should fit the core rules:
- Moodle stays the core
- English-first terminology
- use Moodle core strings wherever possible
- no business logic in the theme
- must stay understandable for UX, docs, marketing and sales

## Main dependencies
- `local_lernhive` — **hard**, declared in `version.php` as
  `$plugin->dependencies['local_lernhive'] = 2026040901`
- `local_lernhive_copy` — **soft**, detected at runtime; card falls
  back to "Unavailable" if the plugin is not installed
- `local_lernhive_library` — **soft**, same pattern
- `local_lernhive_launcher` — **planned**; once it lands it will link
  to ContentHub as the primary content-creation entry point, but
  ContentHub itself has no reverse dependency on the launcher

## Main features
- single entry screen rendered via `admin_externalpage` (admin tree)
  and directly at `/local/lernhive_contenthub/index.php` for authors
- fixed card order: Copy → Template → Library → (optional) AI
- per-card status resolved by `card_registry` with an injectable
  plugin detector (production uses `core_component::get_plugin_directory`)
- AI card hidden behind an admin setting, default off
- English-first labels, no flavour-specific wording

## DevFlow files
- 00-master.md
- 01-features.md
- 02-user-doc.md
- 03-dev-doc.md
- 04-tasks.md
- 05-quality.md
