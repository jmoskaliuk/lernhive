# local_lernhive_contenthub — Features

## Feature summary
Unified content entry UI that orchestrates Copy, Template, Library and
(optionally) AI-backed creation. ContentHub owns no content itself —
every card hands off to a sibling plugin.

## R1 feature set
- single entry screen rendered via `admin_externalpage` (admin tree)
  and directly at `/local/lernhive_contenthub/index.php`
- fixed card order: Copy → Template → Library → (opt-in) AI
- per-card status (`available`, `disabled`, `coming_soon`) resolved by
  `card_registry` via an injectable plugin detector
- Copy card links to `/local/lernhive_copy/index.php`
- Template card links to `/local/lernhive_copy/index.php?source=template`
  — same wizard, different heading/intro; disambiguation is done by
  `local_lernhive_copy\source::from_request`
- Library card links to `/local/lernhive_library/index.php`
- AI card is **hidden by default**, opt-in via
  `local_lernhive_contenthub/show_ai_card`; when enabled, renders as
  `coming_soon` because no AI-backed creation plugin exists in R1

## Acceptance direction
- feature behaviour should be explicit and understandable
- strings should reuse Moodle core when possible
- UX should stay simple and mobile-friendly (single-column below 600px)
- flavour-specific wording should be used only if really necessary
- ContentHub must never persist user decisions — the sibling plugin it
  hands off to is the owner of that flow

## Release note
Target release: R1 — code-complete as of 2026-04-11 (commits
`9475cf5`, `2ab4cbf`)
