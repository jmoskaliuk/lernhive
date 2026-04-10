# local_lernhive_launcher — Developer Documentation

## Architecture note
Global action launcher that orchestrates access to existing Moodle and LernHive flows.

## Technical direction
- keep boundaries clean
- use Moodle APIs where possible
- prefer existing core strings over plugin-specific duplicates
- keep data model minimal for release 1
- document release 2 complexity separately
- treat the launcher primarily as orchestration and permission-aware routing

## Current dependencies
local_lernhive

## Integration points
- Moodle core APIs
- LernHive shared services as needed
- theme integration only for styling, not for business logic

## Implementation direction

### Functional responsibility
- determine which actions should be visible for the current user
- provide launcher metadata such as action id, label, description, icon, and target
- route to the target URL or flow
- avoid owning the business workflow behind each destination

### Non-responsibilities
- no implementation of content creation workflows inside this plugin
- no duplication of `ContentHub`, `Snack`, or `Community` logic
- no replacement of Moodle navigation trees

### Suggested internal structure
- capability-aware action registry or provider layer
- renderer and template layer for the visible launcher UI
- minimal integration hook for a global launcher trigger
- local language strings only for launcher-specific wording

## Expected API touchpoints
- Moodle capability checks
- Moodle URL routing and page context handling
- Moodle output rendering APIs
- plugin availability checks for optional LernHive integrations

## String plan
- reuse Moodle core strings for generic verbs and objects where possible
- keep `Launcher` as a launcher-owned string
- reuse canonical LernHive terms from the owning plugin when the action refers to `ContentHub`, `Snack`, or `Community`
- avoid introducing synonym strings for the same action

## Release 1 technical constraints
- no persistent personalization data required
- no action analytics dependency required for initial rollout
- no hard dependency on the LXP Flavour for the base launcher
