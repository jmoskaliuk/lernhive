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

## Page header (since 0.9.26)

The page header (`lernhive-page-header`) is a horizontal strip at the top of the content column (right of the sidebar). It contains the page title on the left and a cluster of action controls on the right.

### Action control order (right-to-left reading: left-to-right in DOM)

1. **Notifications / plugin output** — `{{{ output.navbar_plugin_output }}}` — messages, notifications, etc. from core plugins
2. **Language selector** — `{{#haslangmenu}}<div class="lernhive-lang-menu">{{{ langmenu }}}</div>{{/haslangmenu}}` — globe icon prefix + `$OUTPUT->lang_menu()`; only visible when Moodle has more than one language installed
3. **Launcher** — `<div class="lernhive-page-header__launcher">{{> theme_lernhive/launcher }}</div>` — 9-dot grid icon; dropdown right-aligned; dark-on-white colors in this context
4. **User block** — custom avatar link + two direct-action icon buttons (preferences, logout) replacing `output.user_menu`. See the _User block_ section below for details.

### User block (current — since 0.9.27, restyled 0.9.37)

The user block (`lernhive-user-block`) replaces Moodle's `output.user_menu`. It renders three focused controls:

- **Avatar** (`lernhive-user-block__avatar`) — `<a href="{{ profileurl }}">` — direct profile link. Circular 36 × 36 px button. Wraps `{{{ useravatar }}}` (Moodle's output, which may be an `<img>` for uploaded photos or a `<span class="userinitials">` on Moodle 5.x when no photo exists).
- **Settings** (`lernhive-user-block__action`) — `<a href="{{ prefsurl }}">` — Preferences icon (fa-cog)
- **Logout** (`lernhive-user-block__action lernhive-user-block__action--danger`) — `<a href="{{ logouturl }}">` — sign-out icon (fa-sign-out), danger-red hover

The old `<details>/<summary>` chevron dropdown pattern (0.9.26 – 0.9.26) was removed in 0.9.27. There is no dropdown on the user block; each action is a direct link.

#### Avatar: no-photo state (Moodle 5.x)

Moodle 5.x renders initials as `<span class="userinitials size-35 bg-color-X">AU</span>` — **not** as `img.defaultuserpic`. The CSS in `_layout.scss` targets `.userinitials` directly:

```scss
.lernhive-user-block__avatar .userinitials {
    font-size: 0 !important;         // hides "AU" text
    background: $lh-primary-light !important;  // overrides Moodle's bg-color-X
    // ::before draws a Lucide "user" SVG via mask-image in $lh-primary
}
```

Do **not** add CSS targeting `img.defaultuserpic` — that element no longer exists on Moodle 5.x.

### Helper functions in lib.php

| Function | Role |
|---|---|
| `theme_lernhive_get_header_user_context($OUTPUT)` | Returns array with `profileurl`, `useravatar`, `logouturl`, `prefsurl`, `langmenu`, `haslangmenu`, `isloggedin`. Shared by `drawers.php` and `admin.php`. |
| `theme_lernhive_get_admin_topnav($PAGE)` | **Deleted in 0.9.34.** Admin nav now delegates to Moodle's `core\navigation\output\more_menu` via `$PAGE->secondarynav` (canonical Boost pattern). |

### Admin top navigation (current — since 0.9.34)

Admin pages render the secondary nav via `secondarymoremenu` / `{{> core/moremenu}}` — the same mechanism Boost uses. This produces the canonical 9-tab sequence (General | Users | Courses | Grades | Plugins | Appearance | Server | Reports | Development) with correct overflow handling. The earlier custom `theme_lernhive_get_admin_topnav()` + two-level `lernhive-admin-topnav` approach was removed in 0.9.34 because it produced a mixed L1/L2 list on `/admin/index.php`.

## Layout JS bootstrap contract (since 0.9.42)

**Rule.** Every layout Mustache template in `theme_lernhive/templates/` that hosts interactive Moodle/Boost UI — tabs, dropdowns, popovers, tooltips, collapsibles, anything wired via Bootstrap 5 data attributes — **must** include a trailing `{{#js}} require(['theme_boost/loader'], …) {{/js}}` block. Boost's upstream `drawers.mustache`, `columns1.mustache`, `columns2.mustache`, and `login.mustache` all ship this block for a reason: it is the single entry point that kicks off `theme_boost/loader.js`, which in turn calls `Aria.init()`, `rememberTabs()`, `enablePopovers()`, `enableTooltips()`, and a handful of other Moodle-wide JS bootstraps. Without it, the DOM is correct but no JS is wired up.

### The standard block

Place at the very end of the template, **after** `</html>`:

```mustache
{{!
    Bootstrap theme_boost/loader so Aria fixes, tab remembering,
    popovers, tooltips, dropdown keyboard nav and collapse interactions
    all register. Boost's upstream layout templates all have this block.
}}
{{#js}}
M.util.js_pending('theme_boost/loader');
require(['theme_boost/loader'], function() {
    M.util.js_complete('theme_boost/loader');
});
{{/js}}
```

### What breaks silently if you forget it

| Surface | Symptom |
|---|---|
| `/admin/search.php` secondary-nav tabs | Markup is correct (`role="tablist"`, `data-bs-toggle="tab"`, `#linkX`). Clicks update `location.hash` and even toggle the active `.nav-link`, but `theme_boost/aria.js` never installed `Aria.tabElementFix()`, so `bootstrap.Tab.show()` is never called and the visible `.tab-pane.active.show` stays on `linkroot`. |
| Any `.dropdown-toggle` with keyboard interaction | Mouse click may still work via Bootstrap's own inline handler; arrow-key navigation is dead. |
| `data-bs-toggle="popover"` / `data-bs-toggle="tooltip"` | No pop-up ever appears. Popovers and tooltips require `enablePopovers()` / `enableTooltips()` from `loader.js`. |
| `data-bs-toggle="collapse"` | Click may visually toggle the `.show` class but related Aria attributes don't update, and Moodle's keyboard collapse handlers don't fire. |
| Tab-remembering across page reloads | `rememberTabs()` never runs — reloading a page with `#linkusers` in the URL falls back to the first tab instead of restoring `linkusers`. |

### How to detect the bug

1. Open DevTools on the affected page and run `M.util.pending_js`. If you see `['init', 'core/first', …]` without a completed `theme_boost/loader` entry — or if `loader` is never even requested — the bootstrap is missing.
2. `grep -l "theme_boost/loader" theme_lernhive/templates/*.mustache` — any layout template that should have interactive UI but is missing from the list is suspect.
3. On the rendered page: `typeof require !== 'undefined'` should be `true` (that's RequireJS itself), but `typeof bootstrap` being `undefined` is normal — Bootstrap is pulled in through AMD modules, not as a global. The right thing to check is whether the page has the loader's side-effects, e.g. `document.querySelectorAll('[role="tablist"] [data-bs-toggle="tab"]')[0].onclick` — it stays `null` either way (the handler is delegated on `document`), but `M.util.pending_js` is the reliable source of truth.

### When the rule does NOT apply

- `columns1.mustache` (popup layout) and `login.mustache` — these pages currently have no Bootstrap tabs, popovers, tooltips, or dropdowns that require the loader. They have been intentionally left without the require block in 0.9.42. If you add any interactive Moodle component to these layouts in the future, add the loader block at the same time.
- Partials (`sidebar.mustache`, `footer.mustache`, `sidepanel.mustache`, etc.) — only the top-level *layout* template triggers the `{{#js}}` block. Partials have their JS requirements fulfilled via the surrounding layout's loader.

### Historical note

Between 0.9.34 (admin tab bar delegation) and 0.9.42 (this fix), admin tabs looked correct but never actually switched panels on click. The bug survived multiple styling passes (0.9.36 CSS scoping fix, 0.9.37 header dock rewire) because everyone — including Claude — read the symptom as "CSS still wrong" or "wrong data attribute on the tabs". The real cause was zero JS running. Diagnosing it required going all the way to `theme_boost/aria.js` source and noticing that its `document.addEventListener('click', …)` handler is only installed when `theme_boost/loader` is `require()`d — which our templates never did.

### SCSS files
| File | What it contains |
|---|---|
| `_layout.scss` | `.lernhive-page-header` shell; `.lernhive-user-block` avatar + action icons (including `.userinitials` mask); `.lernhive-admin-topnav` (removed 0.9.34, CSS now in `.lernhive-secondary-navigation`) |
| `_navigation.scss` | `.lernhive-page-header__launcher` — launcher toggle + dropdown; `.lernhive-lang-menu` — globe prefix styling |
| `_sidepanel.scss` | Side panel system (Messages / Notifications / AI / Help — added 0.9.36) |

### Design decisions
- Sidebar is purely navigational (since 0.9.26): no launcher, no action controls.
- `output.user_menu` is dropped: Moodle's default dropdown mixed navigation and actions. The custom user block splits them into explicit direct links.
- Avatar hover is strictly circular (`border-radius: 50%`) — the generic `.nav-link` hover rule uses `$lh-radius-sm` (rectangle), so `.lernhive-user-block__avatar` must explicitly set `border-radius: 50%` to win.
- Language selector is always wrapped: `.lernhive-lang-menu` provides a stable styling hook regardless of Moodle's internal `lang_menu()` markup changes.

## Context Dock (since 0.9.21)

The Context Dock is a floating, fixed-position action strip for context-aware actions. It is rendered only by `drawers.php` / `drawers.mustache` — admin pages (`admin.php`) do not include it.

### Files
| File | Role |
|---|---|
| `lib.php` → `theme_lernhive_get_context_dock_items()` | Builds the dock items array from page context + user capabilities |
| `layout/drawers.php` | Calls the function, passes `dockitems` + `hasdockitems` into template context |
| `templates/context_dock.mustache` | Renders the dock; includes inline JS IIFE for progressive disclosure |
| `scss/lernhive/_dock.scss` | All dock styles; registered in `get_extra_scss()` partials list |
| `lang/en/theme_lernhive.php` | `contextdock`, `dockblockson`, `dockblocksoff` strings |

### Dock items model
Each item is a PHP associative array with keys: `key` (string), `icon` (FA4 class suffix), `label` (string), `url` (string), `active` (bool), `divider` (bool — adds separator line BEFORE this item).

### Item decision table
| Condition | Item(s) added |
|---|---|
| Course page + `moodle/course:manageactivities` | Edit mode toggle, Participants, Gradebook, Course settings |
| `$PAGE->user_can_edit_blocks()` is true | Block editing toggle (any page, including dashboard) |
| `is_siteadmin()` + layout ≠ admin | Site admin shortcut (with separator) |

### Tooltip progressive disclosure
Inline JS IIFE in `context_dock.mustache` increments `lh_dock_v1` in localStorage on each page load. After `MAX = 3` loads, adds class `.lh-dock--experienced` to the dock element, which hides all `.lh-dock__tooltip` elements via CSS. Safe fallback: if localStorage is blocked, tooltips remain always visible.

### Future Dock items (deferred)
- Student progress shortcut — requires progress data from `local_lernhive_flavour` or similar
- Manager shortcuts — requires manager capability checks not yet defined
- "Continue learning" button — requires tracking of last-accessed activity per user

### Block editing vs. course edit mode
Both "course edit mode" and "block editing" share the same Moodle user preference (`$USER->editing`, reflected by `$PAGE->user_is_editing()`). The dock shows them as distinct icons because:
1. Block editing makes sense in non-course contexts (dashboard, frontpage) where there are no activities.
2. Teachers benefit from a visual separation between "I am editing course content" and "I am editing block layout".
The URLs use different base paths: course edit uses `/course/view.php?edit=on/off`, block editing uses `$PAGE->url?edit=on/off` (generic, works on any page).

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
- `config.php` declares the regions in `$THEME->layouts` via a shared `$lhregions` array, so every content-bearing layout gets the same set — no drift between `drawers`, `course`, and `report`. Admin pages use `admin.php` layout with no block regions (see ADR-04).
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
