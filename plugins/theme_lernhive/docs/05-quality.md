# theme_lernhive — Quality

## Quality goals
- terminology is consistent
- UX stays simple
- strings are reusable and localizable
- feature works on desktop and smaller screens
- no unnecessary duplication of Moodle core logic
- navigation remains clear and predictable
- action surfaces do not blur into navigation
- the theme reduces visual complexity instead of adding new complexity

## Checks
- accessibility basics
- responsive checks
- role/permission checks where relevant
- language string review
- verify left navigation remains primary
- verify top navigation stays minimal
- verify Launcher is action-oriented rather than full navigation
- verify School and optional LXP views remain visually coherent
- verify mockup decisions can be mapped to Moodle theme implementation without hidden business logic
- verify Launcher works with keyboard and visible focus states
- verify card patterns support separate Follow and Bookmark actions without relying on color alone
- verify sidebar navigation remains readable when Moodle flat navigation output changes
- verify course pages keep content central and helper content secondary
- verify Snack-oriented constraints are not contradicted by the course page shell
- verify Snack header and step-flow components keep short-form learning visually distinct from full course presentation
- verify `/admin/index.php` renders the core secondary tab bar with the canonical 9-tab Boost sequence (General | Users | Courses | Grades | Plugins | Appearance | Server | Reports | Development) — it must be visible as a horizontal tab strip between the page header and the main content (regression check for 0.9.34 + 0.9.36, see ADR-06). A missing tab bar typically indicates a too-broad `display: none` selector targeting `nav.moremenu` — the selector must be scoped to `.primary-navigation` only, because Boost renders primary and secondary nav as the same `<nav class="moremenu navigation">` element.
- verify LernHive plugin pages that render via the Plugin Shell (ContentHub, Copy, Library, …) show **only** the Plugin Shell — no Moodle admin secondary tab bar layered on top. Plugin entry pages must not call `admin_externalpage_setup()` because it forces `pagelayout='admin'`. Plugins stay discoverable in the site-admin search by registering themselves in the admin tree via `settings.php` only.
- verify the sticky top page header (since 0.9.53):
  - on a long page (e.g. `/my/`, `/admin/search.php` with expanded content, a course page scrolled past the fold) the `.lernhive-page-header` row stays anchored to the viewport top while scrolling; profile avatar, settings icon, logout icon, notification / message icons, launcher and language picker must all remain clickable
  - on a Plugin Shell page (e.g. ContentHub, Copy) both the theme page header and `.lh-plugin-header` are sticky and must stack cleanly — no overlap, no gap with content peeking through
  - opening a Header Dock side panel (Messages / Notifications / AI / Help) while scrolled mid-page: the panel must anchor flush under the stuck header (its top edge equals `getBoundingClientRect().bottom` of the header) and the backdrop must sit below the header so dock icons stay clickable for quick panel switching
  - the launcher dropdown and any user-menu dropdown open above the main content while the header is stuck (no clipping)
  - the fixed sidebar (z-index 100) still covers the left edge — the sticky header must not paint into the sidebar column
- verify the course-page sidebar (since 0.9.45, render-guard fix 0.9.51, visual polish 0.9.52, placeholder-flash fix 0.9.54):
  - log in as a user enrolled in a course, navigate to `/course/view.php?id=<courseid>` with a `topics` or `weeks` format course
  - the sidebar shows a reduced primary nav with exactly three items — Dashboard, My Courses, Explore — followed by a horizontal divider
  - below the divider, a "Course navigation" heading appears with an `fa-sitemap` icon prefix (icon is `$lh-accent` coloured, heading text is sentence-case — **not** `COURSE NAVIGATION`)
  - below the heading, the core Moodle course index renders the course's sections and activities; collapsing/expanding a section works (chevron rotates, content shows/hides)
  - in the course header infobar, secondary navigation tabs still render via core `moremenu` (e.g. participants/settings depending on role)
  - when course-admin overflow is active (e.g. on `/backup/restore.php` with course context), the overflow selector is visible and includes Course reuse actions (Import / Backup / Restore / Copy / Reset based on capabilities)
  - clicking an activity navigates to it; the currently-active section/activity in the sidebar gets a soft `$lh-accent` background tint + a 2 px inset left rail in `$lh-accent`, text in white-bold — no grey "button box" from Boost defaults
  - no `.current-badge` pill is visible next to the active section (hidden by `display: none`)
  - chevrons on section headers are small (~`1.25 rem`), borderless, inline-flex — not Boost's rectangular button chrome
  - hard-reload the page: there must be **no visible grey skeleton flash** before the course index hydrates. If JS is disabled or breaks, the region degrades to just the heading — never to the core 4-row placeholder template
  - regression check for the `$COURSE` fallback: the diagnostic HTML comment from 0.9.48 (`<!-- lernhive-course-idx-diag: courseid=X … -->`) is no longer in the DOM; an HTML-comment diag in the shipped theme would indicate the 0.9.52 scaffolding cleanup did not land
  - inspect `getComputedStyle(...)`: `.courseindex-item.pageitem` should have `background-color: rgba(22, 163, 74, 0.18)` (or equivalent from `$lh-accent`), `box-shadow: inset 2px 0 0 …`, and `background-color` explicitly not `rgb(240, 240, 240)` (Boost `$gray-100` leakage)
  - on an in-course module page (`/mod/*/view.php`), `core/activity_header` is visible above content and `output.activity_navigation` (previous/next activity) still renders
