# LernHive Target Architecture

## Architecture summary

LernHive is built as a modular product on top of Moodle core.

### Layer 1 — Moodle Core
- users, roles, capabilities
- courses, activities, sections
- groups, cohorts, enrolments
- backup/restore
- competencies, learning plans
- reports, logs, analytics
- messaging / notifications

### Layer 2 — LernHive Functional Plugins
- feature logic
- orchestration
- product-specific rules
- no dependency on theme for business logic

### Layer 2a — LernHive Course Formats
- `format_lernhive_snack` — short-form 10–30 min learning (planned 0.10.0)
- `format_lernhive_community` — community feed rendering (planned 0.11.0)
- `format_lernhive_classic` — optional, only if `format_topics` proves insufficient
- Course format plugins own course-content rendering. The theme stays format-agnostic.

### Layer 3 — LernHive UX Layer
- `theme_lernhive`
- UI patterns
- visual consistency
- touch-friendly, responsive navigation
- English-first terminology and string usage
- **does not render course content** — that is a course format responsibility (ADR-01)

### Layer 4 — Experience Layer
- Flavours
- Level system
- Launcher
- Context Helper
- ContentHub
- Explore (LXP only)

### Layer 5 — SaaS / Delivery Layer
- flavour-based setup
- partner delivery
- configuration history
- content delivery for Library

## Core design rules

- no fork of Moodle
- Theme handles UX/UI, not business logic
- Plugins must work without the LernHive theme
- Moodle core strings and concepts are reused wherever sensible
- flavour terminology changes are restrictive
- Explore exists only in the optional LXP Flavour
- notifications reuse Moodle core plus a LernHive digest layer
- Audience builds on Moodle groups, cohorts, profile data, and activity data
- Release 1 stays simple, guided, and explainable

## Main plugin boundaries

### `local_lernhive`
Base system for levels, shared UI logic hooks, and common helper services.

### `theme_lernhive`
Visual layer and responsive design system.

### `local_lernhive_flavour`
Flavour selection, setup defaults and profile loading.

### `local_lernhive_configuration`
Configuration history and override tracking on top of a base Flavour.

### `local_lernhive_launcher`
Global action launcher.

### `local_lernhive_contexthelper`
Context-aware action suggestions.

### `local_lernhive_onboarding`
Tours, progression and guidance.

### `local_lernhive_contenthub`
Unified entry UI for copy, template, library, and later optional AI paths.

### `local_lernhive_copy`
Course copy wizard based on Moodle backup/restore.

### `local_lernhive_library`
Managed external content import and version visibility.

### `local_lernhive_discovery`
LXP Explore start page, feed, and content projections.

### `local_lernhive_follow`
Follow and Bookmark logic, with Follow shown as a star in the UX.

### `local_lernhive_audience`
Audience abstraction over Moodle groups, cohorts, profile data, and activity rules.

### `local_lernhive_notifications`
LernHive digest extension on top of Moodle notifications and messaging.

### `local_lernhive_reporting`
Simple dashboard-like reporting UX on top of Moodle reports and analytics.

## LXP content model

Everything remains technically based on Moodle objects, mainly courses, but gets a different experience type:

- Course
- Community
- Snack
- optional Event-like usage pattern

The user should see the experience type, not the technical Moodle object.

### Technical mapping: experience type → course format plugin

| Experience type | Course format           | Status       | Target release |
|-----------------|-------------------------|--------------|----------------|
| Classic Course  | `format_topics` (core)  | shipped      | —              |
| Snack           | `format_lernhive_snack` | planned      | 0.10.0         |
| Community       | `format_lernhive_community` | planned  | 0.11.0         |

**Why course formats, not theme partials?** Moodle's canonical separation places course-content rendering in course format plugins, not in themes. During 0.8.x–0.9.x the Snack surface was prototyped as `theme_lernhive/templates/snack_*.mustache`. That prototype proved the UX direction but violates Moodle's separation of concerns — it blocks per-course customization, prevents LernHive from running on other themes, and couples presentation to visual design decisions that should be independent. Post-0.9.3 these templates and their SCSS rules migrate to dedicated course format plugins (see ADR-01 in `plugins/theme_lernhive/docs/00-master.md`).

## Architecture decisions (ADRs)

ADRs for individual plugins live inside that plugin's `docs/00-master.md`. Product-level decisions live here.

### ADR-P01 — Course-specific rendering belongs to course format plugins (2026-04-11)

**Decision.** LernHive's experience types (Course, Snack, Community) are implemented as Moodle course format plugins. The theme provides chrome and blocks; course formats render the course content.

**Why.** Moodle core already treats course-format plugins as the place where course-content rendering lives (see `format_topics`, `format_grid`, `format_tiles`). Building the same capability inside a theme fights the grain: it prevents per-course customization, makes the theme non-portable, and tangles business-shaped presentation with site-wide chrome.

**Consequences.**
- Plugin map grows by 2–3 `format_lernhive_*` plugins (Layer 2a above).
- `theme_lernhive` becomes smaller and reusable — any Moodle theme will eventually host LernHive courses.
- Release 0.9.3 ships block regions and marks the current Snack partials as deprecated; removal happens in 0.10.0 when `format_lernhive_snack` lands.
- Community is a course format because community lifecycle (enrolment, sections, completion, gradebook integration) aligns with Moodle course semantics; no separate "community" object is introduced.

**Status.** Accepted. Implementation starts post-0.9.3.

## Snack guardrails

- created wizard-only
- no course sections
- no right-side course navigation
- expected duration 10–30 minutes
- max about three activities
- designed for user-generated, lightweight learning

## Explore rules

- LXP only
- Explore replaces Dashboard in the LXP Flavour
- content types in release 1:
  - Course
  - Snack
  - Community
- feed blocks stay slim and fixed
- no AI ranking in release 1
- ranking stays explainable
- Follow appears directly on cards and uses a star icon

## Notification model

Reuse Moodle core notification settings for:
- providers
- channels
- defaults
- user preferences

Extend only for:
- daily digest
- weekly digest
- LXP event bundling

Default for release 1:
- Community updates: digest
- Follow updates: digest

## Release boundaries

### Release 1
- guided core UX
- simple Audience rules
- Explore only for the LXP Flavour
- minimal version visibility for Library

### Release 2
- stronger personalization
- refined Audience UX
- richer lifecycle and version workflows
