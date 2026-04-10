# local_lernhive_discovery — Developer Documentation

## Architecture note
Explore feed and content projection layer for the optional LXP Flavour.

## Technical direction
- keep boundaries clean
- use Moodle APIs where possible
- prefer existing core strings over plugin-specific duplicates
- keep data model minimal for release 1
- document release 2 complexity separately

## Release 1 technical scope
- activate Explore only for the LXP Flavour
- replace Dashboard only in the LXP Flavour
- project only these experience types:
  - Course
  - Snack
  - Community
- keep feed blocks fixed and slim
- support explainable ranking based on:
  - community and Audience relevance first
  - snack preference
  - recent relevance within the last 7 days
  - no likes-based signals
- read Follow and Bookmark state separately from `local_lernhive_follow`

## Release 2 separation
- stronger personalization belongs to Release 2
- richer recommendation-style ranking belongs to Release 2
- do not introduce speculative AI or opaque ranking into Release 1

## Current dependencies
- `local_lernhive_follow`
- `local_lernhive_audience`
- `local_lernhive_notifications` where digest-relevant Explore events need aligned semantics

## Integration points
- Moodle core APIs
- Audience membership and rule outputs from `local_lernhive_audience`
- Follow and Bookmark relationship state from `local_lernhive_follow`
- LernHive shared services as needed
- theme integration only for styling, not for business logic

## Interaction rules
- Follow is shown on cards via a star icon
- Bookmark remains a separate explicit action
- Follow and Bookmark must not collapse into one stored relationship
- ranking explanation should be available without exposing internal implementation noise
