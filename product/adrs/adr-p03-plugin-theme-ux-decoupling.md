# ADR-P03 — Plugin / Theme UX decoupling

**Status:** Accepted
**Date:** 2026-04-16
**Deciders:** Johannes Moskaliuk

## Context

`product/01-architecture.md` lists as a core design rule: **"Plugins must work without the LernHive theme."** Over the 0.9.3–0.9.68 theme cycle and the parallel ContentHub, Copy, Onboarding, Library, and Reporting rollouts this rule got quietly broken.

Today's state:

1. Plugin Mustache templates reference shared UX classes that are defined exclusively in `theme_lernhive`:
   - `lh-plugin-header`, `lh-plugin-infobar`, `lh-plugin-grid`, `lh-plugin-card`, `lh-plugin-tag`, `lh-plugin-btn*`, `lh-plugin-content-area` (Plugin Shell)
   - `lh-btn`, `lh-btn-action`, `lh-btn-ghost`, `lh-btn-open`, `lh-btn-outline`, `lh-btn--accent` (buttons)
   - `lh-icon-action`, `lh-icon-artifact`, `lh-icon-nav`, `lh-icon-info` (icon taxonomy)
   - `lh-cta-strip*`
2. `plugins/local_lernhive_onboarding/templates/tour_overview.mustache` and `tours.php` render a theme-owned partial directly: `{{> theme_lernhive/plugin_shell_header }}`. That is a hard runtime dependency on the theme.
3. The partial itself (`theme_lernhive/templates/plugin_shell_header.mustache`) is load-bearing for the Zone A / Zone B header pattern that every plugin shell page needs.
4. Icon-level dependencies: plugin templates use LernHive-defined icon slots that rely on FontAwesome / Lucide sprites wired up in the theme.

Consequence: under Boost or Boost Union, plugin pages render as unstyled markup — grids without grid layout, headers without Zone A chrome, buttons without hover/focus, cards without card chrome. Functionally usable but visually broken.

This blocks three things we have already committed to:

- Plugins must be installable and usable under any Moodle 5.x theme (explicit R1 requirement).
- Onboarding's FR-05b deployment checkpoint and the Library R2 manifest feed push LernHive closer to "plugins that partners can install independently" — they need to render under Boost Union at customer sites where `theme_lernhive` is not active.
- The eLeDia OS DevFlow principle "function and design are separate" (`AGENTS.md` § Non-negotiable principles).

## Decision

1. **All shared Plugin Shell CSS, icon-taxonomy CSS, and button-system CSS move into `local_lernhive`.** No new umbrella plugin (`local_lernhive_uxkit` was considered and rejected — see below).
2. **Under Boost Union the rendering is completely neutral.** Plugin Shell chrome stays structurally intact — Zone A + Zone B, grid, cards, tags, buttons — but colours, accent highlights, and any LernHive brand ornamentation (oranges, navy, Lucide launcher glyph, sparkle animations) disappear. Neutral = Moodle-Bootstrap-5 greyscale.
3. **Theme-only features get explicit plugin-level fallbacks.** Any partial, macro, or helper that currently lives only in `theme_lernhive` and is referenced by plugin templates must be moved to `local_lernhive/templates/` (or equivalent) so the plugin still renders without the theme. The theme may override, but must never be the sole source.
4. **Branding moves to CSS custom properties.** `theme_lernhive` exports tokens via `:root { --lh-primary: …; --lh-accent: …; --lh-bg: …; … }`. Plugin CSS reads the tokens with neutral fallbacks, so the same selector renders neutral under Boost Union and branded under `theme_lernhive`.

## Options Considered

### Option A — Tokenised Plugin-Shell in `local_lernhive` *(chosen)*

Ship the full Plugin Shell basis styles from `local_lernhive/styles.css`. All plugin-shared classes (`lh-plugin-*`, `lh-btn-*`, `lh-icon-*`, `lh-cta-strip`) live here. Colour, radius, shadow driven by CSS custom properties with neutral fallbacks. `theme_lernhive` becomes a thin token layer plus theme-only chrome (sidebar, topbar, dock, header-dock, launcher, context dock).

| Dimension | Assessment |
|-----------|------------|
| Complexity | Medium — one migration pass, then low steady-state |
| Cost | Few days of refactor work |
| Scalability | High — every new plugin inherits a working default |
| Team familiarity | High — CSS custom properties are stable web tech |

**Pros:**
- One source of truth for the Plugin Shell structure
- Plugins usable under any Moodle 5.x theme
- Theme stays responsible for *visual identity*, not *structure*
- No cross-plugin dependency explosion (`local_lernhive` is already universal)

**Cons:**
- `local_lernhive/styles.css` becomes the largest CSS surface in the product — needs organisation (sub-files compiled into one)
- SCSS → CSS compilation step for the plugin (previously only in the theme)
- Two places to look when debugging a style: plugin styles + theme overrides

### Option B — Theme-override directories per plugin

Plugin templates stay neutral (Bootstrap 5 + Moodle-core classes). `theme_lernhive/templates/local_lernhive_contenthub/hub_page.mustache` overrides the neutral template with the LernHive-flavoured variant.

| Dimension | Assessment |
|-----------|------------|
| Complexity | High — two templates per plugin page, kept in sync |
| Cost | Ongoing — every plugin feature pays a 2× template tax |
| Scalability | Low — grows linearly with every new page |
| Team familiarity | Medium — Moodle template override is well-documented but easy to desync |

**Pros:**
- Sharpest separation between plugin (function) and theme (presentation)
- Plugins emit "pure Moodle" markup — easy to reason about under any theme

**Cons:**
- Double maintenance burden for every page change
- Drift between neutral and branded templates is inevitable (one gets a fix, the other doesn't)
- Designer must touch two files for any Zone A / Zone B polish

### Option C — Bootstrap-first plugins, theme overrides via selectors only

Plugins use only Moodle-core Bootstrap 5 classes (`.card`, `.row`, `.btn`, `.container-fluid`). `theme_lernhive` re-skins those core classes with SCSS overrides.

| Dimension | Assessment |
|-----------|------------|
| Complexity | Low per page, High at system level |
| Cost | Rewrite every plugin template |
| Scalability | High — plugins stay generic |
| Team familiarity | High — standard Bootstrap work |

**Pros:**
- Plugins look reasonable under any theme by default
- Smallest plugin CSS footprint

**Cons:**
- Loses the specific Plugin Shell vocabulary (Zone A, Zone B, tag pills, action icon strip)
- Recovering the LernHive look from generic Bootstrap classes requires theme-wide selector gymnastics
- Existing Plugin Shell design work (theme 0.9.27–0.9.68) partially discarded

### Option D — New `local_lernhive_uxkit` plugin *(considered, rejected)*

Shared UX styles live in a dedicated UX-kit plugin, referenced as a dependency by every other LernHive plugin.

**Rejected because:**
- `local_lernhive` is already a universal dependency of every LernHive plugin — adding a second universal dependency is pure ceremony
- Installation complexity grows without a payoff
- Partner customers see one more plugin to install and keep in sync

## Trade-off Analysis

Option A vs B: A picks **style-layer separation** (one template, one CSS, tokens switch the look) over B's **template-layer separation** (two templates, one per theme). A scales sub-linearly with plugin count; B scales linearly. A has been the standard pattern in web design systems since CSS Custom Properties landed.

Option A vs C: A keeps the **Plugin Shell vocabulary** that the 0.9.x theme work built up (Zone A, Zone B, `--cols-3` grid, info-action icon strip). C would throw that vocabulary out and rebuild from Bootstrap primitives. The Plugin Shell has measurable UX value — ContentHub, Copy, Onboarding, and Reporting all use the same mental model, which is the entire point of the system. Discarding it to solve a theme-independence problem is too big a tradeoff.

## Consequences

**Easier:**
- Plugins install and render sensibly under Boost, Boost Union, Classic, and partner themes
- Partner deployments that do not want `theme_lernhive` become possible without breaking plugin UX
- New LernHive plugins inherit the Plugin Shell by importing a class set, not a theme
- Visual regression testing of plugins can happen under vanilla Boost
- The eLeDia DevFlow principle "function and design are separate" is enforceable

**Harder:**
- `local_lernhive` takes on responsibility for shipping a working CSS surface — a build step + a versioned styles contract
- Token changes in the theme require matching token declarations in `local_lernhive` so the fallback path stays sane
- Plugin Shell vocabulary changes now need to ship in two places: structural rules in `local_lernhive`, visual polish in `theme_lernhive`
- Tour completion modal, Context Dock, Header Dock, Side Panel, Sidebar chrome stay theme-only — plugins that currently depend on them must degrade gracefully

**Needs revisiting:**
- If partner customers need a third-party-branded look (e.g. a corporate theme over LernHive plugins), the token layer may need to grow a public contract so third-party themes can opt into Plugin Shell styling
- If `local_lernhive/styles.css` grows past a reasonable size, we revisit Option D (UX-kit extraction)

## Non-goals

- **Not** refactoring theme-only surfaces (sidebar, topbar, Context Dock, Header Dock, Side Panel, Launcher) — those stay theme-owned because they are not plugin content.
- **Not** changing any plugin Mustache template's class vocabulary. Classes stay `lh-plugin-*` / `lh-btn-*` / `lh-icon-*`; what changes is *where the CSS for them lives*.
- **Not** introducing a new CSS framework or a CSS-in-JS approach. Plain SCSS → CSS, Moodle's `theme_lernhive`-style build chain in `local_lernhive`.
- **Not** blocking ongoing plugin feature work. The migration ships incrementally alongside plugin work.

## Action Items (Migration Path)

Phases are ordered for minimum risk — each phase is independently deployable and keeps current `theme_lernhive` users unaffected.

### Phase 1 — Token export contract *(target `local_lernhive` 0.6.0, `theme_lernhive` 0.9.70)*

1. `theme_lernhive/scss/lernhive/_tokens.scss` — add `:root { … }` export block so every token is available as a CSS custom property (not just as a SCSS variable). Existing SCSS-level usage stays.
2. Document the token contract in `plugins/theme_lernhive/docs/03-dev-doc.md` § Tokens.
3. Commit + deploy — no functional change yet.

### Phase 2 — Plugin Shell CSS migration *(target `local_lernhive` 0.6.0)*

1. Create `plugins/local_lernhive/scss/plugin-shell/` with:
   - `_tokens.scss` — neutral fallbacks for all tokens (`--lh-primary: #1a2332`, `--lh-accent: #888`, `--lh-bg: #f5f5f5`, `--lh-radius-btn: 8px`, etc.)
   - `_plugin-shell.scss` — Zone A header, Zone B infobar, content-area wrapper, grid, card, tag, btn — moved from `theme_lernhive/scss/lernhive/_plugin-shell.scss`
   - `_buttons.scss` — `.lh-btn-*` (moved from theme)
   - `_icons.scss` — `.lh-icon-*` full four-type taxonomy (moved from theme, including the 0.9.65 Type 4 work currently uncommitted)
   - `_cta-strip.scss` (moved from theme)
2. Compile to `plugins/local_lernhive/styles/plugin-shell.css`; include it from `styles.css` (Moodle auto-loads `styles.css` of every local plugin via theme).
3. Verify under `theme_lernhive`: nothing visually changes because the theme keeps overriding with its own branded values. If a selector wins in the theme today, it still wins (theme stylesheet loads after plugin stylesheet in Moodle's asset cascade).
4. Verify under Boost + Boost Union: plugin pages render a neutral Plugin Shell — structurally intact, colour-neutral.

### Phase 3 — Move shared Mustache partial *(target `local_lernhive` 0.6.1)*

1. Move `theme_lernhive/templates/plugin_shell_header.mustache` → `plugins/local_lernhive/templates/plugin_shell_header.mustache`.
2. Update call sites in plugin templates: `{{> theme_lernhive/plugin_shell_header }}` → `{{> local_lernhive/plugin_shell_header }}` (breaking change — ship in one PR across onboarding, reporting, ContentHub, Copy, and theme drawers/admin layouts).
3. Add a deprecation shim in `theme_lernhive/templates/plugin_shell_header.mustache`: `{{> local_lernhive/plugin_shell_header }}` only, marked deprecated. Drop in `theme_lernhive` 1.0.0.
4. Deploy together; purge Moodle template cache.

### Phase 4 — Theme-only feature fallbacks *(target `local_lernhive` 0.6.2)*

For every theme-only surface a plugin template references, add a plugin-side fallback:

- **Launcher icons, Lucide sprites**: plugin ships a minimal FontAwesome-only fallback; theme optionally upgrades to Lucide.
- **Accent-coloured icon boxes** (`.lh-icon-action--on-dark`): under Boost Union, render as bordered circle with greyscale fill.
- **Sticky page header behaviour** (`.lernhive-page-header`): plugins that depend on sticky offset read from a CSS custom property `--lh-page-header-height` with a sensible default (e.g. `0px`, i.e. no offset adjustment needed under non-LernHive themes).
- **Launcher trigger orange circle**: theme-only; plugins must not reference it.
- **Context Dock, Header Dock, Side Panel**: theme-only; no plugin may rely on their presence.

Each fallback documented in `plugins/local_lernhive/docs/03-dev-doc.md` § "Theme-only vs plugin-shared surfaces".

### Phase 5 — Verification and Boost Union smoke test *(target end of 0.6.2 cycle)*

1. Install `theme_boost_union` on `dev.lernhive.de` (staging instance).
2. Smoke-test matrix:
   - `/local/lernhive_contenthub/index.php` renders 3-column card grid
   - `/local/lernhive_copy/index.php` renders wizard with template cards + mode buttons
   - `/local/lernhive_library/index.php` renders catalog table with empty state
   - `/local/lernhive_reporting/index.php` renders 3-KPI dashboard + drilldown buttons
   - `/local/lernhive_onboarding/tours.php` renders tour overview with progress shell
3. Document screenshots in `mockups/boost-union-smoke/` for each page. A plugin passes if the functional flow works and nothing is visually broken (no overlapping elements, no unreadable text, no missing labels). Visual neutrality is acceptable.

### Phase 6 — Documentation sweep

1. `plugins/local_lernhive/docs/01-features.md` — add "Ships the Plugin Shell CSS and shared UX partials for all LernHive plugins."
2. `plugins/theme_lernhive/docs/00-master.md` — add ADR-07 entry pointing to ADR-P02 and describing the scope reduction.
3. `product/01-architecture.md` — add a § "Plugin vs theme responsibilities" that references ADR-P02 and restates the decoupling contract.
4. `AGENTS.md` — the "function and design are separate" principle gains an explicit reference to ADR-P02.

## Open questions

- Should `local_lernhive/styles.css` be compiled from SCSS (adds a build step) or hand-written CSS with custom properties (no build step, but we lose SCSS convenience like nesting and `@extend`)? *Leaning SCSS to stay consistent with `theme_lernhive` SCSS workflow.*
- Moodle ships a PHP-time SCSS compiler for themes. Plugins do **not** get the same automatic pipeline. We either pre-compile SCSS → CSS in CI and commit the CSS, or add a `pre_install` / `post_install` script. *Leaning pre-compile + commit.*
- Should `plugin_shell_header.mustache` live in `local_lernhive/templates/` or in `local_lernhive_contenthub/templates/shared/`? *`local_lernhive/` — it is a truly shared partial, not a ContentHub-owned one.*
- Does moving partials to `local_lernhive` create a circular dependency risk? Onboarding depends on `local_lernhive` for levels and capability resolution — still one direction. No circle.

## Numbering note

Skipped **P02** on purpose. `_claude-skills-update/eledia-moodle-ux/SKILL.md` line 440 holds an informal reservation of "ADR-P02" for "Findability is Flavour-owned, not theme-owned". That reservation never landed as a formal ADR document, but Johannes chose on 2026-04-16 to preserve the number for the Findability decision when it is formalised. This ADR claims **P03** to leave P02 available.
