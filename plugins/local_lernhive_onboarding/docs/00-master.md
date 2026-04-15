# local_lernhive_onboarding - Master Document

**Plugin type:** local plugin  
**Release target:** R1

## Purpose

Guided onboarding journeys for LernHive users through Moodle user tours (`tool_usertours`).

## Current delivery status (0.2.8)

Implemented and shipped:
- Trainer learning-path dashboard banner on `/my/`
- Dedicated `lernhive_trainer` role + capability gate
- Tours overview page with Level-1 progress cards
- Deterministic tour start (`start_url` -> `starttour.php` -> `_requested` replay flag)
- Onboarding Sandbox course (`{DEMOCOURSEID}`)
- Admin setting for trainer target course category (`{TRAINERCOURSECATEGORYID}`)

## 0.3.x target scope (planned)

Planned next:
- feature-registry-driven visibility (consumer of `local_lernhive` ADR-01)
- `feature_id` on category-tour mappings
- Level-2 pack activation and remaining Level-3..5 content packs
- chained multi-page learning units (`prereq` + successor activation)
- LXP audience extension via flavour presets

## Role in LernHive architecture

This plugin is a consumer layer:
- Uses Moodle core user tours for delivery
- Uses `local_lernhive` level model for progression
- Should not duplicate level-rights business logic outside the shared feature registry
- Keeps UX guidance in plugin/theme layer, not in Moodle core modifications

## Main dependencies

- `local_lernhive`
- Moodle core `tool_usertours`
- `theme_lernhive` (visual shell classes)

## DevFlow files

- `00-master.md`
- `01-features.md`
- `02-user-doc.md`
- `03-dev-doc.md`
- `04-tasks.md`
- `05-quality.md`
