# local_lernhive_audience — Developer Documentation

## Architecture note
Audience abstraction and simple rule engine built on top of Moodle structures.

## Technical direction
- keep boundaries clean
- use Moodle APIs where possible
- prefer existing core strings over plugin-specific duplicates
- keep data model minimal for release 1
- document release 2 complexity separately

## Release 1 technical scope
- build on Moodle structures rather than replacing them
- reuse Moodle groups, cohorts, enrolment, profile-field, and activity data where relevant
- support two Audience types:
  - static
  - dynamic
- support these initial dynamic rule types only:
  - time-based
  - activity-based
  - profile-based
- support AND / OR rule logic
- keep rule evaluation understandable and implementation-ready
- keep the Release 1 admin UX simple and list-based

## Release 2 separation
- Circle UX refinement belongs to Release 2
- richer visual rule composition belongs to Release 2
- do not move advanced visualization or speculative segmentation concepts into Release 1

## Current dependencies
- `local_lernhive`
- Moodle groups and cohorts
- Moodle enrolment and activity data
- Moodle profile-field data

## Integration points
- Moodle core APIs
- groups and cohorts for existing membership structures
- enrolment and activity data for dynamic rule evaluation
- profile fields for profile-based rules
- LernHive shared services as needed
- theme integration only for styling, not for business logic

## Terminology rules
- keep `Audience` as the visible system term
- keep terminology English-first and stable
- avoid introducing Circle as the main Release 1 term or interaction model
