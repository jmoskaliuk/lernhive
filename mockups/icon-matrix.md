# LernHive Icon Matrix

## Purpose

This matrix defines a first stable icon direction for LernHive mockups and later theme implementation.

The goal is not to copy Lucide mechanically, but to stay close to a calm, readable line-icon system with stable meanings.

Reference family:
- Lucide icon set: [lucide.dev/icons](https://lucide.dev/icons)

## Rules

- use a consistent line-icon family
- keep icon meanings stable across screens
- labels remain primary; icons support scanning
- prefer activity-type icons over decorative icons
- use orange emphasis only for highlighted or active states such as Follow

## Semantic icon taxonomy — four types

Every icon in LernHive belongs to exactly one of four semantic categories. The category is not just a colour decision — it decides shape, hover behaviour, and whether the icon is a control or a passive signal. Mixing categories on one control is what makes a UI feel arbitrary, so this rule is enforced in the theme CSS (`theme_lernhive/scss/lernhive/_icons.scss`).

| Type | Class | Shape | Intent | Hover |
| --- | --- | --- | --- | --- |
| 1. Navigation | `.lh-icon-nav` | 36×36, 8 px radius, transparent | takes the user somewhere | subtle tint, no grow |
| 2. Artifact | `.lh-icon-artifact` | 38×38, 9 px radius, colour-coded | classifies a content object (Course, Snack, Community, …) | none — it's metadata |
| 3. Action | `.lh-icon-action` | 36×36, circle | triggers a function (Edit, Delete, Start, Save) | circle grows + lifts |
| 4. Information | `.lh-icon-info` | 28×28, 6 px radius, `cursor: help` | passive signal (status, help, warning, error) | only a background tint deepen — no grow, no lift |

### Type 1 — Navigation

Rectangle with small radius. A link that moves you to a destination. Pure wayfinding: Home, Dashboard, My Courses, Notifications bell, Launcher. The hover tint is subtle because the icon is usually inside a larger nav context (sidebar, page header) that already communicates "these are nav items".

### Type 2 — Artifact

Colour-coded rounded square. Classifies a content object by its type so the user can scan a card grid and recognise Course vs Snack vs Community at a glance. It is NOT a control — if the surrounding card is clickable, the card handles the hover, not the icon. Colour is part of the meaning (blue = Course, orange = Snack, purple = Community), so designers must not recolour an Artifact icon to match a surface.

### Type 3 — Action

Circle. The distinct shape is what makes the "tap me" affordance learnable across the product. Hover grows the circle slightly and adds a soft lift shadow. Primary actions (Start, Confirm) get the orange `--primary` modifier, destructive actions (Delete, Revoke) get `--danger`, context-specific actions (Edit, Settings in admin) get `--nav`. Labels are mandatory where meaning isn't already clear from context.

### Type 4 — Information

Soft rounded square, slightly smaller than Action. The defining property is `cursor: help` and the total absence of click-like feedback: no grow, no lift, no transform, no box-shadow change. This tells the user "I explain, I don't act". Use it for:

| Scope | Suggested Lucide | LernHive modifier |
| --- | --- | --- |
| Locked / not yet available | `lock` | `.lh-icon-info--locked` |
| Completed | `check` or `circle-check` | `.lh-icon-info--complete` |
| New / unseen | `sparkles` or `dot` | `.lh-icon-info--new` |
| Pending / in progress | `clock` | `.lh-icon-info--pending` |
| Help / contextual explanation | `circle-help` | `.lh-icon-info--help` |
| Warning inline | `triangle-alert` | `.lh-icon-info--warning` |
| Error inline | `circle-x` | `.lh-icon-info--error` |
| Success confirmation | `circle-check` | `.lh-icon-info--success` |
| Neutral info banner | `info` | `.lh-icon-info--info` |

**Do NOT use Information for:**
- card action bar "info" buttons that open a detail popover — those are Actions (`.lh-icon-action`)
- content-type badges on cards — those are Artifacts (`.lh-icon-artifact`)
- clickable notification bells — those are Navigation (`.lh-icon-nav`)

If an icon currently looks like "info but also clickable", it's an Action, not Information. The shape (circle vs rounded square) and cursor (`pointer` vs `help`) must match the intent.

## Activity icons

| LernHive use | Preferred icon direction | Suggested Lucide name |
| --- | --- | --- |
| reading page / short intro | open content / reading | `book-open` |
| schedule / important dates | date / timing | `calendar` |
| document / short resource | readable text resource | `file-text` |
| task / checklist activity | action to complete | `check-square` |
| discussion / exchange | conversation | `messages-square` |
| short media input | quick watch / listen | `circle-play` |
| reflection / short writing | write / respond | `pen-line` |
| takeaway / completion point | marked end point | `flag` |

## Product and UX icons

| LernHive use | Preferred icon direction | Suggested Lucide name |
| --- | --- | --- |
| Launcher | primary quick action trigger | `plus` or `badge-plus` |
| ContentHub | grouped content entry | `layout-grid` |
| Copy | reuse existing content | `copy` |
| Template | predefined structure | `file-stack` |
| Library | managed content import | `archive` |
| Course | structured learning space | `book-open` |
| Snack | lightweight learning item | `circle-play` |
| Community | group / people space | `users` |
| Explore | guided discovery | `compass` |
| Follow | relationship action | `star` |
| Bookmark | save for later | `bookmark` |
| Reporting | overview / metrics | `chart-column` |
| Notifications / digest | summary updates | `inbox` or `bell` |

## Standard action icons

| Action | Meaning | Suggested Lucide name |
| --- | --- | --- |
| Save | confirm and persist changes | `save` |
| Save as draft | persist without publishing or finishing | `file-clock` or `save` with label |
| Publish | make visible / activate | `send` or `badge-check` |
| Download | get a file or export locally | `download` |
| Upload | add file or import content | `upload` |
| Search | find content, users, or settings | `search` |
| Filter | narrow visible results | `sliders-horizontal` or `filter` |
| Sort | change order of results | `arrow-down-wide-narrow` |
| Edit | change an existing item | `pencil` or `pen-line` |
| Delete | remove an item | `trash-2` |
| Duplicate / copy | create a reusable copy | `copy` |
| Add new | create new item from current context | `plus` |
| Open | go to detail or open a surface | `arrow-right` |
| Back | go one level back | `arrow-left` |
| Close | dismiss panel, modal, or message | `x` |
| Settings | open configuration | `settings` |
| More actions | open contextual action menu | `ellipsis` or `more-horizontal` |
| Notifications | show updates and inbox-like signals | `bell` or `inbox` |
| Help | open help or explanation | `circle-help` |
| Bookmark | save for later | `bookmark` |
| Follow | subscribe to meaningful updates | `star` |

## Card action icons (Plugin Shell)

These icons form the standard action strip at the bottom of every content card. Usage and appearance depend on context — not all icons appear on every card.

| Action | Icon (Lucide) | Button style | When to show | Label required? |
|--------|--------------|--------------|-------------|-----------------|
| Start | `play` | `lh-btn--accent` (orange filled) | Active/current items only | Yes always |
| Öffnen | `arrow-right` | `lh-btn--primary` (nav-dark filled) | Navigation to detail view | Yes always |
| Wiederholen | `rotate-ccw` | `lh-btn--ghost` (outline) | Completed tour steps | Yes |
| Kopieren | `copy` | `lh-btn--ghost` icon-only | Where duplication is available | No (tooltip) |
| Löschen | `trash-2` | `lh-btn--danger-ghost` (red outline) | Admin / Trainer role only | No (tooltip) |
| Info | `info` | `lh-btn--ghost` icon-only | Always — every card | No (tooltip) |

### Card action layout rules

- Primary actions (Start, Öffnen) are leftmost, always carry a text label
- Info is always rightmost, separated from other actions by a flex spacer
- Löschen never appears as a default visible action in grid views — move it behind `ellipsis` for list/table rows
- Disabled actions (locked state) use `opacity: 0.5` and `pointer-events: none`, not removal
- Maximum 2 visible labeled buttons per card; other actions are icon-only

## Action icon rules

- standard actions should use the same icon everywhere in the product
- action icons should behave like software controls, not like decorative illustration
- the label remains mandatory where meaning may be unclear
- `icon-only` is preferred for stable software actions in clear contexts such as toolbars, utility bars, inline controls, and repeated list actions
- `Create` and `Open` can use the shared standard icons when the surrounding context makes the target obvious
- larger product CTAs may still use `icon + text` even when the icon itself is standardized
- avoid using different icons for the same action in different plugins
- if Moodle core already establishes a strong action icon pattern, LernHive should stay close to it unless clarity improves clearly
- destructive actions such as delete should be visually distinguished by color or context, not by a different icon family
- do not rely on icon-only controls when accessibility or comprehension would suffer

## Moodle implementation note

- the real theme should not hardcode final SVG choices too early
- first implementation can keep a stable semantic mapping layer
- later theme work can map Moodle activity types and LernHive concepts to chosen icons through templates or pix handling
- if Lucide-compatible icons are adopted in the final theme, the mapping should live in one place rather than being repeated per template

## Current mockup use

- `mockups/launcher.html` uses:
  - `plus`
  - `compass`
  - `book-open`
  - `layout-grid`
  - `file-stack`
  - `archive`
  - `copy`
  - `circle-play`
  - `users-plus`
- `mockups/course-page.html` uses:
  - `book-open`
  - `calendar`
  - `file-text`
  - `check-square`
  - `messages-square`
- `mockups/course-snack.html` uses:
  - `circle-play`
  - `pen-line`
  - `flag`
- `mockups/explore.html` uses:
  - `compass`
  - `book-open`
  - `users`
  - `layers`
  - `chart-column`
  - `search`
  - `circle-play`
  - `star`
  - `bookmark`
  - `arrow-right`
