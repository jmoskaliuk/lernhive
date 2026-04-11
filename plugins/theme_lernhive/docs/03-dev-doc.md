# theme_lernhive — Developer Documentation

## Architecture note
LernHive theme implementation target on top of Moodle theme APIs.

## Technical direction
- keep boundaries clean
- use Moodle APIs where possible
- prefer existing core strings over plugin-specific duplicates
- keep data model minimal for release 1
- document release 2 complexity separately

## Theme implementation boundaries
- styling, layout regions, navigation treatment, and component rendering belong here
- business logic remains in LernHive plugins and Moodle core
- theme decisions must support both School and optional LXP usage without splitting into separate products

## Mockup-to-implementation path
- phase 1 defines visual tokens, shells, and key component patterns
- phase 2 maps those decisions to Moodle theme templates, SCSS, renderers, and settings where needed
- implementation should start from stable page shells and reusable components rather than page-by-page exceptions

## Release 1 technical scope
- left navigation as the primary navigation pattern
- utility-focused top bar
- card and panel system for LernHive-specific surfaces
- responsive behavior for desktop and mobile
- flavour-aware styling differences only where the product docs explicitly require them
- Launcher base pattern implemented in the theme as a compact flyout with an optional dock-style enhancement
- Explore shell components in the theme should remain generic enough for `local_lernhive_discovery` to supply the actual block content
- ContentHub shell components in the theme should support orchestration UI only and avoid absorbing Copy or Library logic
- course/incourse layouts provide chrome only (header, sidebar, footer, block regions) — course content rendering moves to course format plugins per ADR-01
- short-form Snack presentation is **not** owned by the theme; planned `format_lernhive_snack` plugin will own snack templates (0.10.0)

## Block regions (since 0.9.3)

The theme defines six fixed block regions on all content-bearing layouts (standard, course, incourse, frontpage, admin, mydashboard, report):

| Region         | Position                                              | Default? |
|----------------|-------------------------------------------------------|----------|
| content-top    | Full-width, above main content                        | no       |
| content-bottom | Full-width, below main content                        | **yes**  |
| sidebar-bottom | Inside left sidebar, below flat navigation            | no       |
| footer-left    | Three-column footer, left slot                        | no       |
| footer-center  | Three-column footer, center slot                      | no       |
| footer-right   | Three-column footer, right slot                       | no       |

**Legacy `side-pre` (right-hand collapsible drawer) has been removed** as of 0.9.3. Any blocks that were assigned to `side-pre` will become orphaned on upgrade — acceptable while LernHive stays in alpha.

### How the regions are wired
- `config.php` declares the regions in `$THEME->layouts` via a shared `$lhregions` array, so every content-bearing layout gets the same set — no drift between `drawers`, `course`, and `admin`.
- `lib.php` → `theme_lernhive_get_block_regions_context($OUTPUT)` renders each region's block HTML and builds has-flags in camel-lite keys (`contenttop`, `hascontenttop`, …, `hasfooterblocks`).
- Each layout PHP file merges the region context into its template context via `array_merge`. This keeps the layout files focused on layout-specific context (launcher style, page header flags, body attributes).
- Templates reference regions as `{{#hascontenttop}}…{{{ contenttop }}}…{{/hascontenttop}}`. A dedicated `theme_lernhive/footer` partial renders the footer row so drawers, course, and admin all share the same footer markup.
- SCSS for the regions lives in `scss/lernhive/_blocks.scss` and is registered in the `$partials` array inside `theme_lernhive_get_extra_scss`.

### When to add a block region
Don't, unless a product requirement cannot be satisfied with the current six. Adding a new region means touching `config.php`, `lib.php`, every content-bearing layout PHP file, every content-bearing Mustache template, both lang files, and `_blocks.scss`. Prefer placing existing regions differently in CSS over adding new ones.

## Course format migration (post-0.9.3)
Per ADR-01, course-specific rendering leaves the theme. The migration order is:
1. **0.9.3 (this release)** — block regions in place; theme chrome is format-agnostic.
2. **0.10.0** — scaffold `format_lernhive_snack`, move `snack_*.mustache` out of the theme, drop snack-specific rules from `_course.scss`.
3. **0.11.0** — scaffold `format_lernhive_community` for community feeds.
4. **Later** — decide whether `format_lernhive_classic` is needed or if `format_topics` covers the baseline.

Until the format plugins ship, the theme's existing `snack_*.mustache` partials and `_course.scss` rules remain in place as dead code gated by the current `course.mustache` — they will be removed in 0.10.0.

## Current dependencies
- Moodle theme APIs
- Moodle navigation and rendering system
- LernHive product documentation and plugin output structures

## Integration points
- Moodle core APIs
- Moodle theme configuration and rendering hooks
- LernHive shared services as needed
- theme integration only for styling, not for business logic

## Implementation notes
- prefer Moodle region and renderer extension points over custom structural hacks
- keep component names and tokens consistent so mockups can be translated into SCSS/templates later
- avoid theme features that would force custom plugin behavior just to render correctly
- use Mustache partials for reusable shell pieces such as sidebar, launcher, and cards
- keep Launcher interaction simple and accessible; use lightweight native patterns before adding JS
- prepare generic card partials that plugins can reuse for Explore, reporting tiles, and content surfaces without moving plugin logic into the theme
- treat Explore as a themed page pattern with hero, feed sections, and card presentation; ranking and block assembly remain plugin responsibilities
- treat ContentHub as a themed decision surface that routes into distinct plugin flows for Copy, Template, and Library
- treat course pages as content-first surfaces; Context Helper and block output may appear in a secondary helper area instead of becoming primary navigation
- treat Snack-oriented surfaces as short-form presentation variants with visible duration, compact actions, and linear step treatment; actual Snack detection remains outside the theme
