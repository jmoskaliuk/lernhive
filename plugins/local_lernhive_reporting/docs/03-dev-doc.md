# local_lernhive_reporting — Developer Documentation

## Architecture note
Simple tile-based reports on top of Moodle reports and analytics.

## Technical direction
- keep boundaries clean
- use Moodle APIs where possible
- prefer existing core strings over plugin-specific duplicates
- keep data model minimal for release 1
- document release 2 complexity separately

## Current dependencies
local_lernhive

## Integration points
- Moodle core APIs
- LernHive shared services as needed
- theme integration only for styling, not for business logic
