# local_lernhive_flavour — Features

## Feature summary
Provides a flavour picker that applies a sensible set of starting defaults
for a LernHive installation and records every application in an audit
trail so later configuration drift can be understood.

## Release 1 feature set
- Flavour registry with four profiles
  - `school` (default, stable)
  - `lxp` (stable)
  - `highered` (experimental stub, inherits School defaults)
  - `corporate` (experimental stub, inherits School defaults)
- Flavour picker admin page with one card per profile
- Apply action that writes managed config keys across LernHive plugins
- Diff confirm dialog when applying would overwrite existing settings
- Audit trail table `local_lernhive_flavour_apps` with full before/after snapshots
- `flavour_applied` event for downstream plugins (consumed by
  `local_lernhive_configuration` in R2)
- Privacy provider that exposes and deletes audit rows per user

## Non-goals for Release 1
- No runtime data migration when switching flavour (only config is touched)
- No per-flavour terminology overrides
- No rollback UI for individual setting overrides (R2, in
  `local_lernhive_configuration`)
- No concrete Higher Ed or Corporate Academy defaults — the stubs inherit
  from School until product decisions land
- No Behat coverage — PHPUnit is enough for the current UI surface

## Acceptance direction
- applying a flavour from a clean site must not show the confirm dialog
- applying a flavour that overwrites existing customised settings must
  show the diff and require explicit confirmation
- picking the currently active flavour must not be possible
- every apply must result in exactly one audit row
- experimental flavours must be visually distinct (dashed border + badge)
## Release note
Target release: R1
