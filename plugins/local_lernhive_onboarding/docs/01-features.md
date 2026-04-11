# local_lernhive_onboarding — Features

## Feature summary
Guided Onboarding Learning Path for LernHive trainers (and, in the LXP flavor, participants). Delivers level-linked tour packs and a dashboard banner; reads all feature → level mappings from `local_lernhive`'s feature registry (ADR-01 in `../../local_lernhive/docs/00-master.md`).

## Planned feature set

- **Tour catalog.** Five level packs (Explorer → Master), each with 5–7 guided Moodle user-tours grouped into 3–6 categories. Authoring lives under `tours/levelN/<category>/*.json`; runtime visibility is feature-driven, not directory-driven.
- **Feature-addressable tours.** *(new in 0.3.0, consumer of ADR-01)* Every tour JSON carries a top-level `lernhive_feature` key. `tour_manager::get_categories($userid)` asks `local_lernhive\feature\registry::is_available_for_user()` to decide what to show. Tours disappear automatically when the admin moves the feature to a higher level or disables it.
- **Deterministic tour start from the catalog.** *(new in 0.3.0)* Every tour carries an explicit `start_url` (stored in `configdata` as `lh_start_url`) that knows the actual target URL, independent of the `pathmatch` filter. A "Start" click in the catalog routes through `starttour.php`, which (a) resolves placeholders like `{USERID}`, `{SYSCONTEXTID}`, `{SITEID}`, `{DEMOCOURSEID}` against the current user, (b) sets `tool_usertours_{id}_requested = 1` (the Moodle-native "force replay" pref), (c) clears `_completed` and `_lastStep`, and (d) redirects to the resolved URL. On the target page, Moodle's tool_usertours engine auto-plays the tour because `_requested` is set. Works for first-time starts and replays equally, without touching Moodle core tables.
- **One tour = one page (design rule).** *(new in 0.3.0)* Every LernHive onboarding tour binds exactly one `pathmatch` and plays on exactly one URL. This mirrors Moodle core's own shipped-tour convention — every `shipped_tour:true` record in `tool_usertours_tours` is single-page, including the "Activity information" feature, which Moodle itself splits into two separate tours (one for the activity page, one for the course page) rather than navigating inside a single tour. Multi-page journeys are modelled as sequences of single-page tours; the player never redirects mid-tour.
- **Tour chaining across pages.** *(new in 0.3.0)* Multi-page features (e.g. "create a course" → catalogue → edit form → new course view) are authored as N single-page tours connected by an optional `prereq` field on the tour JSON. A tour-end observer (`\tool_usertours\event\tour_ended` or `\core_user_tours\hook\after_tour_ended`) activates the next tour in the chain by setting its `_requested` pref when its prereq completes. The user sees one "Learning Unit" in the catalog; under the hood it's N independent `tool_usertours_tours` records. Clicking "Start Learning Unit" primes only the head of the chain — follow-up tours stay dormant until their predecessor completes, so the user never gets a premature tour popup on an unrelated page visit.
- **Level-based progress dashboard.** `/local/lernhive_onboarding/tours.php` — shows unlocked categories with a progress bar per category, computed from Moodle user-tour completion preferences.
- **Dashboard banner.** Renderable on `/my/` that points trainers at their unfinished Level-1 tours. Gated by `banner_gate::should_show()`.
- **Grow area.** Next-level teaser that shows up when the current level is complete and the next level is available.
- **Admin override support.** Handled entirely through `local_lernhive`'s registry + override UI. This plugin consumes the result and never owns a second level-mapping table.
- **Flavor-aware audience.** In the Schul/Academy flavors the learning path is restricted to the `lernhive_trainer` role. In the LXP flavor the `local/lernhive_onboarding:receivelearningpath` capability is additionally granted to participant-type roles via `local_lernhive_flavour` presets.

## Acceptance direction

- Feature behavior is explicit and understandable: a trainer never sees a tour for a feature they cannot use.
- Strings reuse Moodle core wherever possible.
- UX stays simple and mobile-friendly — progress bars, no nested menus.
- Flavor-specific wording only when really necessary. Trainer/in and Teilnehmer/in are the two audiences; tours should be written neutrally enough to serve both with the same strings.

## Release note

- **0.2.0 (shipped 2026-04-11)** — dashboard banner + `lernhive_trainer` role + visibility gate + PHPUnit coverage.
- **0.3.0 target** — feature-registry integration (consumer of ADR-01), deterministic tour start (`start_url` + `_requested` mechanic), one-tour-per-page design rule, tour chaining via `prereq`, Level-1 assignment tour migration to Level 2, new Level 2–5 tour packs per the v2 review of `level-tour-matrix.md`.
- **R1 envelope** — all five level packs, feature-driven visibility, LXP-flavor participant audience, all multi-page features modelled as chained single-page tours.
