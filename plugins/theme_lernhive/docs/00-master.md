# theme_lernhive — Master Document

**Plugin type:** theme
**Release target:** R1

## Purpose

LernHive visual layer, interaction system, and Moodle-facing theme implementation target.

## Role in LernHive

This plugin is part of the LernHive ecosystem and should fit the core rules:
- Moodle stays the core
- English-first terminology
- use Moodle core strings wherever possible
- no business logic in the theme
- must stay understandable for UX, docs, marketing and sales

## Main dependencies
- Moodle theme APIs
- product navigation and terminology rules
- LernHive plugin output that the theme styles but does not control functionally

## Main features
- design tokens and component styling
- responsive left-oriented navigation
- calm, touch-friendly page layouts
- Launcher and helper surfaces as action-oriented UI

## Release scope

### Phase 1 — Mockup
- define the visual direction for LernHive
- define page shells, navigation, cards, and action surfaces
- define responsive behavior for desktop and mobile
- align all theme decisions with the documented LernHive product model

### Phase 2 — Moodle theme implementation
- translate the approved mockup direction into `theme_lernhive`
- keep Moodle core functionality intact
- implement styling, layout regions, navigation treatment, and reusable components

### Release 1
- simple, guided, readable UI
- left navigation as the primary navigation pattern
- Launcher stays action-oriented and does not become full navigation
- Explore is styled only for the optional LXP Flavour

### Later
- richer refinement after the Release 1 baseline is stable
- no hidden movement of advanced interaction ideas into the initial theme scope

## DevFlow files
- 00-master.md
- 01-features.md
- 02-user-doc.md
- 03-dev-doc.md
- 04-tasks.md
- 05-quality.md
