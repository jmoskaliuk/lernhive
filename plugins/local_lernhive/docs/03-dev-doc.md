# local_lernhive — Developer Documentation

## Architecture note
Base plugin for levels, shared hooks and common LernHive helpers.

## Technical direction
- keep boundaries clean
- use Moodle APIs where possible
- prefer existing core strings over plugin-specific duplicates
- keep data model minimal for release 1
- document release 2 complexity separately

## Current dependencies
—

## Integration points
- Moodle core APIs
- LernHive shared services as needed
- theme integration only for styling, not for business logic
