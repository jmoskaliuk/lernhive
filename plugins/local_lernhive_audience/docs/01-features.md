# local_lernhive_audience — Features

## Purpose
Provide a simple Audience layer on top of Moodle structures.

## Product rule
- Audience builds on Moodle structures rather than replacing them
- terminology stays English-first and stable
- use `Audience` as the product term across UX, docs, and implementation planning

## Audience types
- Static audience
- Dynamic audience

## Dynamic rule types
- Time-based
- Activity-based
- Profile-based

## Rule logic
- AND
- OR

## Release 1 rule engine behaviour
- a dynamic Audience is defined by one or more rules
- each rule uses one of the initial rule types:
  - time-based
  - activity-based
  - profile-based
- rules can be combined with AND / OR logic
- rule outcomes should be explainable to admins
- rule scope should stay close to Moodle data that already exists
- Release 1 keeps the rule model simple rather than visually advanced

## Example rules
- if profile city = Stuttgart
- if course has not been visited in 3 days
- if user enrolled in course X
- combine profile + activity conditions

## Release 1 UX
- list-based audience management
- member view
- rule view
- simple create/edit flow
- no Circle-first interaction model
- no advanced visual rule builder

## Release 2
- refined Circle UX
- richer visual rule composition if still explainable

## Acceptance criteria
- Audience uses Moodle groups, cohorts, profile fields, enrolment, and activity data where relevant
- Release 1 supports time-based, activity-based, and profile-based rules
- Release 1 supports AND / OR rule logic
- Audience terminology remains English-first and stable
- Circle UX is clearly out of Release 1 scope
