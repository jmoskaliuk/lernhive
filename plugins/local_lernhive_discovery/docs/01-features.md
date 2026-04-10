# local_lernhive_discovery — Features

## Purpose
Provide the Explore experience for the LXP Flavour.

## Core feature set
- Explore exists only in the LXP Flavour
- Explore replaces Dashboard in the LXP Flavour
- feed stays slim and stable
- only these content types in release 1:
  - Course
  - Snack
  - Community
- ranking is explainable
- no AI in release 1

## Feed blocks
1. New in your community
2. New in content you follow
3. Popular snacks
4. Explore

## Ranking principles
- audience/community relevance is strongest
- followed content is relevant but separate from Bookmark state
- snacks are preferred over courses
- time window: last 7 days
- likes are ignored
- hover/explanation text is supported

## Follow and Bookmark rules
- Follow and Bookmark are separate concepts
- Follow is shown on cards with a star icon
- Bookmark is explicit only
- Bookmark never happens automatically
- card actions must not blur Follow and Bookmark into one state

## Release boundaries

### Release 1
- fixed feed blocks only
- no speculative AI ranking
- no hidden recommendation engine
- no likes-based ranking
- no expanded content model beyond Course, Snack, and Community

### Release 2
- stronger personalization can be explored later
- richer ranking inputs may be evaluated later
- any Release 2 expansion must keep ranking explainable

## Acceptance criteria
- Explore exists only in the LXP Flavour
- Explore replaces Dashboard only in the LXP Flavour
- feed is understandable without explanation
- ranking reasons can be surfaced in the UI
- content cards show separate Follow and Bookmark actions
- Follow is visible as a star on cards
- Bookmark is explicit only
- no more than four main feed blocks
