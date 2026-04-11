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
