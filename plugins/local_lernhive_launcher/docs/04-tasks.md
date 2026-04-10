# local_lernhive_launcher — Tasks

## Implementation-ready tickets

### LH-LAUNCHER-01 — Define Release 1 action inventory
- confirm the small initial action set for Release 1
- separate global actions from role-specific actions
- mark which actions route to Moodle core, `ContentHub`, or optional LXP flows
- outcome: approved Release 1 action inventory with owner plugin for each action

#### Current proposed Release 1 inventory

| Action label | Type | Visible for | Owner / destination | Release 1 decision |
|---|---|---|---|---|
| `ContentHub` | global | users with relevant content creation access | `local_lernhive_contenthub` | core launcher action |
| `Create course` | global | teachers, course creators, admins with course creation rights | Moodle core or shared LernHive course creation entry | core launcher action |
| `Create snack` | conditional | users with LXP creation rights and required plugin availability | LXP flow owned outside the launcher | optional direct shortcut |
| `Create community` | conditional | users with community creation rights and required plugin availability | LXP flow owned outside the launcher | optional direct shortcut |

#### Decisions for Release 1
- keep the baseline launcher to two always-priority actions: `ContentHub` and `Create course`
- allow `Create snack` and `Create community` only as conditional shortcuts in LXP-capable contexts
- keep `Template`, `Library`, and similar content sub-paths behind `ContentHub` instead of exposing them all in the launcher baseline
- do not add reports, navigation destinations, or broad admin menus to the initial launcher inventory

#### Rationale
- `ContentHub` already owns orchestration for copy, template, and library paths
- `Create course` is a frequent high-value action and is already present in the launcher mockup direction
- direct `Snack` and `Community` shortcuts support the documented LXP model without making LXP the default for every installation
- a shorter launcher list reduces overload and stays aligned with the action-surface principle

### LH-LAUNCHER-02 — Define visibility and permission rules
- map each launcher action to the required Moodle capability or plugin availability check
- confirm which actions are hidden when the target plugin is unavailable
- confirm how beginner-oriented level reduction should affect visible actions if `local_lernhive` level logic is present
- outcome: permission and visibility matrix for each launcher action

#### Proposed Release 1 visibility matrix

| Action label | Base visibility rule | Capability / system check | Hidden when | Notes |
|---|---|---|---|---|
| `ContentHub` | visible only to users who can start content-related creation flows | plugin availability plus at least one accessible downstream content path | `local_lernhive_contenthub` is unavailable or no downstream path is accessible | launcher should not expose a dead-end entry |
| `Create course` | visible to users with valid course creation access | if LernHive teacher course creation is enabled, use `local_lernhive\\course_manager::is_enabled()` plus course creation rights in the target category | course creation is disabled or the user lacks creation rights | primary baseline action |
| `Create snack` | visible only in LXP-capable contexts | required plugin path exists and user has the relevant creation right | LXP creation path unavailable or user lacks creation rights | optional shortcut |
| `Create community` | visible only in LXP-capable contexts | required plugin path exists and user has the relevant creation right | community creation path unavailable or user lacks creation rights | optional shortcut |

#### Level simplification rule
- hide optional shortcuts before baseline actions
- keep `ContentHub` available when it is the safest orchestration entry for beginners
- keep `Create course` visible for eligible users when course creation is a core path in the active Flavour
- remove LXP shortcuts first when the action list must stay especially simple

#### Implementation note
- evaluate visibility centrally in the launcher provider layer
- optional actions should fail closed and stay hidden until all checks pass

### LH-LAUNCHER-03 — Define launcher information architecture
- decide whether actions are shown as one short list or in a very small number of groups
- keep the model scan-friendly on mobile and desktop
- ensure the launcher stays an action surface and not a navigation clone
- outcome: documented Release 1 launcher structure and grouping rule

#### Proposed Release 1 structure
- use a single compact action list as the baseline pattern
- avoid multi-column layouts in the launcher flyout
- allow one lightweight grouping distinction only when needed:
  - baseline actions
  - optional LXP actions

#### Ordering rule
1. `Create course`
2. `ContentHub`
3. `Create snack` when visible
4. `Create community` when visible

#### IA rationale
- a single list matches the current launcher flyout mockup and theme pattern
- ordering by action priority is clearer than introducing categories too early
- optional LXP entries can sit after the two baseline actions without redefining the product around LXP

#### Release 1 interaction model
- one global trigger
- one compact flyout
- short label plus optional one-line description per action
- click or tap goes directly to the destination flow
- no nested menus inside the launcher

### LH-LAUNCHER-04 — Define string and label plan
- identify which labels can reuse Moodle core strings
- identify which labels must stay LernHive-specific
- define one canonical label per action
- outcome: launcher string table with source component and reuse decision

#### Proposed Release 1 string table

| UI text | Source decision | Preferred component | Note |
|---|---|---|---|
| `Launcher` | LernHive-specific string | `local_lernhive_launcher` | canonical product term |
| `Create course` | reuse existing wording where possible | Moodle core or stable existing LernHive wording | avoid synonyms such as `New course` |
| `ContentHub` | LernHive-specific string reuse | `local_lernhive_contenthub` | canonical product term |
| `Create snack` | mixed label: core verb plus LernHive object | verb from core where practical, `Snack` from owning LernHive term | keep exact term `Snack` |
| `Create community` | mixed label: core verb plus LernHive object | verb from core where practical, `Community` from owning LernHive term | keep exact term `Community` |
| action descriptions | launcher-owned strings | `local_lernhive_launcher` | helper copy belongs with the launcher |

#### Label rules
- use one canonical visible label per action
- prefer `Create course`
- keep `ContentHub` unchanged
- keep `Snack` and `Community` unchanged
- avoid admin-heavy wording in the launcher baseline

#### Follow-up cleanup note
- theme prototype strings for launcher actions should later be aligned with launcher-owned production strings
- the functional launcher must not depend on theme-only wording definitions

### LH-LAUNCHER-05 — Define target destinations and handoff rules
- document the exact destination for each launcher action
- clarify whether the action opens a Moodle page, LernHive plugin screen, or wizard entry point
- avoid duplicate entry screens where another plugin already owns the flow
- outcome: target routing map for all Release 1 actions

#### Proposed Release 1 routing map

| Action label | Target type | Proposed target | Ownership rule |
|---|---|---|---|
| `Create course` | Moodle core or shared LernHive-assisted URL | `/course/edit.php` with the category URL resolved through `local_lernhive\\course_manager::get_create_course_url()` when enabled | launcher routes only; course creation stays outside launcher |
| `ContentHub` | LernHive plugin screen | `local_lernhive_contenthub` entry URL to be finalized in plugin implementation | `ContentHub` owns copy, template, and library path choice |
| `Create snack` | LXP plugin-owned creation flow | final plugin URL to be defined by the owning snack flow | launcher must not add a parallel snack wizard |
| `Create community` | LXP plugin-owned creation flow | final plugin URL to be defined by the owning community flow | launcher must not add a parallel community wizard |

#### Handoff rules
- if a destination flow already has its own decision screen, deep-link into that flow instead of adding a launcher-owned intermediate step
- if a downstream plugin is unavailable, hide the action instead of routing to a placeholder page
- route ownership stays with Moodle core or the destination plugin, not with `local_lernhive_launcher`

#### Current dependency note
- `Create course` is the most concrete route today because `local_lernhive` already provides a URL helper
- `ContentHub`, `Snack`, and `Community` still need final implementation URLs from their owning plugins

### LH-LAUNCHER-06 — Prepare UI implementation ticket
- define trigger placement expectations for desktop and mobile
- define open, close, and focus behaviour at a functional level
- record accessibility basics for keyboard and screen reader use
- outcome: implementation ticket for launcher UI shell

#### Proposed UI shell ticket
- use the compact flyout pattern as the Release 1 default
- place the launcher trigger in the left-oriented global shell, visually aligned with navigation but clearly separate from navigation items
- keep the dock-style pattern as an optional later enhancement, not the baseline requirement

#### Functional behaviour
- trigger opens and closes the flyout predictably
- flyout opens close to the trigger
- keyboard opening lands focus on the first actionable item in a logical way
- `Escape` closes the flyout
- outside-click or blur-close behaviour stays simple and unsurprising
- action list remains tappable on smaller screens without hover

#### Accessibility basics
- trigger has a clear accessible label using `Launcher`
- action items have visible labels and are not icon-only
- trigger and actions have visible focus states
- structure stays understandable for screen readers even if lightweight native components such as `details/summary` are used

### LH-LAUNCHER-07 — Prepare backend implementation ticket
- define the minimal internal action registry structure
- define how capability checks and optional plugin checks are evaluated
- keep the architecture compatible with later extension without overengineering
- outcome: implementation ticket for launcher action provider layer

#### Proposed backend shape
- create a single action provider that returns a normalized list of visible actions
- each action definition should include:
  - stable action id
  - visible label
  - optional description
  - icon token
  - target URL resolver
  - visibility callback
  - sort order

#### Evaluation order
1. check owner plugin or route availability
2. check capability or config prerequisites
3. apply optional level simplification
4. return only visible actions to the renderer

#### Release 1 engineering rule
- keep the provider small and explicit
- do not introduce database-backed launcher customization in Release 1
- do not compute recommendation scores or usage ranking

### LH-LAUNCHER-08 — Prepare QA ticket
- define role-based test scenarios
- define responsive checks
- define string and terminology review checks
- outcome: Release 1 QA checklist for launcher rollout

#### Proposed QA checklist
- manager sees baseline actions when routes are available
- eligible teacher sees `Create course` when course creation is enabled
- users without creation rights do not see dead-end launcher actions
- `Create snack` and `Create community` appear only in intended LXP-capable contexts
- launcher order remains stable across desktop and mobile
- launcher can be operated with keyboard only
- visible labels use `Launcher`, `ContentHub`, `Snack`, and `Community` consistently
- launcher still behaves correctly if optional downstream plugins are disabled

## Open questions
- should `Snack` and `Community` creation appear directly in the launcher by default in LXP contexts, or stay hidden behind `ContentHub` until usage proves they are frequent enough
- how strongly should level-based simplification reduce visible actions for beginner users in Release 1
- which create/configure destinations already have stable Moodle or LernHive target URLs versus placeholder future flows
- whether a short descriptive subtitle under each action is needed for Release 1 or can wait

## Next step
Use the tickets above to create implementation issues in this order:
1. `LH-LAUNCHER-01`
2. `LH-LAUNCHER-02`
3. `LH-LAUNCHER-03`
4. `LH-LAUNCHER-04`
5. `LH-LAUNCHER-05`
6. `LH-LAUNCHER-06`
7. `LH-LAUNCHER-07`
8. `LH-LAUNCHER-08`
