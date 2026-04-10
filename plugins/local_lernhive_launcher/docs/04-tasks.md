# local_lernhive_launcher — Tasks

## Implementation-ready tickets

### LH-LAUNCHER-01 — Define Release 1 action inventory
- confirm the small initial action set for Release 1
- separate global actions from role-specific actions
- mark which actions route to Moodle core, `ContentHub`, or optional LXP flows
- outcome: approved Release 1 action inventory with owner plugin for each action

### LH-LAUNCHER-02 — Define visibility and permission rules
- map each launcher action to the required Moodle capability or plugin availability check
- confirm which actions are hidden when the target plugin is unavailable
- confirm how beginner-oriented level reduction should affect visible actions if `local_lernhive` level logic is present
- outcome: permission and visibility matrix for each launcher action

### LH-LAUNCHER-03 — Define launcher information architecture
- decide whether actions are shown as one short list or in a very small number of groups
- keep the model scan-friendly on mobile and desktop
- ensure the launcher stays an action surface and not a navigation clone
- outcome: documented Release 1 launcher structure and grouping rule

### LH-LAUNCHER-04 — Define string and label plan
- identify which labels can reuse Moodle core strings
- identify which labels must stay LernHive-specific
- define one canonical label per action
- outcome: launcher string table with source component and reuse decision

### LH-LAUNCHER-05 — Define target destinations and handoff rules
- document the exact destination for each launcher action
- clarify whether the action opens a Moodle page, LernHive plugin screen, or wizard entry point
- avoid duplicate entry screens where another plugin already owns the flow
- outcome: target routing map for all Release 1 actions

### LH-LAUNCHER-06 — Prepare UI implementation ticket
- define trigger placement expectations for desktop and mobile
- define open, close, and focus behaviour at a functional level
- record accessibility basics for keyboard and screen reader use
- outcome: implementation ticket for launcher UI shell

### LH-LAUNCHER-07 — Prepare backend implementation ticket
- define the minimal internal action registry structure
- define how capability checks and optional plugin checks are evaluated
- keep the architecture compatible with later extension without overengineering
- outcome: implementation ticket for launcher action provider layer

### LH-LAUNCHER-08 — Prepare QA ticket
- define role-based test scenarios
- define responsive checks
- define string and terminology review checks
- outcome: Release 1 QA checklist for launcher rollout

## Open questions
- should `Snack` and `Community` creation appear directly in the launcher in Release 1, or only via `ContentHub` when both paths exist
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
