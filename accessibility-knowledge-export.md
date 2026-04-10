# Accessibility Knowledge Export

## Purpose

This document exports the core knowledge from the accessibility audit skill package into the LernHive repository.

It is intended as a practical reference for:
- UX and mockup work
- plugin development
- theme implementation
- accessibility reviews of Moodle-based product surfaces

## Primary standards and compliance context

### Primary technical basis
- WCAG 2.2 Level AA

### Secondary mapping
- EN 301 549 for EU ICT contexts

### Contextual legal mapping
- BITV 2.0 for German public-sector contexts
- BFSG for relevant private product and service contexts

## Core working principles

- Never claim full accessibility or full legal compliance.
- Separate observation, interpretation, and recommendation.
- Prefer native HTML semantics over ARIA workarounds.
- Use WCAG 2.2 success criteria as the primary normative anchor.
- Use legal mappings carefully and only when the context supports them.
- Prioritize user impact in core workflows over purely technical deviations.
- Clearly mark uncertainty when evidence is incomplete.

## What this knowledge is for

- auditing websites, web apps, and web-based plugins
- identifying likely barriers in interfaces and workflows
- creating structured accessibility findings with fix guidance
- improving design and implementation decisions before release

## What this knowledge is not for

- final legal opinions
- formal conformity certification
- replacing manual testing with keyboard, screen reader, and real users

## Audit modes

- `best_practice_wcag`
- `eu_public_sector`
- `de_public_sector`
- `bfsg_private_service`
- `unknown_context`

If the legal or organizational context is unclear, default to a technical WCAG 2.2 AA review.

## Accessibility review areas

- semantics and structure
- accessible names, roles, values, and states
- keyboard access
- focus management
- forms and validation
- contrast and visual perception
- zoom and reflow
- dynamic updates and status messages
- complex widgets
- public-sector additions such as accessibility statements

## Core workflow priorities

Accessibility issues should be prioritized especially in:
- navigation
- login
- search
- data entry
- form completion
- checkout or booking
- saving or publishing
- dialogs and overlays
- keyboard-only use

## Finding status model

Each finding should use exactly one status:
- `confirmed_issue`
- `probable_issue`
- `manual_check_required`

## Testability model

Each finding should use exactly one testability class:
- `automatic`
- `semi_automatic`
- `manual`

## Severity model

- `blocker` = a core task is practically unusable
- `high` = strong barrier in an important task
- `medium` = usage is clearly impaired
- `low` = limited impact or quality issue

## Confidence model

- `high`
- `medium`
- `low`

## Recommended prioritization logic

`priority_score = severity × workflow_criticality × breadth_of_impact × confidence`

### Suggested weights

#### Severity
- blocker = 4
- high = 3
- medium = 2
- low = 1

#### Workflow criticality
- core_task_blocked = 4
- core_task_impaired = 3
- secondary_task = 2
- cosmetic = 1

#### Breadth of impact
- system_wide_component = 4
- multiple_pages = 3
- single_section = 2
- isolated_element = 1

#### Confidence
- high = 1.0
- medium = 0.7
- low = 0.4

## Required fields per finding

For each structured finding, include:
- `id`
- `title`
- `artifact_type`
- `location`
- `summary`
- `evidence`
- `classification.status`
- `classification.testability`
- `classification.confidence`
- `severity`
- `affected_users`
- `normative_mapping.wcag_22`
- `normative_mapping.en_301_549`
- `normative_mapping.bitv_relevant`
- `normative_mapping.bfsg_relevant`
- `why_it_matters`
- `recommended_fix`
- `manual_follow_up`

## Recommended report structure

1. Executive Summary
2. Top Priorities
3. Detailed Findings
4. Manual Follow-up Checklist
5. Compliance Context Note

## Rule modules from the skill

### 1. Semantics and structure

Common issues:
- missing or unclear main heading
- illogical heading hierarchy
- missing landmarks
- interactive elements without native semantics
- visually grouped content without semantic grouping
- data tables without proper header relationships

### 2. Names, roles, values

Common issues:
- buttons, links, or inputs without an accessible name
- mismatch between visible label and accessible name
- missing state exposure such as expanded or selected
- incorrect or unnecessary ARIA roles
- form fields without proper labels
- missing exposure of required, invalid, or disabled states

### 3. Keyboard and focus

Common issues:
- interactions that require a mouse
- keyboard traps
- illogical focus order
- weak or missing focus indicators
- dialogs without proper focus management
- missing skip links or bypass mechanisms

### 4. Forms and validation

Common issues:
- placeholder used as the only label
- errors not programmatically linked to fields
- unclear required field markers
- errors communicated by color only
- missing or unbound help text
- focus not moving meaningfully after failed submit

### 5. Visual perception

Common issues:
- insufficient text contrast
- insufficient non-text contrast
- broken layouts at 200 percent zoom
- forced horizontal scrolling in reflow scenarios
- information conveyed through color only
- layout failure under custom text spacing

### 6. Dynamic updates and status messages

Common issues:
- status messages not exposed to assistive tech
- auto-rotating content without pause or stop controls
- dynamic content stealing focus
- new content appearing without meaningful announcement
- unclear timeout or session warnings
- drag and drop without a full alternative

### 7. Complex widgets

Common issues:
- tabs with incomplete keyboard or role behavior
- disclosure patterns without proper state logic
- broken comboboxes or listboxes
- inaccessible date pickers
- fragile data grid implementations
- infinite scroll without orientation and announcement support

### 8. Germany and EU additions

Contextual checks:
- accessibility statement missing or hard to find
- no electronic feedback channel for accessibility barriers
- unclear legal context requiring a caution note

## Practical implementation notes

### For UX
- Do not rely on color alone for meaning.
- Keep workflows linear and explainable.
- Make primary actions visible without overloading the screen.
- Keep navigation, forms, and dialogs predictable.

### For plugin development
- Reuse native HTML wherever possible.
- Add ARIA only when native semantics are insufficient.
- Expose names, roles, states, and status changes programmatically.
- Avoid custom controls when a native control would work.

### For Moodle and theme work
- Keep region structure and heading hierarchy stable.
- Preserve keyboard access when customizing navigation or dialogs.
- Ensure focus visibility remains strong after theming.
- Test card systems, Launchers, overlays, and filters for keyboard and screen-reader use.
- Validate contrast against the chosen brand palette before final implementation.

## Suggested language for reports

- Say “technical indication of non-conformance with WCAG 2.2”, not “illegal”.
- Say “likely relevant in EN 301 549, BITV, or BFSG context” when the legal context is not fully established.
- Always explain user impact, not just the technical fault.
- Give concrete implementation guidance rather than abstract theory.

## Relevance for LernHive

For LernHive, this knowledge is especially relevant in:
- left navigation and page shells
- Launcher and Context Helper surfaces
- Explore cards and follow/bookmark actions
- forms, settings, and configuration flows
- onboarding journeys
- reporting tiles and drilldowns
- any dynamic updates or digest-related notification surfaces

## Practical reminder

Good accessibility work in LernHive should:
- support WCAG 2.2 AA technically
- remain compatible with Moodle core patterns where possible
- reduce complexity rather than increase it
- stay testable, explainable, and implementation-oriented
