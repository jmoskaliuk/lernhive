# theme_lernhive — Features

## Feature summary
LernHive visual layer and design system for a calm, guided Moodle experience.

## Shipped features (current)
- design tokens (color, typography, spacing, surfaces) — shipped 0.9.x
- responsive left-oriented navigation (sidebar, horizontal nav on mobile) — shipped 0.9.x
- six fixed block regions replacing the right-hand drawer — shipped 0.9.3
- Launcher compact flyout (context-dependent quick-action grid) — shipped 0.9.x
- Context Dock (floating action strip for Teacher/Trainer + Admin, fixed position) — shipped 0.9.21
  - Course edit mode toggle (course pages, teacher role)
  - Block editing toggle (all pages where user can edit blocks, 0.9.22)
  - Participants / Gradebook / Course settings (course pages, teacher role)
  - Site admin shortcut (non-admin pages, siteadmin role)
  - CSS-only tooltips with progressive disclosure after 3 page visits
  - Desktop: vertical pill at bottom of sidebar; Mobile: horizontal strip at bottom of screen
- Admin layout using Moodle standard secondary navigation (admin.php, 0.9.20)
- Admin settings top navigation (0.9.34, visibility fix 0.9.36): horizontal tab-bar delegating to Moodle's `core\navigation\output\more_menu` via `$PAGE->secondarynav` — produces the canonical 9-tab sequence (General | Users | Courses | Grades | Plugins | Appearance | Server | Reports | Development) with core overflow handling, replacing the hidden Boost left-drawer admin settings tree. (Superseded the 0.9.26 custom `admin_get_root()` walker, which produced a mixed L1/L2 tab list on `/admin/index.php` — see ADR-06 in `00-master.md`.)
- Page header redesign (0.9.26, user-block restyled 0.9.27 + 0.9.37, sticky since 0.9.53):
  - Launcher (9-dot grid icon) moved from sidebar to page header top-right — sidebar stays purely navigational
  - Profile avatar is a direct, circular link to the user's own profile page (no dropdown required for the most common action)
  - Two explicit icon buttons next to the avatar: gear → preferences, sign-out → logout (red hover). No dropdown — each action is a direct link. Replaced the short-lived 0.9.26 `<details>/<summary>` chevron dropdown in 0.9.27.
  - Language selector (globe icon + `output.lang_menu()`) between notifications and Launcher; hidden when only one language is installed
  - Sticky top bar (0.9.53): the whole `.lernhive-page-header` row sticks to the viewport top while scrolling, so profile / settings / logout / notifications / launcher / language picker remain one click away on long pages
- Course-page sidebar (0.9.45, render-guard fix 0.9.51, visual polish 0.9.52, placeholder-flash fix 0.9.54):
  - On the `course` pagelayout the theme swaps `sidebar.mustache` for `sidebar_course.mustache`: a reduced primary nav (Dashboard / My Courses / Explore only), a `<hr>` divider with `role="separator"`, then a dedicated course-navigation region that renders the core Moodle course index (sections + activities) via `course_get_format($course)->get_renderer($page)->course_index_drawer($format)`
  - The course-index chrome is re-skinned for the LernHive dark palette: no `text-transform: uppercase` on the "Course navigation" heading, `fa-sitemap` icon prefix on the heading, all `.courseindex*` backgrounds / borders / shadows reset to transparent inside `.lernhive-course-index__body`, active section gets a soft `$lh-accent` tint + a `2 px` inset left rail, chevrons become borderless `1.25 rem` inline-flex boxes, and Boost's "current" pill (`.current-badge`) is hidden because the accent treatment already signals the active item
  - No more skeleton flash: the core placeholder loader (`#course-index-placeholder` / `.placeholders` — four grey pulse rows shipped in `core_courseformat/local/courseindex/placeholders.mustache`) is `display: none !important` inside the sidebar, so the ~50–300 ms gap between first paint and the `core_courseformat/courseeditor` AMD hydration shows an empty region instead of mangled-for-light-mode grey boxes
  - Graceful fail: if AMD hydration ever fails the sidebar degrades to just the heading, never leaks back the light-palette skeleton
  - Course actions stay core-driven: Zone B renders Moodle's canonical secondary `moremenu` and the course-admin overflow `url_select` (including Course reuse actions such as Import / Backup / Restore / Copy / Reset), styled but not reimplemented
  - In-course activity UX parity: the layout renders `core/activity_header` and `output.activity_navigation` so module pages keep completion/header controls and prev/next activity navigation

## Planned feature set
- design tokens for color, typography, spacing, and surfaces
- responsive layout with a left-oriented navigation model
- clear card patterns for Explore, reporting, and content entry points
- touch-friendly UI with simple actions and readable spacing
- distinct treatment for action surfaces such as Launcher and Context Helper
- reusable Explore shell pieces for the optional LXP Flavour, without moving discovery logic into the theme
- reusable ContentHub shell pieces that keep Copy, Template, and Library visually aligned but conceptually separate
- a dedicated course page shell that keeps the main learning content central and treats helper content as secondary
- reusable short-form components for Snack-oriented headers and compact step flows without turning Snacks into full course pages

## Theme rules
- the theme does not contain business logic
- Moodle core strings are reused where suitable
- LernHive product terms stay stable and English-first
- Launcher is action-oriented, not full navigation
- Explore replaces Dashboard only in the optional LXP Flavour

## Release 1 UX scope
- left navigation is primary
- top navigation stays small and utility-focused
- cards stay calm and easy to scan
- mobile behavior stays responsive and touch-friendly
- visual hierarchy should reduce Moodle complexity rather than add more options
- Explore presentation should support the fixed Release 1 feed blocks and explainable ranking hints
- ContentHub presentation should keep orchestration separate from Copy and Library implementation details
- course pages should present course content clearly without defaulting to a noisy right-side navigation pattern

## Mockup targets
- global shell for desktop and mobile
- navigation model for School and LXP contexts
- Explore cards and feed presentation
- Launcher panel and action grouping
- ContentHub entry screen
- reporting tiles and calm dashboard treatment

## Context Dock — persona scope

| Persona | Release | Dock actions |
|---|---|---|
| Teacher/Trainer | R1 (current) | Edit mode, block editing, participants, gradebook, course settings |
| Admin | R1 (current) | Site admin shortcut |
| Student | Post-R1 | Progress overview, continue-learning shortcut |
| Manager | Later | Course management, user enrolment, reporting |

## Acceptance direction
- feature behaviour should be explicit and understandable
- strings should reuse Moodle core when possible
- UX should stay simple and mobile-friendly
- flavour-specific wording should be used only if really necessary
- visual design should make the product feel calmer and clearer than standard Moodle without forking Moodle behavior

## Release note
Target release: R1
