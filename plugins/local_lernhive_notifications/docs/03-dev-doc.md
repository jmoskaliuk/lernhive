# local_lernhive_notifications — Developer Documentation

## Architecture note
Digest and LXP notification extension over Moodle notifications.

## Technical direction
- keep boundaries clean
- use Moodle APIs where possible
- prefer existing core strings over plugin-specific duplicates
- keep data model minimal for release 1
- document release 2 complexity separately

## Current dependencies
local_lernhive_follow, local_lernhive_discovery

## Integration points
- Moodle core APIs
- LernHive shared services as needed
- theme integration only for styling, not for business logic
