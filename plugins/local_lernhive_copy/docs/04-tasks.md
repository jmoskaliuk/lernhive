# local_lernhive_copy - Tasks

## Current status

### R1 scaffold: shipped (2026-04-11)

- Entry page for both source modes (course/template) delivered and linked from ContentHub.

### R2.0: shipped (0.2.0, 2026-04-11)

- Simple copy flow via `copy_helper` shipped.

### R2.1: shipped (0.2.1, 2026-04-11)

- Standard-layout-only rendering shipped (no admin layout branch).

### R2.2: shipped (0.3.0, 2026-04-15)

- **LH-COPY-UX-01** done:
  explicit clean-copy callout for userdata default.
- **LH-COPY-NAV-01** done:
  cancel and return path consistently route to ContentHub.
- **LH-COPY-TEST-01** done:
  Behat happy-path scenario added (`tests/behat/copy_flow.feature`).
- **LH-COPY-TEST-02** done:
  `wizard_page` PHPUnit coverage added.
- **LH-COPY-PREF-01** done:
  per-user default target category stored in `user_preferences`,
  with privacy metadata provider update.
- **LH-COPY-EXPERT-01** done:
  expert flow redirects into Moodle core copy UI.
- **LH-COPY-TPL-01** done:
  template source wired to Library catalog backend.
- **LH-COPY-TPL-02** done:
  two-step template UX implemented
  (pick template -> run Simple or Expert mode).

## Next steps

1. Add automated coverage for template edge cases:
   missing source course, missing catalog backend, stale template id.
2. Add stronger permission matrix tests for mixed role setups
   (coursecreator vs manager vs editingteacher).
3. Confirm whether template cards should expose additional metadata
   (owner, flavour tags, intended audience) in R1 scope.
