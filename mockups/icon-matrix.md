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
