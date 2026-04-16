# local_lernhive_flavour — Developer Documentation

## Architecture overview

The plugin is split into three concentric layers:

```
                 ┌──────────────────────────────┐
                 │     admin_flavour.php        │
                 │   (thin controller only)     │
                 └──────────────┬───────────────┘
                                │
                 ┌──────────────▼───────────────┐
                 │  output/renderer.php         │
                 │  output/flavour_picker.php   │   ← renderables
                 │  output/flavour_diff.php     │
                 │  templates/*.mustache        │   ← HTML
                 │  styles.css                  │   ← CSS
                 └──────────────┬───────────────┘
                                │
                 ┌──────────────▼───────────────┐
                 │  flavour_manager             │   ← orchestration
                 │  flavour_registry            │   ← profile lookup
                 │  flavour_definition          │   ← base class
                 │  profile/school_profile      │
                 │  profile/lxp_profile         │
                 │  profile/highered_profile    │
                 │  profile/corporate_profile   │
                 │  flavour_audit               │   ← DB persistence
                 │  event/flavour_applied       │   ← core event
                 └──────────────────────────────┘
```

There is no direct SQL anywhere except inside `flavour_audit` and the
privacy provider. The manager calls `set_config()` / `get_config()` for
plugin settings so that Moodle's config cache stays consistent, and then
hands feature-level override payloads to
`local_lernhive\feature\registry::apply_flavor_preset(...)`.

## Data model

### Table `local_lernhive_flavour_apps`

Immutable audit trail — rows are inserted by
`flavour_manager::apply()` and never updated. Deleting rows is only
allowed via the privacy provider.

| Column               | Type       | Purpose |
|----------------------|------------|---------|
| `id`                 | int        | PK |
| `flavour`            | char(32)   | Key applied (`school`, `lxp`, …) |
| `previous_flavour`   | char(32)?  | Key active before this apply |
| `applied_by`         | int        | User ID of the admin |
| `timeapplied`        | int        | Unix ts |
| `settings_before`    | text(JSON) | `{component: {key: value|null}}` |
| `settings_after`     | text(JSON) | Same shape, after the writes |
| `overrides_detected` | bool       | Any previously-set value overwritten? |

Indexes: `(flavour, timeapplied)` and `(timeapplied)` for the audit view
that `local_lernhive_configuration` will build in R2.

## Adding a new flavour

1. Create `classes/profile/<key>_profile.php` extending `flavour_definition`
2. Implement `get_key`, `get_label`, `get_description`, `get_icon`, `get_defaults`
3. Register in `flavour_registry::all()`
4. Add language strings `flavour_<key>` and `flavour_<key>_desc` in both
   `lang/en/` and `lang/de/`
5. If the profile is not ready for production, override `get_maturity()`
   to return `MATURITY_EXPERIMENTAL` — the picker will show the dashed
   border and the experimental badge automatically
6. Add a unit test assertion in `flavour_registry_test` for the new key

## Adding a managed config key

Every key a flavour profile writes must already exist as a real setting
in the target plugin's `settings.php`. The pre-refactor stub violated
this (keys like `allow_course_creation` were written but never read) —
keep new profiles honest by running the
`flavour_registry_test::test_school_defaults_use_correct_local_lernhive_keys`
pattern for any new plugin integration.

## Dependencies

- `local_lernhive` (levels, teacher settings) — hard dependency via `version.php`
- Moodle 4.5+ (core `\core\context\*` classes, privacy v2 interfaces)
- No dependency on `theme_lernhive` — the plugin ships its own scoped CSS
  so it works under any Moodle theme

## Integration points

- **Moodle core config API** — `set_config()` / `get_config()`
- **local_lernhive registry API** — `registry::apply_flavor_preset(...)`
- **Moodle events API** — `flavour_applied` is a `\core\event\base` subclass
- **Moodle privacy API** — full provider, not `null_provider`
- **R2 consumer**: `local_lernhive_configuration` will listen to
  `flavour_applied` events and query `local_lernhive_flavour_apps`
  directly to build a configuration history view

## Testing

```bash
vendor/bin/phpunit local/lernhive_flavour/tests/
```

Three test files cover:

- `flavour_registry_test` — registry invariants, profile counts, key correctness
- `flavour_manager_test` — apply semantics, diff, event, override detection
- `flavour_audit_test` — DB writer, JSON encoding, ordering

## Known limitations

- Runtime data migration between flavours is not in scope for R1
- Flavour terminology overrides (renaming UI concepts per flavour) are
  deferred to R2 per AGENTS.md
- The picker is Moodle-standard Bootstrap — once `theme_lernhive` is
  further along, the cards should inherit its design tokens
