# LernHive Strategy

## Product vision

LernHive is a SaaS-first Moodle product layer that creates a better learning and teaching experience through simpler UX, guided onboarding, clear action entry points, and an optional LXP Flavour.

## Strategic goals

- reduce Moodle complexity for first-time and occasional users
- accelerate adoption for teachers, trainers and organizations
- provide out-of-the-box starting points through Flavours
- keep compatibility with current Moodle releases by avoiding forks
- make product logic visible and marketable for customers and partners

## Business model direction

- SaaS-first
- partner-enabled sales model
- flavour-based consulting and configuration
- managed service with documented configuration state

## Product layers

1. Moodle core
2. LernHive functional plugins
3. LernHive theme and UX components
4. Flavour and configuration layer
5. SaaS delivery and partner layer

## Product positioning

LernHive is not a Totara clone.
LernHive is Moodle made simpler, clearer and more experience-driven, with an optional LXP Flavour inspired by stronger Explore and engagement patterns.

## Flavours in scope

Current priority Flavours:
- School
- LXP

Later Flavours:
- Higher Education
- Corporate Academy
- Association / Membership

## Product model

- Moodle core remains the technical foundation
- LernHive plugins add orchestration, guidance, and product-specific UX behaviour
- `theme_lernhive` provides the visual and interaction layer
- Flavours provide recommended starting configurations, not hardcoded product modes
- Explore exists only in the optional LXP Flavour

## Release principles

- Release 1 must stay simple and guided
- Moodle core concepts, roles, and strings are reused where sensible
- Audience builds on Moodle structures rather than replacing them
- notifications reuse Moodle core plus a LernHive digest layer

## Non-goals

- no Moodle fork
- no full mobile app in release 1
- no heavy AI dependency in release 1
- no fully custom notification engine replacing Moodle core
