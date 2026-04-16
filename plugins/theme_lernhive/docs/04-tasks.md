# theme_lernhive — Tasks

## Done (shipped)

- [x] 0.9.56 — DevFlow docs alignment for the 0.9.51/0.9.52/0.9.54 course-page sidebar fix cycle (2026-04-11):
  - `docs/01-features.md` — new "Course-page sidebar (since 0.9.45, render-guard fix 0.9.51, visual polish 0.9.52, placeholder-flash fix 0.9.54)" bullet documents that logged-in users on a course page see a reduced primary nav (Dashboard / My Courses / Explore) followed by a divider and the core Moodle course index with LernHive dark-palette styling, without uppercase and with a `fa-sitemap` icon on the heading.
  - `docs/02-user-doc.md` — new R1 experience bullet: "on a course page, the sidebar shows an icon-prefixed 'Course navigation' section (sections + activities) under the primary nav — no skeleton flash, no uppercase labels, no rectangular buttons bleeding through from Boost defaults".
  - `docs/03-dev-doc.md` — new "Course-page sidebar (since 0.9.45)" section under Page header docs, documenting:
    1. `templates/sidebar_course.mustache` replaces `sidebar.mustache` for the `course` layout via `theme_lernhive_get_course_sidebar_context()` in `lib.php`, and the split: reduced primary nav (whitelist keyed on the standard Moodle nav keys: `home`, `myhome`, `courses`) + `<hr class="lernhive-nav__divider">` + core course index.
    2. The `$PAGE->course` vs `$COURSE` divergence that blocked the render in 0.9.45–0.9.50: on the `course` layout template, `$PAGE->course->id` reports as `SITEID` even though `require_login($course)` has already run, so a bare `$page->course->id > SITEID` guard short-circuited the renderer. 0.9.51 added a `$COURSE` global fallback (`if ($page->course->id > SITEID use $page->course; else if ($COURSE->id > SITEID use $COURSE`) so the guard actually picks up the active course. Reason: for the `course` pagelayout, Moodle sets `$COURSE` at `require_login()` time but `$PAGE->course` is only populated when the course format's own renderer runs — by then we have already built the sidebar context. Always consult `$COURSE` as the secondary source of truth for the active course inside theme layout helpers.
    3. The course-index content is rendered via `course_get_format($course)->get_renderer($page)->course_index_drawer($format)` — the canonical Boost path. That call registers the `core_courseformat/courseeditor` AMD module via `include_course_editor()`, which then hydrates the server-side placeholder client-side via the `core_course_get_state` web service. The raw HTML returned from `course_index_drawer()` is just a `<nav id="courseindex">` scaffold plus the `core_courseformat/local/courseindex/placeholders.mustache` skeleton loader — the real sections/activities DOM is injected by JS after hydration. **Do not try to "pre-render" the course index server-side**; that is not how core expects a theme to embed it.
    4. Styling strategy (`_navigation.scss`): the core courseindex ships with a Boost-tailored light palette (`$gray-100` active background, `$gray-300` borders, `.current-badge` pill, rectangular chevron buttons), which looks wrong inside the dark LernHive sidebar — active sections render as grey "buttons" with visible borders. The fix is a scoped reset inside `.lernhive-course-index__body`: `background / border / box-shadow / padding / margin` set to transparent/0 for all nested `.courseindex*` elements, then re-apply LernHive-native rules: section titles at `rgba(#fff, 0.9) / weight 600`, activity links at `rgba(#fff, 0.72) / weight 400`, hover at `rgba(#fff, 0.08)`, active/pageitem at `rgba($lh-accent, 0.18)` with a `box-shadow: inset 2px 0 0 $lh-accent` left rail. `.current-badge` is hidden. Chevrons keep a `1.25rem` inline-flex box with no border/background/padding, icon at `0.75rem`. Heading is sentence-case (no `text-transform: uppercase`) with a `fa-sitemap` icon prefix inside a `display: flex; gap` wrapper.
    5. Skeleton placeholder hide (0.9.54): the core placeholders template (`#course-index-placeholder[data-region="loading-placeholder-content"]`, body class `.placeholders`) flashes for the ~50–300 ms between first paint and AMD hydration as four grey "pulse" rows. Inside `.lernhive-course-index__body` those three selectors are all set to `display: none !important`, so the user sees an empty heading block briefly instead of mangled grey boxes. The gracefully-failing empty region is less jarring than the core skeleton painted against the dark palette.
  - `docs/04-tasks.md` — this entry, plus three new `0.9.51`/`0.9.52`/`0.9.54` entries for the actual code changes. Also corrects the historical `0.9.51 Context Dock` label to `0.9.50` to match the `afe1dc7` commit on main (the parallel docs commit `517cc52` labelled the entry `0.9.51` but the underlying code change shipped as 0.9.50; the label is retroactively corrected here so the task log matches git truth).
  - `docs/05-quality.md` — new smoke-test entries for the course-page sidebar: reduced primary nav renders; core course index renders below the divider; no skeleton flash on load; no uppercase; active section has accent-tinted background + left rail; `.current-badge` hidden; heading has `fa-sitemap` icon prefix; clicking a section/activity navigates correctly (hydration path still works).
  - `version.php` — 0.9.55 → 0.9.56 / 2026041156. Docs-only bump; no code changes in this release.
- [x] 0.9.54 — Hide core course-index skeleton placeholder flash (2026-04-11):
  - `scss/lernhive/_navigation.scss` — inside `.lernhive-course-index__body`, `display: none !important` for `#course-index-placeholder`, `[data-region="loading-placeholder-content"]`, and `.placeholders`. The core `placeholders.mustache` template ships a 4-row grey-pulse skeleton that is visible for ~50–300 ms between first paint and the `core_courseformat/courseeditor` AMD hydration, and the Boost-tailored skeleton styles (rounded rectangles in `$gray-200` on a white background) look broken against the LernHive dark sidebar palette.
  - Motivation: Johannes reported a brief flash of mangled grey boxes on every course-page load. An empty heading region during the hydration wait is less jarring than a styled-for-light-mode skeleton leaking through.
  - Graceful-fail rationale: if the AMD hydration ever fails (JS disabled, require error, webservice 500), the sidebar will now show an empty course-nav region instead of the placeholder. This matches the rest of LernHive's progressive-enhancement stance — a silently empty region beats a visually broken one.
- [x] 0.9.52 — Course-page sidebar visual polish: icon, sentence case, dark-palette courseindex reset (2026-04-11):
  - `templates/sidebar_course.mustache` — `.lernhive-course-index__heading` restructured: `<h2>` now wraps a `<i class="fa fa-sitemap lernhive-course-index__heading-icon">` + `<span>` text, rendered as `display: flex; gap` for clean icon/text alignment.
  - `scss/lernhive/_navigation.scss` — full rewrite of the course-index styling to drop all Boost light-palette chrome:
    - `.lernhive-course-index__heading` uses `$lh-font-size-sm`, weight 600, `rgba(#fff, 0.78)`, no `text-transform: uppercase`. `.lernhive-course-index__heading-icon` is `$lh-accent` at `0.95em`, opacity 0.9.
    - `.lernhive-course-index__body` resets `background / border / box-shadow / padding / margin` on all nested `.courseindex*` elements to transparent/0. Section content indents 1.5 rem. `.courseindex-chevron` / `.icons-collapse-expand` become a 1.25 rem inline-flex box with no border/background/padding, icon at 0.75 rem — matches the LernHive accordion aesthetic instead of Boost's rectangular button.
    - Section title link: `rgba(#fff, 0.9)`, weight 600, hover `background: rgba(#fff, 0.08)`. Activity link: `rgba(#fff, 0.72)`, weight 400.
    - `.courseindex-item.pageitem`, `.courseindex-item.active`, `.courseindex-item[aria-current="page"]` → `background: rgba($lh-accent, 0.18)` + `box-shadow: inset 2px 0 0 $lh-accent` left rail + white-bold text. Replaces Boost's `$gray-100 / $gray-300` "button box" look that bled through a dark sidebar.
    - `.current-badge { display: none !important }` — the core "current" pill is redundant because the active section already has the accent background + rail treatment.
  - Johannes review ("Passt gut"): icon, sentence case, and button fixes landed correctly.
  - Rebase note: originally prepared as 0.9.52 locally, and shipped as 0.9.52 on main. The parallel sticky-header change was then bumped to 0.9.53.
- [x] 0.9.51 — Course-page sidebar render fix: `$COURSE` global fallback for the course-index guard (2026-04-11):
  - `lib.php` — `theme_lernhive_get_course_sidebar_context()` relaxed the course-detection guard. The old guard was `if ($page->course && $page->course->id > SITEID)`; diagnostic HTML-comment instrumentation on the course page showed `courseid=none status=skipped` because `$PAGE->course` reports as SITE (id=1) on the `course` layout template even though `require_login($course)` has already run. New guard prefers `$page->course` when valid, otherwise falls back to the `$COURSE` global: `if ($page->course->id > SITEID) { $course = $page->course; } else if ($COURSE->id > SITEID) { $course = $COURSE; }`. With a valid `$course` in hand, it calls `course_get_format($course)->get_renderer($page)->course_index_drawer($format)` exactly like `theme_boost/layout/drawers.php` does via `core_course_drawer()`.
  - Root cause: Moodle only populates `$PAGE->course` when the course format's own renderer runs, which happens *after* the layout template builds its sidebar context. At that point `$COURSE` is already set by `require_login($course)` but `$PAGE->course` is still the SITE fallback. Under Moodle 5.x this divergence is specific to the `course` pagelayout — drawers and admin layouts do not see it.
  - After the guard fix the real course-index HTML renders (Johannes: "Ist da").
  - Scaffolding from 0.9.48 (HTML-comment diag probe) was dropped in the follow-up 0.9.52 commit because it is only useful while debugging the render path.
  - Rebase note: shipped as 0.9.51 on main. The parallel Context Dock bottom-right move became 0.9.50.
- [x] 0.9.55 — DevFlow docs alignment for 0.9.53 sticky page header (2026-04-11):
  - `docs/01-features.md` — "Page header redesign" feature bullet updated with the sticky top bar, explicitly listing which controls stay reachable.
  - `docs/02-user-doc.md` — R1 experience bullet list gained "top navigation stays anchored to the viewport while scrolling".
  - `docs/03-dev-doc.md` — new "Sticky positioning (since 0.9.53)" section under the Page header docs: explains the two load-bearing constraints (preserve `position: sticky` in later-loaded partials; Plugin Shell offset `.lh-plugin-header` to `top: 3rem`), lays out the full z-index ladder, and updates the SCSS files table so `_plugin-shell.scss` is listed and the `_sidepanel.scss` preservation rule is called out.
  - `docs/05-quality.md` — verification checklist entry added covering long-page scrolling, Plugin Shell page stacking, Header Dock side-panel anchoring, dropdown clipping, and sidebar-column overlap.
  - `version.php` — 0.9.54 → 0.9.55 / 2026041155. Docs-only bump so the plugin bookkeeping stays in lockstep with the docs set; no code changes in this release. Originally prepared as 0.9.54 locally, but a parallel `hide courseindex skeleton placeholder flash` 0.9.54 landed on main first — rebased.
- [x] 0.9.53 — Sticky page header (profile / settings / logout stay visible on scroll) (2026-04-11):
  - `scss/lernhive/_layout.scss` — `.lernhive-page-header` gained `position: sticky; top: 0; z-index: 30;` plus an opaque `background: $lh-bg` (was `transparent`) and a 1 px bottom shadow for visual separation from content scrolling underneath. z-index 30 sits above `.lh-plugin-header` (20) but below the fixed sidebar (100), so launcher/user dropdowns and the sidebar stacking are unaffected.
  - `scss/lernhive/_plugin-shell.scss` — `.lh-plugin-header` `top: 0` → `top: 3rem`, so on Plugin Shell pages (where the page-header hides its `__main` and only the ~48 px action-icon row shows) the two sticky headers stack cleanly instead of overlapping.
  - `scss/lernhive/_sidepanel.scss` — the pre-existing `.lernhive-page-header { position: relative; z-index: 1092 }` override would have killed stickiness by re-setting position. Switched to `position: sticky; top: 0;` while keeping `z-index: 1092` so the header still sits above the side-panel backdrop (1090) and the panel itself (1091) when a Header Dock panel is open.
  - `templates/sidepanel.mustache` JS still measures `.lernhive-page-header` via `getBoundingClientRect().bottom`, which naturally reflects the stuck position, so Header Dock panels keep sitting flush under the nav bar when opened mid-scroll.
  - Motivation: Johannes reported that scrolling long pages pushed the profile / settings / logout icons off-screen, forcing a scroll back to the top for common actions. Sticking the whole top bar is the lightest fix and keeps the design language consistent with `.lh-plugin-header` which has been sticky since 0.9.40.
  - Rebase note: initially bumped to 0.9.52 locally, but the course-sidebar-polish 0.9.52 shipped to main in parallel, so this change ended up as 0.9.53.
- [x] 0.9.50 — Context Dock moved from sidebar column to bottom-right viewport corner (2026-04-11):
  - `scss/lernhive/_dock.scss` — desktop rule switched from `left: 0; width: $lh-sidebar-width; justify-content: center` to `right: 1.5rem; bottom: 1.5rem; left: auto; width: auto; max-width: calc(100vw - 3rem); justify-content: flex-end`. Dock now hugs its contents and grows leftward as items are added, with a consistent 1.5 rem breathing distance from the viewport's right edge. The `max-width` guard covers narrow desktop windows before the mobile breakpoint takes over.
  - Mobile `@media` branch re-asserts `justify-content: center` explicitly so the desktop flex-end rule does not leak into the full-width strip layout.
  - Motivation: with 6+ icons (Teacher course actions + block edit + admin shortcut) the rightmost icon visually spilled into the main content area, and anchoring the Dock to a fixed sidebar width tied an action surface to an unrelated navigation width. Bottom-right anchoring matches the conventional FAB position and scales cleanly to 5+ items.
  - ADR-03 updated with a follow-up note documenting the decision flip. `03-dev-doc.md` gained a new "Positioning" paragraph in the Context Dock section.
  - Label note: the parallel docs commit `517cc52` originally labelled this entry as "0.9.51"; corrected to `0.9.50` in 0.9.56 to match the `afe1dc7` code-change commit title on main. The follow-up paragraphs in `00-master.md` (ADR-03) and `03-dev-doc.md` (Context Dock Positioning) were also corrected to say "since 0.9.50".
- [x] 0.9.42 — Plugin Shell injected into core Moodle pages + theme_boost/loader bootstrap (2026-04-11):
  - `templates/drawers.mustache` + `templates/admin.mustache` — trailing `{{#js}} require(['theme_boost/loader'], …) {{/js}}` block added so `Aria.tabElementFix()`, Bootstrap Tab/Collapse/Dropdown/Tooltip/Popover keyboard nav, and tab-remembering all register on LernHive pages. Boost's upstream `drawers.mustache` has this same block; we were missing it entirely, which is why `/admin/search.php` secondary-nav tabs weren't switching panels despite the correct markup from 0.9.34/0.9.36.
  - **Main course of 0.9.42 — Plugin Shell on core pages:**
  - **Problem.** `local_lernhive_contenthub` has the polished Plugin Shell treatment — Zone A sticky header with `name | tagline + subtitle + tag pills`, Zone B info bar, `.lh-plugin-content-area` gutter wrap — and feels like a dedicated LernHive surface. But the core Moodle pages the user lands on every day (`/my/`, `/my/courses.php`, `/user/profile.php`, `/user/preferences.php`) render with the generic `full_header` block and look jarringly different. Johannes asked to transfer the ContentHub UX 1:1 to these pages without editing core.
  - `templates/plugin_shell_header.mustache` — **new** shared partial that renders Zone A (`.lh-plugin-header` with `__title-block → __name · __sep · __tagline → __subtitle → __tags`) and Zone B (`.lh-plugin-infobar` with `__stats → __stat + fa-info-circle`) from a `{name, tagline, subtitle, hint, tags[], hastags}` context. Markup mirrors `local_lernhive_contenthub/templates/hub_page.mustache` one-to-one so both call sites stay visually identical. Consumed by `drawers.mustache`, `admin.mustache`, and by local plugins that want to ride the same chrome (see `local_lernhive_onboarding/tours.php` below).
  - `lib.php` — **new** `theme_lernhive_get_plugin_shell_context(\moodle_page $page): ?array`. Central dispatcher: checks `$page->pagetype` against a whitelist (`my-index → dashboard`, `my-courses → mycourses`, `user-profile → profile`, `user-preferences → preferences`), returns the shell context (name/tagline/subtitle/hint + one `lh-plugin-tag--type` pill with matching FA icon) or `null`. Local plugins that already render their own shell inside `main_content` are never on the whitelist, so they're excluded by construction — no risk of double-header rendering.
  - `layout/drawers.php` + `layout/admin.php` — both call the helper and forward `pluginshell` + `haspluginshell` into the template context alongside the existing `blockregions` data.
  - `templates/drawers.mustache` + `templates/admin.mustache` — inside `#region-main`, wrap `output.main_content` in `{{#haspluginshell}} … {{/haspluginshell}}`: render the shared partial, then open `<div class="lh-plugin-content-area">` before `course_content_header`, close it after `course_content_footer`. The content-area wrap is load-bearing: the existing `.lernhive-main:has(.lh-plugin-header) { padding: 0 }` and `.lernhive-page-content:has(.lh-plugin-header) { padding: 0 }` rules from 0.9.27 strip all outer gutters when a plugin shell is detected, so without the wrap the core page content would render flush to the viewport edges. The `lh-plugin-content-area` partial (0.9.35) re-adds the responsive 24/32/16/8 px gutter. Same trick ContentHub uses.
  - `lang/{en,de}/theme_lernhive.php` — 20 new strings: `shell_name_{dashboard,mycourses,profile,preferences}`, `shell_tagline_*`, `shell_subtitle_*`, `shell_hint_*`, plus four `shell_tag_{overview,courses,account,settings}` tag labels. German follows the Trainer/in wording conventions.
  - **Zero CSS changes.** All three `:has(.lh-plugin-header)` selectors already ship from 0.9.27–0.9.35. Because `:has()` bubbles up through the DOM, the rules match whether the shell header is rendered by a local plugin inside `main_content` (ContentHub) or injected by the theme around `main_content` (core pages) — the selectors don't care about the origin.
  - `local_lernhive_onboarding/tours.php` — paired update: replaces the hand-rolled `.lh-tours-header` block (h2 + intro + badge) with a `$shell` context (`shell_name`/`shell_tagline`/`shell_subtitle`/`shell_hint` strings + one `lh-plugin-tag--level` pill using the existing `tours_level_badge` string) that's rendered via the shared partial at the top of `tour_overview.mustache`. Template body is then wrapped in `.lh-plugin-content-area` to match drawers.mustache. Bumped to 0.2.2 (`local_lernhive_onboarding/version.php`: `2026041202`).
  - Net: the five target pages (`/my/`, `/my/courses.php`, `/user/profile.php`, `/user/preferences.php`, `/local/lernhive_onboarding/tours.php`) now share the same sticky-header + info-bar + content-gutter chrome as ContentHub, and any future core page can be added by dropping a line into the whitelist map. Version bump: `2026041141 → 2026041142` / `0.9.41 → 0.9.42`. `local_lernhive_onboarding` bumped in lockstep: `2026041202 → 2026041203` / `0.2.2 → 0.2.3`.
- [x] 0.9.3 — Six fixed block regions (content-top, content-bottom, sidebar-bottom, footer-left/center/right); remove side-pre drawer
- [x] 0.9.9 — Fix login form invisible (#maincontent height:1px trap); add lernhive-skip-anchor
- [x] 0.9.18 — drawers.mustache admin navigation: add Site Admin link to sidebar for siteadmin users
- [x] 0.9.19 — Fix #maincontent in drawers.mustache: move skip-anchor out of content section wrapper
- [x] 0.9.20 — Route 'admin' layout to admin.php (standard Moodle admin nav tree); no block regions on admin pages (ADR-04)
- [x] 0.9.21 — Context Dock: floating action strip for Teacher/Trainer (course edit mode, participants, gradebook, course settings) + Admin (site admin shortcut); CSS tooltips with progressive disclosure (ADR-03)
- [x] 0.9.22 — Context Dock: add block editing toggle (all pages where user_can_edit_blocks); lang strings dockblockson/dockblocksoff; icon: fa-pen-to-square
- [x] 0.9.23–0.9.25 — Admin layout: restore sidebar on admin pages; fix chrome-hiding CSS scope to cover admin pages; add output.secondary_nav to admin template
- [x] 0.9.26 — Page header redesign (ADR-05):
  - Launcher moved from sidebar to page header top-right; dropdown right-aligned, dark-on-white colors
  - Profile avatar → direct link to own profile page; chevron dropdown for preferences / logout
  - Language selector (globe icon + `lang_menu()`) between notifications and Launcher
  - Admin settings top-nav: horizontal tab-bar from `admin_get_root()` above main content on all admin pages
  - New lib.php helpers: `theme_lernhive_get_header_user_context()`, `theme_lernhive_get_admin_topnav()`
  - Sidebar is now purely navigational (launcher removed)
- [x] 0.9.27 — UX cleanup + Plugin Shell foundation:
  - User block: `<details>` dropdown replaced by gear (Preferences) + sign-out (Logout) icon buttons — drawers.mustache + admin.mustache + _navigation.scss
  - Admin nav: two-level horizontal tab-bar — Level 1 top categories + Level 2 sub-items of active category — lib.php + admin.mustache + _layout.scss
  - Plugin Shell: new `_plugin-shell.scss` partial — 2-zone sticky page header for local plugins (Zone A header+tags, Zone B info/CTA, card grid with states, action buttons)
  - Mockup: `plugin-shell-concept.html` v0.2 (inline SVG sprite) + `plugin-shell-spec.md`
- [x] 0.9.39 — Side Panel refinements: transparent backdrop, straight top-left corner, uniform width (2026-04-11, commit d4b339e):
  - `scss/lernhive/_sidepanel.scss` — `.lh-sidepanel-backdrop` loses its grey `rgba(#0e1d2c, 0.28)` wash and becomes `background: transparent`. The element still exists (pointer-events auto when `--open`) so outside-clicks still close the panel, but the page content behind the panel — course context, admin screens, breadcrumbs — stays fully legible. The `opacity` transition + `.lh-sidepanel-backdrop--open { opacity: 1 }` rule are gone; only `pointer-events` toggles now.
  - `scss/lernhive/_sidepanel.scss` — `.lh-sidepanel` `border-top-left-radius: 0.75rem` and the companion `overflow: hidden` are removed. The meeting line with the page header is now a clean horizontal seam; the softened corner read like a layout mistake against Moodle's flat content area.
  - `scss/lernhive/_sidepanel.scss` — unified width for all four panels. `.lh-sidepanel` width `380px → 420px`; `.lh-sidepanel--size-m` (480 px) and `--size-l` (640 px) variants are deleted, as are their mobile `width: 100%` overrides. Switching between Messages / Notifications / AI / Help no longer re-flows the right edge.
  - `scss/lernhive/_sidepanel.scss` — `transition: transform 0.25s ease, width 0.25s ease` simplifies to `transition: transform 0.25s ease` since width never animates now.
  - `lib.php` — `theme_lernhive_get_sidepanel_items()`: `'size' => 'm'` replaced with `'size' => ''` on the Messages and AI Assistant entries so they pick up the default unified width.
- [x] 0.9.38 — Side Panel sits below the page header so the Header Dock stays visible (2026-04-11, commit d186af9):
  - **Problem.** 0.9.37 shipped the Header Dock + Side Panel scaffolding with panel + backdrop at `top: 0`; as soon as any panel opened it covered the whole page header including the dock itself, so swapping between Messages / Notifications / AI / Help meant closing the open panel first — breaking the "icons always visible" promise from the design review.
  - `scss/lernhive/_sidepanel.scss` — new CSS custom property `--lh-sidepanel-top` drives the top offset for both `.lh-sidepanel` and `.lh-sidepanel-backdrop`. Mobile `@media (max-width: 720px)` resets `.lh-sidepanel-backdrop { top: 0 }` so the bottom-sheet still covers the full viewport (no dock-preservation on narrow viewports — no room for both).
  - `scss/lernhive/_sidepanel.scss` — new stacking-context rule: `.lernhive-page-header { position: relative; z-index: 1092 }` lifts the page header above the backdrop (1090) and the panel (1091) so dock buttons reliably receive clicks while a panel is open. Added a 1 px top border on the panel so the meeting line with the header reads as an intentional dock seam.
  - `templates/sidepanel.mustache` — new `syncHeaderOffset()` IIFE function: `document.querySelector('.lernhive-page-header').getBoundingClientRect().bottom` is rounded to an integer px value and written to `:root` via `style.setProperty('--lh-sidepanel-top', …)`. Called on every `openPanel()` and on `window.resize` while a panel is open, so orientation changes / zoom / responsive breakpoints all stay accurate. Header height can change per page (h1 vs breadcrumb), so a hard-coded top value would break.
  - Net: the Header Dock icons remain visible AND clickable for the entire duration of a panel session, and the user can cycle through Messages → Notifications → AI → Help in-place without ever closing the panel — matching the "(1) Icons immer sichtbar" requirement from the 2026-04-11 design review.
- [x] 0.9.37 (addendum, undocumented at ship) — Header Dock + Side Panel scaffolding (commit beeb3d6):
  - New `scss/lernhive/_sidepanel.scss` partial (loaded from `theme_lernhive_get_extra_scss()` after `_icons.scss`): `.lh-hdock` always-visible pill container with four icon buttons in the page header top-right, `.lh-sidepanel` right-anchored drawer with shared Header / Body / Footer skeleton, `.lh-sidepanel-backdrop`, `.lh-sidepanel__empty` empty-state, `.lh-sidepanel__linklist` for the Help panel's curated links, `::before` drag-handle hint in the mobile bottom-sheet breakpoint. A11y rules for `[aria-expanded="true"]` (active dock button), `prefers-reduced-motion`, focus-visible outlines.
  - New `templates/sidepanel.mustache` partial: dock `<div class="lh-hdock" role="toolbar">` with four `data-lh-sidepanel-trigger` buttons, single `<aside class="lh-sidepanel" role="dialog" aria-modal>` container, hidden `<template>` payload blocks per panel keyed by `data-lh-sidepanel-payload`. Inline vanilla IIFE handles `openPanel()` / `closePanel()`, focus trap via Tab keydown, ESC handler, backdrop click, and restoring focus to the trigger button on close. Partial is rendered via `{{#hassidepanel}}{{> theme_lernhive/sidepanel }}{{/hassidepanel}}` in `drawers.mustache`, `admin.mustache`, and `course.mustache`, replacing the previous `{{{ output.navbar_plugin_output }}}` slot.
  - `lib.php` — new `theme_lernhive_get_sidepanel_items(): array` returns four panel definitions (messages / notifications / aiassistant / help) with inline Lucide-style SVG icons, URLs, title/subtitle/empty-text strings, and the Help panel's curated three-item link list. Returns `[]` for guests + logged-out users so the dock only appears post-login. Layouts (`layout/drawers.php`, `admin.php`, `course.php`) forward the array as `sidepanelitems` + `hassidepanel` to the template.
  - `lang/{en,de}/theme_lernhive.php` — 22 new strings for dock labels, panel titles, subtitles, empty-state copy, and Help-panel link titles/descriptions. German wording follows the Trainer/in convention from the wording-conventions memory.
  - `mockups/sidepanel-concept.html` — clickable 645-line prototype produced before the implementation pass, shows the four panels with their shared skeleton and the top-right dock position.
- [x] 0.9.37 — Page-header alignment + avatar redesign + CTA strip icon (2026-04-11):
  - `scss/lernhive/_layout.scss` — `.lernhive-page-header`: `align-items: center → flex-start` so action icons pin to the top of the header rather than floating vertically centred when `__main` is tall (page title + breadcrumb); top padding `$lh-spacing-sm (8px) → $lh-spacing-xs (4px)`.  `__actions` gets `padding-top: $lh-spacing-xs` for a consistent 4 px visual buffer.
  - `scss/lernhive/_layout.scss` — New `.lernhive-user-block` CSS block (replaces the now-dead `.usermenu` rules). Targets the template's actual class structure (`__avatar` link + `__action` icon buttons):
    - `__avatar`: 36 × 36 px, `border-radius: 50%`, circular border-color hover (was rectangular `$lh-radius-sm` hover from generic `.nav-link` rule)
    - `.userinitials` (Moodle 5.x no-photo output): `font-size: 0 !important` hides "AU" initials text; `::before` draws a Lucide "user" SVG via CSS mask-image in `$lh-primary` on `$lh-primary-light` background
    - `__action` (settings, logout): 36 × 36 px, `$lh-radius-sm` hover; `__action--danger` gets red hover
  - `scss/lernhive/_dashboard.scss` — `.lh-cta-strip__icon`: changed from solid dark primary tile (`border-radius: 0; background: $lh-primary; color: #fff`) to `.lh-icon-artifact`-style rounded square (`border-radius: 9px; background: $lh-primary-light; color: $lh-primary`). Warning/success variants updated to match.
- [x] 0.9.36 — Admin tab bar visibility fix (Johannes: Screenshot Boost-Referenz `/admin/search.php` zeigt `Allgemein | Nutzer/innen [active] | Kurse | …`, LernHive produziert das HTML aber rendert nichts):
  - `scss/lernhive/_base.scss` — remove the too-broad `nav.moremenu { display: none !important }` selector. Root cause: Boost's `core/moremenu` partial renders BOTH the primary navbar and the secondary admin tab bar as `<nav class="moremenu navigation">`, so the broad hide that was meant to suppress the primary navbar also killed the admin tab bar that 0.9.34 builds via `{{> core/moremenu }}`. The `.secondary-navigation { display: block }` override from 0.9.26 only un-hid the wrapper div; the `<nav>` element inside stayed `display: none !important`. Scoped the hide rule to `.primary-navigation` only, which cleanly suppresses the Boost primary navbar wrapper without affecting the `.secondary-navigation` wrapper used by the admin tab bar.
  - Net: admin tab bar now actually renders on screen — canonical Boost 9-tab sequence (General | Users | Courses | Grades | Plugins | Appearance | Server | Reports | Development) is visible as originally intended in 0.9.34.
- [x] 0.9.35 — Plugin shell card grid + ContentHub refinements (Johannes, commit 74a76a8):
  - `scss/lernhive/_plugin-shell.scss` — `.lh-plugin-grid` gap 8→16px; new `.lh-plugin-content-area` wrapper with responsive horizontal gutter (24/32px desktop, 16px tablet, 8px mobile)
  - `local_lernhive_contenthub/templates/hub_page.mustache` — remove redundant ← Dashboard back button, switch grid from `--cols-2` to `--cols-3`, replace `container-fluid py-4` with `lh-plugin-content-area` for consistency across plugin shell pages
- [x] 0.9.34 — Admin nav: delegate to core secondary_navigation (Johannes: "orientiere Dich komplett an der Boost Darstellung"):
  - `layout/admin.php` — build `secondarymoremenu` via `\core\navigation\output\more_menu($PAGE->secondarynav, 'nav-tabs', …)` exactly like `theme_boost/layout/drawers.php`. Also forwards `overflow` via `$PAGE->secondarynav->get_overflow_menu_data()`.
  - `templates/admin.mustache` — replaces the custom two-level `{{#hasadmintopnav}}…{{/hasadmintopnav}}` / `{{#hasadminsecondnav}}…{{/hasadminsecondnav}}` blocks with the canonical Boost pattern: `{{#secondarymoremenu}} … {{> core/moremenu}} {{/secondarymoremenu}}` wrapped in a thin `.lernhive-secondary-navigation` container. Also renders the `tertiary-navigation / url_select` overflow block when present.
  - `lib.php` — `theme_lernhive_get_admin_topnav()` function deleted (no longer called; the custom `admin_get_root()` walk produced an L1/L2-mixed tab list on `/admin/index.php`).
  - `scss/lernhive/_layout.scss` — `.lernhive-admin-topnav` + `.lernhive-admin-topnav--secondary` rules removed; replaced with a thin `.lernhive-secondary-navigation` wrapper that only handles horizontal gutter + bottom margin so the core `nav.nav-tabs` sits flush with the flush-layout shell from 0.9.33.
  - Net: admins now see the exact same canonical tab bar as Boost (General | Users | Courses | Grades | Plugins | Appearance | Server | Reports | Development), including Moodle core's own overflow-menu handling.
- [x] 0.9.33 — Layout shell flush edges (Johannes: "vollflächige Hintergründe, nur Kacheln als Abgrenzung"):
  - `.lernhive-page` — `padding: 0`, `gap: 0` (was `$lh-spacing-lg` + `$lh-spacing-md`); page column is now a flush full-width canvas
  - `.lernhive-page-header` — tile chrome dropped: `background: transparent`, `border: none`, `border-radius: 0`, `box-shadow: none`; pulled flush to viewport top + flush to sidebar right edge; internal padding preserved so icon cluster keeps breathing room
  - `.lernhive-page-content` — horizontal + bottom gutter added locally (`padding: 0 $lh-spacing-lg $lh-spacing-lg`) so regular Moodle pages still have breathing room around `.lernhive-main`; plugin-shell pages override this to `padding: 0` via `:has(.lh-plugin-header)` so Zone A sits edge-to-edge under the top header bar
  - Small-screen responsive block rewired: `<375px` tightens `.lernhive-page-content` horizontal padding instead of the (now zero-padding) `.lernhive-page`
  - No changes to `.lh-plugin-header` / `.lh-plugin-infobar` — the two strips (title + message) remain visually distinct as designed
  - Applies to both `drawers.mustache` and `admin.mustache` because they share the same class hierarchy

## Open — R1 scope

- [ ] **Smoke-test 0.9.39** — Header Dock + Side Panel, on `dev.lernhive.de` (logged in):
  - Four dock icons (message / bell / sparkle / help) appear in the page header top-right, in a rounded-pill container
  - Clicking any dock icon opens the right-anchored panel at a uniform 420 px width; the panel sits BELOW the page header with its top edge flush against the header's bottom border
  - The page header + dock icons remain visible AND clickable while the panel is open — click a second dock icon and the panel swaps in-place without closing first
  - The backdrop is transparent: course navigation, breadcrumbs, and admin content behind the panel stay fully legible; clicking anywhere outside the panel still closes it
  - Top-left corner of the panel is a clean 90° angle (no border-radius)
  - ESC closes the panel; focus returns to the dock button that opened it
  - Help panel shows three curated links (Getting started, Your dashboard, User preferences) with hover state
  - Messages / Notifications panels show an empty state with a CTA button that links to `/message/index.php` and `/message/output/popup/notifications.php` respectively
  - AI Assistant panel shows its "coming soon" empty state with the dot badge on the dock button
  - On < 720 px viewport: panel becomes a bottom-sheet with drag-handle hint; backdrop covers the full viewport (no dock preservation on narrow screens)
  - `prefers-reduced-motion` respected: no transition on panel open
- [ ] **Smoke-test 0.9.37** — on `dev.lernhive.de`:
  - Nav icons (notifications, launcher, avatar) sit at the very top of the content column — no large top gap
  - Avatar with no uploaded photo shows a person icon (not "AU" text, not a rectangle hover)
  - CTA strip icon is a soft rounded square (not solid dark tile)
  - Admin tab bar (General | Users | Courses | …) is visible; active tab highlights; overflow renders
  - Primary navbar is still suppressed (no duplicate nav bars)
  - ContentHub renders 3-column card grid with `lh-plugin-content-area` gutter
- [ ] **Smoke-test 0.9.34/0.9.36** — superseded by the 0.9.37 smoke-test above
- [ ] **Smoke-test 0.9.33** — superseded by the 0.9.37 smoke-test above
- [x] **0.9.38 — Icon taxonomy Type 4 `.lh-icon-info`** (shipped on main, 2026-04):
  - `scss/lernhive/_icons.scss` — header comment rewritten from three-type to four-type taxonomy (Navigation / Artifact / Action / **Information**). Type 4 documents intent explicitly: "passive signal — does NOT navigate, does NOT classify content, does NOT trigger a function; communicates state, help, warning, or error".
  - `.lh-icon-info` class landed at lines 324–461: 28 × 28 px rounded square (6 px radius), `cursor: help`, NO transform/scale/lift on hover, NO box-shadow on hover — visually distinct from both `.lh-icon-action` (circle, grows) and `.lh-icon-artifact` (9 px tile, metadata). Subtle background-tint deepen on hover is the only visual feedback.
  - Semantic modifiers shipped: `--locked`, `--complete`, `--new`, `--pending` (status), `--help`, `--warning`, `--error`, `--success`, `--info` (form/alert signals); all modifiers use the existing `$lh-*-light` / accent palette.
  - Size variants shipped: `--sm` (20 × 20, borderless), `--lg` (36 × 36).
  - Template rewire (status badges, help marks in forms, alert icons) tracked separately under 0.9.65 — CSS is live, but no Mustache currently references `.lh-icon-info`.
- [ ] `regionmainsettingsmenu` must stay — Teachers use it to add blocks to courses (block positions still unclear after right-hand drawer removal). Keep until an explicit block-placement UX replaces it.
- [ ] Student dock items: progress overview shortcut, continue-learning button (post-flavour integration)
- [ ] PHPUnit @covers deprecations in non-onboarding plugins (contenthub, copy, flavour, library — 41 remaining)
- [x] ~~PHPUnit failure: `flavour_manager_test::test_apply_on_fresh_site_does_not_flag_overrides`~~ — fixed in `local_lernhive_flavour` 0.2.1: `has_pending_overrides()` now requires `$current !== null` to treat a diff row as an override, matching `detect_overrides()`. Last full PHPUnit run (2026-04-16, sha ded40269) green with 14 tests / 43 assertions.
- [x] ~~Behat init_failed on Hetzner~~ — fixed in `playbooks/test.sh` (Session 1, 2026-04-16): first-time init now calls plain `init.php` (no `--add-core-features-to-theme`) when `diag_rc != 0`, matching the working PHPUnit path. Second-pass re-syncs keep the flag. Runbook entry updated in `playbooks/testing-hetzner.md`.

## Deferred — post-R1

- [ ] Manager dock items (course management, user enrolment, reporting shortcuts)
- [ ] Dock progressive disclosure: migrate from localStorage to Moodle user preference (M.util.set_user_preference) for persistence across devices
- [ ] format_lernhive_snack: move snack_*.mustache out of theme (ADR-01, target 0.10.0)
- [ ] format_lernhive_community: community feed rendering (ADR-01, target 0.11.0)
- [ ] Decide whether format_lernhive_classic is needed or if format_topics suffices

## Design System Consolidation — 2026-04-15 (Abend-Session)

### Abgeschlossen in dieser Session

- [x] **Design System Reference** (`mockups/design-system-reference.html`) — kanonisches Referenzdokument mit allen Tokens, Icon-Taxonomie (4 Typen), Buttons, Tags, CTA Strip, Plugin Shell (Zone A+B), Cards, App Shell 5-Ebenen-Diagramm. Dient als einzige Wahrheitsquelle für alle künftigen Implementierungen.
- [x] **Design Vocabulary + Icon Matrix** aktualisiert — `docs/design-vocabulary.md` + `mockups/icon-matrix.md` spiegeln die finalisierten Regeln.
- [x] **Course header action parity (core-preserving)** — `layout/course.php` + `templates/course.mustache` nutzen jetzt wie Boost die `secondarymoremenu`/`overflow`-Pipeline (`core/moremenu` + `core/url_select`) im Zone-B-Aktionsbereich. Dadurch bleiben Course-Reuse-Aktionen (Import / Backup / Restore / Copy / Reset) capability-gesteuert sichtbar; zusätzlich rendert das `course`/`incourse`-Layout wieder `core/activity_header` und `output.activity_navigation`.
- [x] **Neue Mockups** erstellt:
  - `mockups/dashboard.html` — Dashboard ohne Plugin Shell (Zone 0), Launcher-Panel, CTA Strip, Today/My Courses/Recommended
  - `mockups/course-page.html` — voller Plugin Shell (Zone A+B), Kursnavigation links in Sidebar
  - `mockups/explore.html` — LXP Discover-Surface, Zone A+B mit Filter-Bar

### Finalisierte Design-Entscheidungen (in dieser Session bestätigt)

| Entscheidung | Regel | Konsequenz |
|---|---|---|
| Icon Typ 1 Navigation | Immer transparenter Hintergrund — auch im aktiven Zustand. Aktiv = nur Farbwechsel | `.lh-icon-nav--active { background: transparent }` |
| Icon Typ 2 Artifact | Immer sichtbarer farbiger Kasten — muss zum Kontext kontrastieren | Kein `background: transparent` erlaubt |
| Icon Typ 3 Action | Immer sichtbarer Vollkreis — vor Hover, hell: `rgba(primary,.08)`, dunkel: `rgba(white,.12)` | Kein `background: transparent` als Default |
| Icon Typ 4 Information | Immer sichtbares abgerundetes Rechteck (6px) — `cursor: help`, kein grow | Trennt sich von Artifact (Quadrat) und Action (Kreis) |
| Buttons | Alle Buttons `border-radius: 8px`. **Pill ausschließlich für Tags** | `.lh-btn-start`, `.lh-btn-open`, `.lh-btn-ghost` — alle 8px |
| Back-Button Zone A | Zeigt **konkreten Eltern-Kontext** — nie "← Dashboard" (schon in Sidebar-Nav) | Content-spezifisches Label zwingend |
| App Shell Topbar | Launcher-Trigger = **oranger Vollkreis** neben LernHive-Logo in Sidebar. Klick öffnet Panel in linker Topbar-Hälfte | `lh-sidebar__launcher { background: var(--lh-accent); border-radius: 50% }` |

## Open — R1 scope (nach Design-Session ergänzt)

- [ ] **0.9.65 — Icon-Taxonomie implementieren** (`_icons.scss` + alle Templates):
  - **Typ 1 Navigation** — `.lh-icon-nav--active`: `background: transparent !important` — nur Farbwechsel, nie Quadrat/Kreis
  - **Typ 3 Action** — `.lh-icon-action`: Default `background: rgba($lh-primary, .08)` (nicht `transparent`). `.lh-icon-action--on-dark`: Default `background: rgba(#fff, .12)`. Gilt für alle Vorkommen: User Block, Context Dock, Topbar Actions, Card Buttons (Info-Icon)
  - **Typ 4 Information** — `.lh-icon-info` neu einführen: 28px, 6px radius, `cursor: help`, immer sichtbarer Kasten, kein scale/shadow beim Hover. Modifiers: `--help`, `--warning`, `--success`, `--error`, `--locked`, `--new`, `--pending`
  - Referenz: `mockups/design-system-reference.html` Sektion 3

- [ ] **0.9.66 — Button-System vereinheitlichen** (`_buttons.scss` + alle Templates):
  - Standalone-Buttons (`.lh-btn`): `border-radius: 8px` statt `$lh-radius-pill`
  - CTA Strip Button (`.lh-cta-strip__cta`): `border-radius: 8px`
  - Zone B CTA (`.lh-plugin-infobar__cta`): `border-radius: 8px`
  - Neue Klassen einführen: `.lh-btn-start` (orange, 8px), `.lh-btn-open` (navy, 8px), `.lh-btn-ghost` (outline, 8px), `.lh-btn-action` (Kreis, Typ-3-Icon in Cards)
  - Tags (`.lh-plugin-tag`): behalten `border-radius: $lh-radius-pill` — **einzige Pills im System**
  - Referenz: `mockups/design-system-reference.html` Sektion 5

- [ ] **0.9.67 — App Shell Header refaktorieren** (`_layout.scss`, `drawers.mustache`):
  - **Sidebar Brand Row**: LernHive-Wordmark links, Launcher-Trigger rechts als oranger Vollkreis (`background: $lh-accent; border-radius: 50%`). Kein Icon-Kasten (Quadrat), kein weiß-transparentes Pseudo-Quadrat.
  - **Sidebar Nav Items**: Icon in `.lh-nav-icon` Wrapper (24px, border-radius 6px, rgba-weißer Tint). Aktives Item: Row-Highlight `rgba(#fff, .10)` + Icon-Box `rgba($lh-accent, .22)`. Text weiß. Kein Border/Rahmen.
  - **Topbar** (48px): Linke Hälfte = Launcher-Panel (`.lh-launcher-icon` als Vollkreis-Buttons, `rgba(primary,.07)`). Rechte Hälfte = Action Icons (Bell, Avatar, Settings, Logout). Border-Right zwischen beiden Hälften als Trenner.
  - **Zone 0**: `background: $lh-bg`, Breadcrumb links, h1, Page-Action-Icons rechts. Auf Plugin-Shell-Seiten ausgeblendet (`:has(.lh-plugin-header)`).
  - Referenz: `mockups/design-system-reference.html` Sektion 0 + alle drei neuen Mockups

- [ ] **0.9.68 — Dashboard-Content-Muster** (`_dashboard.scss`, `drawers.mustache`/Dashboard-Block):
  - Abschnitte: "Today" (fällige + laufende Items), "My Courses" (Fortschrittskarten), "Recommended" (Snacks)
  - Section-Header-Muster: Icon (Typ 1 Nav, inline 16px) + Titel + optionaler "View all →"-Link
  - Card-Grid: `display: grid; gap: 14px` — 2-spaltig für Today, 3-spaltig für My Courses
  - Referenz: `mockups/dashboard.html`

- [ ] **0.9.69 — Course-Page-Sidebar** (`sidebar_course.mustache`, `_navigation.scss`):
  - Kursnavigation kommt in linke Sidebar: nach `<hr class="lernhive-nav__divider">`, Section-Label "Course Navigation", Section-Items mit Typ-2-Artifact Icons (check=abgeschlossen, play=aktiv, lock=gesperrt)
  - Kein rechtes Spalten-Layout mehr — Content-Bereich ist einspaltig
  - Referenz: `mockups/course-page.html`

- [ ] **0.9.70 — Explore-Surface** (`explore_shell.mustache`, `_layout.scss`):
  - Zone A ohne Back-Button (Explore ist Toplevel in der Sidebar-Nav)
  - Zone B mit Filter-Bar (aktive Filter als Ghost-Buttons mit ×, Search, Sort)
  - Grid-Inhalt: Featured (full-width Hero-Card) + Results (3-spaltig, gemischte Typen)
  - Referenz: `mockups/explore.html`
