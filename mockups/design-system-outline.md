# LernHive Design System Outline

## Purpose

This outline turns the current mockup work into a reusable design system direction for LernHive.

It should help keep School, optional LXP, launcher, course pages, reporting, and future admin surfaces visually consistent and easier to implement in Moodle.

## Core goals

- reduce visible complexity
- make the next useful action obvious
- keep Moodle-based structures understandable
- support both School and optional LXP without splitting into two unrelated products
- keep accessibility and responsive behavior as default requirements

## Information architecture model

### Primary surface types

- `Daily`
  - the place for immediate tasks, continue/resume, required items, due soon, and recent course-related work
- `Explore`
  - optional LXP-only discovery surface
  - broader, but still calm and explainable
- `Operate`
  - admin, reporting, setup, configuration, and management surfaces

### Navigation principles

- left navigation is primary
- top navigation stays small and utility-focused
- Launcher is for actions, not navigation
- Context Helper supports the current page, but does not become a second navigation system

## Content hierarchy rules

### Priority order

1. must do now
2. continue what is already in progress
3. useful next steps
4. broader discovery

### Dashboard rule

- dashboards must act as handlungsoberflaechen, not as content wallpaper
- every section should answer one clear user question
- sections should stay small enough to scan quickly

## Layout system

### Global shell

- stable left rail
- broad content area
- optional quiet helper column when needed
- strong panel grouping and readable spacing

### Page patterns

- dashboard / home
- discovery / explore
- course
- snack
- content orchestration
- reporting
- forms and setup

## Component families

### Core components

- shell
- section header
- hero panel
- card
- card section
- list item
- tile
- table
- filter bar
- search field
- helper panel
- status banner
- toast / inline feedback
- empty state
- form field group
- stepper / progress pattern

### Shape logic

- structural containers may use calmer and more angular geometry
- panels, cards, tables, and helper surfaces should communicate structure first
- explicit interactive actions should be rounder and easier to recognize as clickable
- buttons, CTA pills, launcher triggers, and comparable action elements should usually use a softer or fully rounded radius
- the UI should make the difference between structure and interaction visible through shape, not only through color

### Relationship actions

- Follow with star icon
- Bookmark as separate save-for-later action
- labels stay visible or at least recoverable through tooltip/accessible name

## State model

Every important component should have defined states:

- default
- hover
- focus-visible
- active
- loading
- empty
- error
- success
- disabled

### Important system-level states

- nothing assigned yet
- no results found
- no access
- first-run / onboarding
- due soon
- overdue
- completed

## Search and filter pattern

- search should stay globally recognizable
- filters must remain visible and understandable
- active filters must always be obvious
- mobile should use a drawer or compact filter pattern, not hide filtering completely
- Explore and reporting should share the same filter behavior logic where possible

## Table and admin pattern

- support finding, comparing, editing, and acting
- do not overload first view with all controls
- keep advanced options secondary
- allow drilldown instead of forcing broad dashboard density

## Form pattern

- reduce decision load
- prefer grouped steps over giant forms
- use helper text only where it prevents mistakes
- show consequences of important settings clearly
- keep role and capability complexity readable

## Responsive rules

- mobile is responsive and touch-friendly, not app-first
- left navigation may collapse, but primary orientation must remain clear
- helper columns stack under main content when needed
- filters, launcher, and actions must remain reachable on smaller screens

## Accessibility rules

- WCAG AA direction by default
- visible focus states
- no color-only meaning
- icons support, but do not replace labels
- semantic structure first
- predictable keyboard order
- clear empty, error, and success messages

## Icon system

- use one stable Lucide-oriented mapping
- keep icon meanings consistent across screens
- product icons and activity icons should come from the same family
- maintain the mapping centrally in `mockups/icon-matrix.md`
- define a stable software-style action icon set for common actions such as save, download, search, filter, edit, delete, open, close, and settings
- do not vary standard action icons casually between plugins or screens
- if an action icon appears without text, the context and accessible name must still make the action obvious
- allow `icon-only` for stable software actions in clear contexts such as toolbars, card utility menus, inline actions, and top-level controls
- treat `Create` and `Open` as part of the stable action system when their meaning is obvious from context
- keep `icon + text` for larger product CTAs, ambiguous actions, and first-run guidance

## Tag system

- allow more than one tag when each tag communicates a different layer of meaning
- keep tag layers distinct instead of mixing unrelated meanings into one badge row

### Tag layers

- `Type`
  - what the item is, for example `Course`, `Snack`, or `Community`
  - usually `icon + text`
- `State`
  - current progress or status, for example `In progress` or `Completed`
  - usually text-first
- `Signal`
  - relevance or urgency, for example `Due soon`, `Required`, or `Updated`
  - use sparingly
- `Meta`
  - quiet facts such as `24 learners` or `15 minutes`
  - often better as inline metadata than as colored tags

### Tag rules

- tags should not look like buttons
- type tags may use icons to support quick scanning
- status and signal tags should not rely on color alone
- tags can be lightly rounded, but should stay less button-like than real actions

## Dashboard directions to explore

### Direction A

- operational daily dashboard
- strong focus on due, continue, and next actions
- good fit for School and role-based daily work

### Direction B

- calmer learning home with curated highlights
- fewer modules, more spacious cards
- good fit for optional LXP and lighter modern learning entry

## Implementation bridge for Moodle

- convert patterns into Mustache components
- map tokens to SCSS variables
- keep icon mapping centralized
- keep plugin logic out of the theme
- test the same component patterns in learner and admin contexts before expanding variants
