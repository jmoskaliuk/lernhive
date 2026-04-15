# local_lernhive_launcher — Features

## Feature summary
Global action launcher for create, manage, and configure actions that complements navigation without replacing it.

## Release 1 feature goals
- provide one global action surface for high-value actions
- keep the action list short, understandable, and role-aware
- route users into the correct LernHive or Moodle flow instead of duplicating those flows inside the launcher
- work across desktop and mobile without turning the launcher into a full navigation system

## Release 1 feature set

### 1. Global launcher entry point
- launcher can be opened from a consistent global UI position
- launcher label stays `Launcher`
- open and close behaviour is predictable on desktop and mobile

### 2. Role-aware action groups
- show only actions that are relevant for the current user role and capability set
- keep beginner-facing action lists shorter than admin-facing lists
- do not expose unavailable actions as dead ends

### 3. High-value action shortcuts
- provide direct entry points for the most common create and configure tasks
- prioritize fast access over long action catalogs
- use existing Moodle destinations where a dedicated LernHive flow does not exist

### 4. ContentHub entry
- include a clear entry to `ContentHub`
- treat `ContentHub` as the preferred entry point for content-related creation flows where appropriate

### 5. Library entry
- include a direct launcher action to `Library` when `local_lernhive_library` is installed
- show the action only when the user can access `local/lernhive_library:import`
- keep `Library` action-oriented (open catalog + import), not as a full navigation replacement

### 6. LXP-related creation entry points
- support entry points for `Snack` and `Community` creation where the relevant plugins and capabilities are available
- keep these entries clearly optional and capability-dependent
- do not make LXP actions appear as the default primary model in non-LXP usage

### 7. Reporting entry
- include a direct entry to `Reports` when `local_lernhive_reporting` is installed
- show `Reports` only for users who can access the reporting dashboard
- keep reporting access action-oriented and avoid turning the launcher into a full admin menu

### 8. Guided action language
- action labels must be short and outcome-oriented
- Moodle core strings should be reused where they fit semantically
- LernHive-specific labels should remain limited to established product terms such as `Launcher`, `ContentHub`, `Library`, `Reports`, `Snack`, and `Community`

## Baseline Release 1 action inventory

### Core actions
- `ContentHub`
- `Library` (capability-dependent)
- `Create course`
- `Reports` (capability-dependent)

### Conditional Release 1 shortcuts
- `Create snack`
- `Create community`

### Release 1 routing rule
- `ContentHub` is the default orchestration entry for content-related creation paths
- direct launcher shortcuts should be limited to a very small number of repeated high-value actions
- `Template` and similar sub-paths should stay behind `ContentHub`; `Library` may be exposed as a direct action when import rights exist
- broader configuration destinations may be added later only if they remain action-oriented and do not turn the launcher into a second navigation menu

## Release 1 guardrails
- launcher is for actions, not for browsing content areas
- launcher must not duplicate business logic from `local_lernhive_contenthub` or other plugins
- launcher must not depend on `theme_lernhive` for its functional logic
- no advanced personalization or adaptive ranking in Release 1
- no flavour-specific renaming of the launcher concept

## Out of scope for Release 1
- personalized action recommendations
- usage-based ranking of actions
- user-customizable launcher layouts
- recent actions, pinned actions, or favorites
- workflow builders or multi-step automation inside the launcher

## Release note
Target release: R1
