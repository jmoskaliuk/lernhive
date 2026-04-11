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

### Sticky positioning (since 0.9.53)

`.lernhive-page-header` is sticky to the viewport top (`position: sticky; top: 0`) so the action-icon row stays reachable while the user scrolls a long page. Two things are load-bearing for this to keep working:

**1. `_sidepanel.scss` must not reset `position`.** An older override already set `.lernhive-page-header { position: relative; z-index: 1092 }` to lift the header into its own stacking context above the side-panel backdrop (1090) and the panel itself (1091). Because `_sidepanel.scss` is imported **after** `_layout.scss` (see `lib.php` `theme_lernhive_get_extra_scss()`), a bare `position: relative` there would cascade-over the sticky rule and silently kill stickiness. The override is therefore written as:

```scss
// _sidepanel.scss
.lernhive-page-header {
    position: sticky;   // preserve stickiness from _layout.scss
    top: 0;
    z-index: 1092;      // above .lh-sidepanel-backdrop (1090) + .lh-sidepanel (1091)
}
```

When adding new `.lernhive-page-header` rules in a later-loaded partial, **never** set `position` to anything other than `sticky`. If you need a different position mode, refactor the sticky rule out of `_layout.scss` first and document why.

**2. Plugin Shell pages offset `.lh-plugin-header` to `top: 3rem`.** `.lh-plugin-header` (`_plugin-shell.scss`) has been sticky since 0.9.40 at `top: 0`. With the theme header now also sticky at `top: 0`, they would overlap. On Plugin Shell pages the theme header hides its `__main` area and only shows the ~48 px action-icon row, so offsetting the plugin header to `top: 3rem` makes the two stack cleanly:

```
┌──────────────────────────────────────┐  ← viewport top
│ .lernhive-page-header (sticky, 48px) │
├──────────────────────────────────────┤  ← top: 3rem
│ .lh-plugin-header      (sticky)      │
├──────────────────────────────────────┤
│ (scrolling content)                  │
```

If the action-icon row grows (e.g. a new dock button pushes height past ~48 px), bump `.lh-plugin-header { top }` to match or the two stacking headers will overlap.

**Z-index ladder.** The sticky page header uses `z-index: 30` by default and `1092` while a side panel is open. The plugin header sits at `z-index: 20`. The fixed sidebar is at `z-index: 100`. Side-panel backdrop `1090`, side panel `1091`. Launcher / user dropdowns inside the header are at `z-index: 1050` but participate in the header's own stacking context, so they still render above the page content without colliding with the fixed sidebar (which is on the opposite edge).

**Side-panel offset stays accurate.** `templates/sidepanel.mustache` measures `.lernhive-page-header` via `getBoundingClientRect().bottom` on open and writes `--lh-sidepanel-top` in pixels. Because `getBoundingClientRect()` is already viewport-relative, the measurement stays correct whether the header is in its natural position or stuck — no code change was needed in the side panel JS.

### SCSS files
| File | What it contains |
|---|---|
| `_layout.scss` | `.lernhive-page-header` shell **+ sticky rule** (base `position: sticky; top: 0; z-index: 30`); `.lernhive-user-block` avatar + action icons (including `.userinitials` mask); `.lernhive-admin-topnav` (removed 0.9.34, CSS now in `.lernhive-secondary-navigation`) |
| `_navigation.scss` | `.lernhive-page-header__launcher` — launcher toggle + dropdown; `.lernhive-lang-menu` — globe prefix styling |
| `_plugin-shell.scss` | `.lh-plugin-header` sticky at `top: 3rem` so it stacks below the theme page header (since 0.9.53) |
| `_sidepanel.scss` | Side panel system (Messages / Notifications / AI / Help — added 0.9.36); preserves sticky on `.lernhive-page-header` while bumping its z-index to 1092 above the panel backdrop |

### Design decisions
- Sidebar is purely navigational (since 0.9.26): no launcher, no action controls.
- `output.user_menu` is dropped: Moodle's default dropdown mixed navigation and actions. The custom user block splits them into explicit direct links.
- Avatar hover is strictly circular (`border-radius: 50%`) — the generic `.nav-link` hover rule uses `$lh-radius-sm` (rectangle), so `.lernhive-user-block__avatar` must explicitly set `border-radius: 50%` to win.
- Language selector is always wrapped: `.lernhive-lang-menu` provides a stable styling hook regardless of Moodle's internal `lang_menu()` markup changes.

## Course-page sidebar (since 0.9.45)

On the `course` pagelayout the theme swaps `sidebar.mustache` for a dedicated `sidebar_course.mustache` partial. The goal is to keep the global navigation cognitively available while foregrounding the course-specific section/activity tree — the same split Boost's drawer achieves, but without the right-hand collapsible drawer and inside the LernHive dark palette.

### Files

| File | Role |
|---|---|
| `lib.php` → `theme_lernhive_get_course_sidebar_context(\moodle_page $page)` | Builds the sidebar context: whitelisted primary-nav items + pre-rendered course-index HTML + aria labels. Shared by `layout/course.php`. |
| `templates/sidebar_course.mustache` | Renders the reduced primary nav (`.lernhive-nav--reduced`), the separator `<hr class="lernhive-nav__divider" role="separator">`, and the course-index nav region with a heading + body. Also renders the shared `sidebar-bottom` block region. |
| `scss/lernhive/_navigation.scss` | All styling — reduced nav, divider, `.lernhive-course-index` heading + icon, and the full scoped reset of the core `.courseindex*` chrome inside `.lernhive-course-index__body`. |
| `layout/course.php` | Calls the helper and forwards `coursesidebar` + `hassidebarbottom` / `sidebarbottom` into the template context. |

### Reduced primary nav (whitelist)

`theme_lernhive_get_course_sidebar_context()` walks the same primary-nav source as `sidebar.mustache` but filters down to a fixed set of nav keys — `home`, `myhome`, `courses` (standard Moodle site nav, named `Dashboard`, `My Courses`, `Explore` in the LernHive language pack). Any other nav item is dropped for the course layout. The whitelist is enforced in PHP so a later core navigation refactor can add or rename keys without breaking the sidebar.

### The `$PAGE->course` vs `$COURSE` divergence (fixed 0.9.51)

On the `course` pagelayout, a naive `$page->course->id > SITEID` guard inside the helper short-circuits because `$PAGE->course` reports as SITE (`id = 1`) even though `require_login($course)` has already run upstream. Diagnostic HTML-comment instrumentation in 0.9.48 captured this directly: `courseid=none status=skipped`, in a context where the course *obviously* existed.

**Why.** Moodle only hydrates `$PAGE->course` with the real course record when the course format's own renderer runs, which happens *after* the layout template has built its sidebar context. By contrast, `$COURSE` is set by `require_login($course)` at the very start of the request, so it is reliably populated by the time the layout helper executes.

**The fix (0.9.51).** The guard reads both sources and prefers `$PAGE->course` when it looks valid, then falls back to `$COURSE`:

```php
global $COURSE;
$course = null;
if (!empty($page->course) && is_object($page->course)
        && !empty($page->course->id) && $page->course->id > SITEID) {
    $course = $page->course;
} else if (isset($COURSE) && is_object($COURSE)
        && !empty($COURSE->id) && $COURSE->id > SITEID) {
    $course = $COURSE;
}
if ($course !== null) {
    try {
        $format   = course_get_format($course);
        $renderer = $format->get_renderer($page);
        if (method_exists($renderer, 'course_index_drawer')) {
            $courseindexhtml = (string) $renderer->course_index_drawer($format);
        }
    } catch (\Throwable $e) {
        debugging('theme_lernhive: course index render failed — ' . $e->getMessage(), DEBUG_DEVELOPER);
        $courseindexhtml = '';
    }
}
```

**Rule for future theme helpers:** inside a layout helper that runs during template context assembly, always consult `$COURSE` (global) as the secondary source of truth for the active course. Do not rely on `$PAGE->course` alone — it is not populated yet for the `course` pagelayout.

### How the course-index HTML is produced

The helper calls `course_get_format($course)->get_renderer($page)->course_index_drawer($format)` — the same canonical path `theme_boost/layout/drawers.php` takes via `core_course_drawer()`. This function returns a `<nav id="courseindex">` scaffold plus the `core_courseformat/local/courseindex/placeholders.mustache` skeleton, and — importantly — registers the `core_courseformat/courseeditor` AMD module on `$PAGE->requires` via `include_course_editor()`. After first paint, the AMD module hits the `core_course_get_state` webservice and hydrates the placeholder into the real sections + activities DOM.

**Do not try to "pre-render" the course index server-side.** The sections/activities are not in the returned HTML at all — they arrive via JS. Any attempt to walk `$format->get_sections()` yourself and build custom markup will fight the AMD hydration and end up either double-rendered or empty.

### Core courseindex chrome reset (0.9.52)

The core `.courseindex*` selectors ship with a Boost-tailored light palette: `$gray-100` for the active section background, `$gray-300` borders, rectangular chevron buttons, a `.current-badge` pill on the active section. Dropped into the LernHive dark sidebar that reads as grey "buttons" with visible borders floating over the dark background — completely wrong.

The fix lives entirely in `_navigation.scss` inside the `.lernhive-course-index__body` scope, so core pages (`/course/view.php` when rendered without the LernHive sidebar) are unaffected:

```scss
.lernhive-course-index__body {
    // Strip all Boost chrome from every nested courseindex element.
    .courseindex,
    .courseindex-section,
    .courseindex-item,
    .courseindex-link,
    .courseindex-chevron,
    .icons-collapse-expand,
    .courseindex-sectioncontent {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        padding: 0;
        margin: 0;
    }

    .courseindex-sectioncontent { padding-left: 1.5rem; }

    // Chevron: 1.25rem inline-flex, 0.75rem icon, no button chrome.
    .courseindex-chevron,
    .icons-collapse-expand {
        width: 1.25rem;
        height: 1.25rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        .icon { font-size: 0.75rem; }
    }

    // Section title = emphasis, activity = body.
    .courseindex-section-title .courseindex-link {
        color: rgba(#fff, 0.9);
        font-weight: 600;
        &:hover { background: rgba(#fff, 0.08); }
    }
    .courseindex-item .courseindex-link {
        color: rgba(#fff, 0.72);
        font-weight: 400;
    }

    // Active / current item → accent tint + left rail.
    .courseindex-item.pageitem,
    .courseindex-item.active,
    .courseindex-item[aria-current="page"] {
        background: rgba($lh-accent, 0.18);
        box-shadow: inset 2px 0 0 $lh-accent;
        color: #fff;
        font-weight: 600;
    }

    // "Current" pill is redundant with the accent treatment.
    .current-badge { display: none !important; }
}
```

Heading treatment (`_navigation.scss`, `.lernhive-course-index__heading`): `display: flex; gap`, `$lh-font-size-sm`, weight 600, `rgba(#fff, 0.78)`, **no `text-transform: uppercase`**. The icon (`.lernhive-course-index__heading-icon`, `fa-sitemap`) is `$lh-accent` at 0.95em, opacity 0.9 — an explicit visual cue that this region is the course navigation, not a generic link list.

### Skeleton placeholder hide (0.9.54)

`core_courseformat/local/courseindex/placeholders.mustache` renders a 4-row grey-pulse skeleton (`#course-index-placeholder[data-region="loading-placeholder-content"]` wrapping `ul.placeholders` with `.bg-pulse-grey` children) that is visible for ~50–300 ms between first paint and AMD hydration. Against the dark LernHive sidebar, those light-palette grey rounded rectangles read as a layout bug.

All three selectors are hidden inside `.lernhive-course-index__body`:

```scss
#course-index-placeholder,
[data-region="loading-placeholder-content"],
.placeholders {
    display: none !important;
}
```

**Graceful-fail rationale.** If the AMD hydration ever fails (JS disabled, require error, web service 500), the sidebar will show an empty course-nav region with just the heading instead of falling back to the placeholder. This matches the rest of LernHive's progressive-enhancement posture — a silently empty region is better than a styled-for-light-mode skeleton leaking through.

### Design decisions

- **The reduced nav whitelist lives in PHP**, not in the template. Mustache can't conditionally drop nav items based on stable keys without a helper, and doing it in the template would also make the whitelist invisible to `grep`. Keeping it in `lib.php` means any future renaming is caught by PHPUnit / lint.
- **The heading is a real `<h2>`, not a styled `<div>`**, so assistive tech picks it up as a landmark boundary between the primary nav and the course index.
- **The divider is a real `<hr role="separator" aria-orientation="horizontal">`**, not a CSS border on one of the nav items, so it is announced as a section boundary between the two nav regions.
- **`course_index_drawer()` is the canonical integration point**; we do not re-implement the course-index renderable. This keeps us auto-compatible with future core refactors of the courseindex model (Moodle 5.2 has already moved pieces around).

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

### Positioning (since 0.9.50)
Desktop: the Dock is anchored to the viewport's bottom-right corner via `position: fixed; right: 1.5rem; bottom: 1.5rem; left: auto; width: auto; max-width: calc(100vw - 3rem)`. `justify-content: flex-end` pins the right-most icon to the right edge, so added items grow the strip leftward — there is no fixed width container to blow out. The `max-width` guard prevents overflow on narrow desktop windows before the mobile breakpoint kicks in. Mobile (`@media (max-width: $lh-bp-desktop - 1px)`): full-width strip at the screen bottom with `justify-content: center` explicitly re-applied so the desktop flex-end rule does not leak through.

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

## Layout pitfalls (hard-won)

Small selector details around `#page` and the fixed sidebar have bitten us more than once — document them here so future edits don't step on the same rakes.

### Moodle 5.x `#page` has no `.drawers` class

Moodle core still ships a plain rule:

```css
#page { margin-top: 60px; }
```

to reserve space for the fixed Boost navbar. In older Moodle versions this was qualified as `#page.drawers`, and `post.scss` used to carry a matching `#page.drawers { ... }` reset. In Moodle 5.x the `.drawers` class is no longer applied — only the `#page` id is targeted — so any selector written as `#page.drawers` is a silent no-op. Always match via `.theme-lernhive-shell #page` (our wrapper class on `#page-wrapper`) when overriding core `#page` rules. Verify with DevTools: `document.getElementById('page').className` should be `"lernhive-page"` on drawers pages and `"lernhive-page lernhive-admin-page"` on admin pages — never `drawers`.

### Never `!important`-override `margin-left` on `#page`

`.lernhive-page { margin-left: $lh-sidebar-width; }` (in `_layout.scss`, desktop media query) is the single mechanism that pushes the content column past the 260 px fixed sidebar. If a later rule collapses that with `margin-left: 0 !important` on `#page`, the entire content column slides back to `x = 0` and hides under the sidebar. Visible symptoms:

- Admin secondary-nav tabs (General | Users | Courses | …) render at `x ≈ 40–210` — inside the 0–260 sidebar band. They look visible but are unclickable because `elementFromPoint()` hits `.lernhive-sidebar` first (z-index 100 wins over a `position: static` content column).
- `.lernhive-page-header` (z-index 1092, higher than the sidebar) stretches across the full viewport width and covers the top of the sidebar — including the LernHive brand link at `(16, 24)`. Hover / click on the brand silently hits the header instead.

When adding resets, scope them to the specific properties you actually need. `margin-top: 0` is safe; `margin-left: 0` is almost never safe. `_sidepanel.scss` explains why the header has `z-index: 1092` — it needs to sit above the side-panel backdrop (1090) and panel (1091) — so bumping the header's z-index down isn't an option either.

### `.lernhive-page-header` is `position: relative` — it only behaves if its parent is offset

Because the page header has `z-index: 1092` but `position: relative`, it only looks correct *inside* a page column that already has `margin-left: $lh-sidebar-width`. Any layout or page where `#page.lernhive-page` ends up at `left: 0` will cause the header to visually overlap the sidebar regardless of the page-header's own padding. The fix is always on the parent (`#page` margin-left), never on the header itself.

### Debugging layout bugs: live DevTools beats guessing

For CSS / layout regressions the fastest path is:

1. Navigate to the broken page on `dev.lernhive.de` (the Hetzner dev deploy is always close to main).
2. Use DevTools / the Claude-in-Chrome `javascript_tool` to read `getComputedStyle()` on `#page`, `.lernhive-app-shell`, `.lernhive-page-header`, `.lernhive-sidebar`, and — critically — run `document.elementFromPoint(x, y)` at the suspect click coordinates. If a link's hit target isn't the link itself, the layout is wrong; if it is the link itself but clicks still don't fire, look at JS (pointer-events, overlapping form elements, or a missing `theme_boost/loader` require).
3. Walk the matched style rules for the suspect property via `document.styleSheets` to find out *which* selector is winning. That's how the `#page.drawers` → `#page` mismatch from 0.9.41/0.9.43 was identified.

### Don't forget Moodle's theme cache after SCSS changes

Once the Hetzner pipeline deploys new SCSS, the compiled theme CSS on the server is still stale until the theme cache is purged. Run `admin/cli/purge_caches.php` or hit `/admin/purgecaches.php`. If the browser is also caching, append `?reload=1` to any page URL to force Moodle to rebuild the CSS bundle.

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
