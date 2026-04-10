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
