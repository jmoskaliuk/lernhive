# local_lernhive — Tasks

## ADR-01 — Feature Registry (target 0.3.0)

Tracking milestones for the decision in `00-master.md` → ADR-01. Order is dependency-driven, not calendar-driven.

- [x] **LH-CORE-FR-01** — Scaffold `local_lernhive\feature\definition` value object + `registry` class with a hardcoded initial feature list *(see `03-dev-doc.md` → "Canonical feature IDs")*. No DB yet. Unit tests against the pure `effective_level()` path. *(Landed 2026-04-11: `classes/feature/definition.php`, `classes/feature/registry.php`, `tests/feature/definition_test.php`, `tests/feature/registry_test.php`. 38 features registered after FR-05b extension for onboarding course-settings + messaging tours. `registry::effective_level()` returns pure defaults — override layer arrives in LH-CORE-FR-03.)*
- [x] **LH-CORE-FR-02** — XMLDB: `local_lernhive_feature_overrides` table. `db/install.xml` + `db/upgrade.php` step with savepoint. Index on `feature_id` unique. *(Landed 2026-04-16: schema + upgrade step in `db/install.xml` and `db/upgrade.php`, plugin version bump to `2026041601`.)*
- [x] **LH-CORE-FR-03** — `override_store` DB adapter. Handles the precedence rule (admin > flavor_preset) and idempotent flavor-preset writes. *(Landed 2026-04-16: `classes/feature/override_store.php`; `registry::effective_level()` now resolves DB overrides with disabled-state support.)*
- [x] **LH-CORE-FR-04** — Rewrite `capability_mapper::apply_level()` to consume the registry. Remove `get_level_modules()` + `get_level_capabilities()` as public API (keep thin shims that delegate to the registry for one cycle, then drop in 0.4.0). *(Landed 2026-04-16: registry-driven capability aggregation in `classes/capability_mapper.php`; legacy APIs kept as shims.)*
- [ ] **LH-CORE-FR-05** — Admin settings page under `Site administration → LernHive → Level configuration`. Table view, inline dropdown per feature (Level 1..5 / disabled / default). Uses `admin_setting_*` primitives and writes through `override_store`.
- [ ] **LH-CORE-FR-06** — `local_lernhive\event\feature_override_changed` + listener-free fire path. Onboarding plugin consumes it in a follow-up ticket.
- [ ] **LH-CORE-FR-07** — Flavor-preset hook API. Public static `registry::apply_flavor_preset(string $flavor_id, array $overrides): void`. Idempotent, admin-override-safe.
- [ ] **LH-CORE-FR-08** — PHPUnit: `registry_test.php`, `override_store_test.php`, `capability_mapper_test.php` (retargeted). Behat: `admin_override.feature` exercising the settings UI end-to-end.
- [ ] **LH-CORE-FR-09** — Docs sweep: update `01-features.md` default map once the code list is final, replace the inline table in `03-dev-doc.md` with a single-line pointer to `registry.php`.

## Open questions

- **Hierarchical vs. flat feature IDs.** Leaning flat — revisit if the admin UI gets unwieldy past ~40 entries.
- **Override UI location.** Confirmed: lives in `local_lernhive`, not in `local_lernhive_onboarding`.
- **Flavor-preset application mode.** Diff-wise (protect admin overrides) is the current plan — needs explicit test coverage in `LH-CORE-FR-08`.
- **Deprecation window for `capability_mapper::get_level_modules()`.** Shim for one release (0.3.0 → 0.4.0), then drop. Do any external plugins call this method? `grep` says no as of 2026-04-11 — confirm before removing.

## Next step

1. ~~Accept ADR-01 in review round 2 (Johannes).~~ **Done 2026-04-11.**
2. ~~Start with **LH-CORE-FR-01** — pure-code scaffold, no DB, no UI.~~ **Done 2026-04-11** — classes + unit tests merged, `registry::effective_level()` pinned to matrix v2.
3. Next up: **LH-CORE-FR-05** — Admin settings page on top of `override_store` so admins can edit feature levels without touching code.
4. In parallel (consumer side): `local_lernhive_onboarding` can proceed with `LH-ONB-FR-03` against registry overrides instead of the old hardcoded map.
