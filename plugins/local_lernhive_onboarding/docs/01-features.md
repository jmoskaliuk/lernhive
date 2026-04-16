# local_lernhive_onboarding - Features

## Feature summary

Guided onboarding learning path for LernHive users, currently focused on trainer onboarding and Level-1 delivery.

## Implemented (0.3.0-dev)

- Dashboard banner on `/my/` for eligible users
- Capability-gated audience via `local/lernhive_onboarding:receivelearningpath`
- Tours catalog page (`/local/lernhive_onboarding/tours.php`) with category progress
- Deterministic tour start from catalog:
  - reads `lh_start_url` from tour `configdata`
  - resolves placeholders (`{USERID}`, `{SITEID}`, `{SYSCONTEXTID}`, `{DEMOCOURSEID}`, `{TRAINERCOURSECATEGORYID}`)
  - primes replay state using Moodle-native `_requested`
  - enforces exact tour on first redirected request via one-shot forced-tour filter
  - shows a completion dialog after tour end with:
    - `Back to onboarding`
    - `Stay here`
  - uses Moodle modal stack (`core/modal_factory`) with a scoped LernHive dialog class for pattern-conform styling
- Feature-mapping groundwork:
  - supports optional `lernhive_feature` in tour JSON
  - persists to `local_lhonb_map.feature_id`
- Level-1 tour content pack imported on install
- Level-1 runtime hotfixes:
  - backfilled stale `pathmatch` values from JSON source of truth
  - retargeted self-enrol selectors for `/enrol/instances.php`
  - moved course-create intro step anchor away from `fullname` input overlap
- Sandbox course provisioning for safe course-context tour targets

## Planned (0.3.x)

- Feature-addressable tour visibility:
  - consume `local_lernhive\feature\registry`
- Level-2 onboarding pack runtime activation (files exist, runtime integration incomplete)
- Remaining Level-3..5 tour packs
- Tour chaining across multiple pages (`prereq`, successor activation)
- LXP flavour audience extension

## Acceptance direction

- No Moodle fork, no custom tour engine
- Tour visibility reflects effective product rights
- Start-from-catalog works deterministically and replay-safe
- UX remains simple, calm, mobile-friendly
- Terminology stays aligned with product language rules
