---
name: eledia-moodle-ux
description: "eLeDia Moodle plugin & theme UX / visual design system, plus Moodle-theme review framework. Use this skill whenever building or modifying any Moodle plugin UI for eLeDia GmbH — including view pages, report pages, attempt/interaction pages, settings forms, CSS styling, progress visualizations, dashboard layouts, icon design — OR when auditing, reviewing, refactoring, or making architectural decisions about a Moodle theme (theme_lernhive, theme_boost_*, theme layouts, templates, SCSS, block regions, design tokens, findability contracts). Also trigger when the user mentions 'make it look like LeitnerFlow', 'eLeDia style', 'consistent design', plugin icons, color palette, Moodle component usage, 'Theme-Review', 'Theme-Architektur', 'theme refactor', 'Wo finde ich was' (findability), or asks whether a requirement belongs in theme, course format, or plugin. This skill ensures all eLeDia plugins share a coherent visual language and that theme-level decisions follow a consistent, Moodle-conform, upgrade-safe methodology."
---

# eLeDia Moodle Plugin UX System

This skill defines the visual design language for all eLeDia GmbH Moodle plugins. Every plugin should feel like part of the same family — clean, learner-focused, and built on Moodle's native component library rather than custom CSS.

It also carries the **theme-design review framework** used for `theme_lernhive` and any other Moodle theme eLeDia audits, refactors, or ships. The plugin-UI content and the theme-review content share the same spine: Moodle-native first, design-system thinking, accessibility as a floor, and upgrade-safety as a non-negotiable.

## Design Philosophy

eLeDia plugins follow three principles:

1. **Moodle-native first** — Use Bootstrap and Moodle Component Library components wherever possible. Custom CSS only for truly unique visualizations (e.g., color-coded progress stages). This ensures plugins survive Moodle theme updates and maintain built-in accessibility.

2. **Progressive disclosure** — Start with a clean overview (dashboard cards), let users drill into detail (history tables, reports). Don't overload any single view.

3. **Visual progress storytelling** — Use color gradients and spatial progression to communicate learning/completion state at a glance. Warm colors = early/struggling, cool colors = advancing, green = mastered/complete.

## When to Read Reference Files

- **Building any view page, report, or dashboard** → Read `references/components.md` for the full component catalog with code examples
- **Choosing colors or styling custom elements** → Read `references/colors.md` for the complete eLeDia color palette and usage rules
- **Creating plugin icons** → Read `references/icons.md` for the Lucide-style icon guidelines

Always read the relevant reference files before writing any HTML, CSS, or PHP output code. The patterns in those files represent tested, production-proven approaches.

---

## Quick Reference: Layout Patterns

### Page Structure

Every plugin view page follows this skeleton:

```php
echo $OUTPUT->header();

// Optional: hide activity description on focused pages (attempt, quiz)
$PAGE->activityheader->set_description('');

// Content cards, top to bottom
echo '<div class="card mb-4"><div class="card-body">...</div></div>';

echo $OUTPUT->footer();
```

### Card Hierarchy

Cards are the primary layout container. Use them consistently:

| Card Type | Pattern | When |
|-----------|---------|------|
| Dashboard | `card mb-4` with `card-title` + visualization | Main student view |
| Action area | Buttons in `d-flex gap-2 flex-wrap` | Above content, before history |
| History/list | `card mb-4` with `table table-sm table-hover` inside | Session/activity logs |
| Report summary | `row` with `col-md-4` cards | Teacher overview stats |

### Responsive Rules

- Button groups: `d-flex gap-2 flex-wrap` (wraps on mobile)
- Multi-column stats: `row` + `col-md-4` (stacks on mobile)
- Focused content (questions, forms): `max-width: 720px; margin: 0 auto;`
- Tables: Always `table-sm` for compact mobile display

### Typography Scale

| Element | Size | Weight | Class/Style |
|---------|------|--------|-------------|
| Page heading | Auto | Auto | Moodle renders via `$OUTPUT->heading()` |
| Card title | Default | Bold | `<h5 class="card-title">` |
| Large number | 1.7rem | 800 | Custom (counts, scores) |
| Body text | Default | Regular | No class needed |
| Table header | Small | Regular | `small text-muted` |
| Small label | 0.7rem | 600 | `text-transform: uppercase; letter-spacing: 0.3px` |

### Button Hierarchy

Maintain consistent button semantics across all plugins:

| Action Type | Class | Example |
|-------------|-------|---------|
| Primary action | `btn btn-primary` | Start, Continue, Submit |
| Alternative action | `btn btn-outline-secondary` | New session, Back |
| Destructive | `btn btn-outline-danger` | End session, Reset |
| Small table action | `btn btn-sm btn-outline-danger` | Per-row reset |
| Moodle-wrapped | `$OUTPUT->single_button()` | Form-backed actions |

### Progress Visualization

Multi-segment progress bars show stage distribution:

```html
<div class="progress mb-1" style="height: 12px;">
    <div class="progress-bar lf-seg-stage1" style="width:25%"
         role="progressbar" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
    </div>
    <!-- more segments -->
</div>
```

- Height: `12px` (compact but readable, visible on mobile)
- Color per segment matches the stage color from the palette
- Always include ARIA `role="progressbar"` attributes

### Status Indicators

Use Bootstrap badges consistently:

| State | Badge | Use |
|-------|-------|-----|
| Count/neutral | `badge bg-secondary` | "5 sessions completed" |
| Success/learned | `badge bg-success` | Learned count, positive trend |
| Error/warning | `badge bg-danger` | Error count, negative trend |
| Primary highlight | `badge bg-primary` | Current/active state |
| Trend up | `badge bg-success` + ↗ | Improving performance |
| Trend down | `badge bg-danger` + ↘ | Declining performance |
| Trend stable | `badge bg-secondary` + → | Stable performance |

### Alerts & Notifications

| Purpose | Pattern |
|---------|---------|
| Celebration | `alert alert-success d-flex align-items-center` |
| Active state info | `alert alert-info` |
| System warning | `$OUTPUT->notification()` |
| Auto-fading feedback | Custom `.lf-feedback-banner` with JS fade |

---

## CSS Architecture

### Variable Naming Convention

All plugin CSS variables follow the pattern `--{prefix}-{color-name}`:

```css
:root {
    --lf-orange: #f98012;
    --lf-dark-blue: #194866;
    /* ... */
}
```

Replace `lf` with your plugin's 2-letter prefix. See `references/colors.md` for the full palette.

### Custom CSS Rules

Only write custom CSS for:
- **Stage/phase color schemes** (the warm→cool gradient concept)
- **Unique visualizations** (box layouts, flowcharts, interactive elements)
- **Micro-animations** (pulse, fade, glow transitions)
- **Hiding Moodle duplicate elements** (e.g., duplicate description)

Everything else should use Bootstrap utility classes directly in HTML.

### Animation Patterns

For state-change animations (e.g., card moving, answer feedback):

```css
/* Subtle entrance */
@keyframes plugin-pulse-in {
    0% { transform: scale(0.8); opacity: 0.3; }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); opacity: 1; }
}

/* Highlight glow */
.some-element-highlight {
    box-shadow: 0 0 12px rgba(102, 153, 51, 0.6); /* green for success */
    /* or rgba(249, 128, 18, 0.6) for orange/warning */
}
```

- Keep durations short: 0.3–0.5s for transitions, 2–3s for feedback banners
- Always make animations optional via a plugin setting (`showanimation`)
- Use `data-` attributes on target elements for JS hooks

---

## Accessibility Checklist

Every plugin page must include:

- [ ] `role="group"` + `aria-label` on custom component groups
- [ ] `role="progressbar"` + `aria-valuenow/min/max` on progress bars
- [ ] `role="status"` on live-updating counters
- [ ] Meaningful `aria-label` on interactive custom elements
- [ ] `aria-hidden="true"` on decorative icons/arrows
- [ ] Semantic table structure: `<thead>` + `<tbody>`
- [ ] Color is never the only indicator — always pair with text/icons

---

## Language Strings

### Reuse Core Strings

Before defining a custom string, check if Moodle core already has it:

Common reusable strings: `date`, `progress`, `question`, `participants`, `continue`, `cancel`, `categories`, `delete`, `reset`, `status`, `name`, `description`

Use: `get_string('date')` (no component = core)

### Custom String Naming

```
{action}{object}     → startsession, resetprogress
{object}{qualifier}   → questionsinpool, avglearnedpercent
{object}_help         → showanimation_help
{status}_{state}      → cardstatus_learned
event_{action}        → event_session_started
privacy:metadata:{table}:{field} → standard privacy strings
```

### Multilingual Content

For content displayed outside lang-string system (e.g., User Tours), use Moodle core multilang HTML:

```html
<span class="multilang" lang="en">English text</span>
<span class="multilang" lang="de">Deutscher Text</span>
```

This requires the core `multilang` filter (not the third-party `multilang2` plugin). Enable it programmatically during install if needed.

---

## Forms (mod_form.php)

### Section Organization

Group settings into logical collapsible sections:

```
general              → Name, description (standard)
{domain}settings     → Domain-specific options
sessionsettings      → Session/interaction config
displaysettings      → Visual toggles (animations, layout)
gradingsettings      → Assessment options
```

### Field Patterns

- Narrow number inputs: `$mform->addElement('text', 'field', $label, ['size' => '4'])`
- Yes/No toggles: `$mform->addElement('selectyesno', 'field', $label)`
- Multi-select with counts: Autocomplete element with `"Category (42 items)"` labels
- Every field gets a help button: `$mform->addHelpButton('field', 'field', 'mod_plugin')`

---

## Report Pages

### Summary Cards (Top)

Three key metrics in a responsive grid:

```php
$summaries = [
    [get_string('participants'), count($students), 'bg-primary'],
    [get_string('itemcount', 'mod_plugin'), $count, 'bg-secondary'],
    [get_string('avgpercent', 'mod_plugin'), $pct . ' %', 'bg-success'],
];
```

Render as `row` → `col-md-4` → `card text-white {bg-class}`.

### Student Table

Standard Moodle generaltable with badges for status columns:

```html
<table class="table table-striped table-hover generaltable">
```

- User column: `$OUTPUT->user_picture()` + linked name
- Status columns: Colored badges (`bg-success`, `bg-secondary`, `bg-danger`)
- Progress column: Inline progress bar + percentage
- Action column: Small outline buttons for per-student actions

---

# Part 2 — Theme Review & Audit Framework

This section applies when the user is **not** building a single plugin view but instead working at theme level: refactoring a theme, reviewing a theme architecture, making a theme-vs-format-vs-plugin decision, or auditing an existing theme against accessibility, consistency, and upgrade-safety criteria.

## Core mental model

Moodle theme design is **never** just CSS. Think in six dimensions at once:

1. Branding / visual identity
2. Information architecture & orientation
3. Moodle extension points (layouts, templates, renderers, block regions)
4. SCSS / design-system strategy
5. Accessibility (WCAG 2.2 AA floor, 2.1 AA as non-negotiable)
6. Upgrade-safety & maintainability across Moodle major versions

If a recommendation only addresses one dimension, it is incomplete.

## Decision frame: Theme vs Course Format vs Plugin

This is the most common classification error in Moodle projects. Apply it as **step 2 of every theme-related conversation**:

- **Theme** = global chrome. Header, footer, navigation frame, block regions, visual tokens, typography, component styling, login/base layouts. Site-wide, role-agnostic, reusable across any Moodle instance that installs the theme.
- **Course Format** = the structure and rendering of a course *inside*. Sections, ordering, section-level navigation, course-internal layout. Course-scoped, swappable per course.
- **Plugin** (local / block / mod / auth / …) = functional capability, business logic, new workflows, new data, new capabilities. Role-sensitive, may or may not render UI.

Decision rules (apply in order):

1. Is the requirement about **site-wide chrome, visual identity, or navigation frame**? → Theme.
2. Is the requirement about **how a course looks from inside** (sections, course-level ordering, course-internal navigation)? → Course Format, not Theme.
3. Is the requirement about **a new capability, new data, new workflow, or new permission**? → Plugin, not Theme.
4. Is the requirement achievable only via **fragile core/renderer overrides**? → Surface the fragility explicitly and offer a less fragile alternative, even if it means saying "we can't do this cleanly in the theme".
5. Does the requirement **improve aesthetics but hurt orientation, accessibility, or task efficiency**? → Push back and offer a better alternative.
6. If the user wants to *improve* an existing theme, **analyze architecture before touching CSS**.

LernHive-specific anchor: ADR-01 (in `plugins/theme_lernhive/docs/00-master.md`) and ADR-P01 (in `product/01-architecture.md`) pin this rule for the LernHive product. The theme does not render course content; course format plugins do.

## Work logic — the 6 steps

For every theme-scoped request, work in this order. Skip no step.

1. **Classify the request** — introduction, architecture question, UX question, implementation question, review, strategy, refactor, or documentation.
2. **Check jurisdiction** — is this really a theme question, or does step 2 of the decision frame move it elsewhere?
3. **Pick the Moodle-correct extension point** — theme config, layout PHP, Mustache template override, SCSS partial, AMD JS, renderer subclass, course format, other plugin, or a combination.
4. **Develop a recommendation** — robust, maintainable, upgrade-safe, with an explicit why.
5. **Name the risks** — update risk, coupling, accessibility regressions, maintenance cost, conflicts with core Moodle behaviour.
6. **Make it pragmatic** — concrete, realistic, prioritized next steps. No exotic solutions unless they are the only correct ones.

## Review checklist

When auditing an existing theme (eLeDia-owned or third-party), score each category. Every gap is a candidate ticket.

### Architecture
- Theme responsibility cleanly scoped?
- Any theme logic doing work that belongs to a course format or plugin?
- Any core hacks? Any renderer overrides that fight core too hard?
- Layout → template → SCSS flow traceable?

### UX & orientation
- Navigation model consistent across pages?
- Does the design support learning and working tasks, or only look good?
- Are key functions placed visibly and consistently?
- Does the theme improve or damage orientation on primary pages?
- Does each page answer a clear user question? (Findability contract — see `product/09-ux-navigation.md` in the LernHive repo.)

### Frontend system
- Design tokens present and consistently named?
- SCSS organized by partial / concern, not by patch history?
- Components designed for reuse, not per-page copies?
- CSS/SCSS strategy documented and enforceable?

### Accessibility
- Visible focus states everywhere, including custom components?
- Colour contrast plausible for all text and interactive elements?
- Full keyboard operability on all interactions?
- Semantic structure (headings, landmarks, ARIA) robust?
- Screen reader labels meaningful on icon-only buttons?
- Reduced-motion respect via `prefers-reduced-motion`?
- Target: WCAG 2.2 AA. Floor: WCAG 2.1 AA.

### Maintainability
- Parent theme strategy sensible for the target Moodle versions?
- Template overrides kept to a documented minimum?
- Low fragility against Moodle major-version upgrades?
- Docs-as-code present for each non-obvious decision?

## Anti-pattern catalog

Flag these explicitly on review, even if they "work":

1. Theme and course format conflated — course-content rendering glued into theme templates.
2. Everything solved with CSS hacks instead of proper extension points.
3. Components designed per-page instead of systemically.
4. Branding prioritized over usability — "looks like the corporate brochure, works like a maze".
5. Navigation visually beautified but functionally degraded.
6. Hard overrides of core structures where a renderer override or hook would have been enough.
7. Accessibility ignored, treated as "phase 2", or compensated for with a separate accessibility page.
8. Too many per-page special cases without a unifying principle — theme has become a scrapyard.
9. Strong coupling to one specific Moodle version, no forward compatibility plan.
10. No governance or documentation of anchor decisions — every refactor starts from zero.

## Default assumptions

When the user hasn't specified:

- Target Moodle version: current major (Moodle 5.x at time of writing).
- Parent theme: Boost (or a Boost-based parent), not Classic.
- Accessibility: WCAG 2.2 AA target. No lower floor accepted.
- Responsive design required across phone / tablet / desktop.
- Upgrade safety required — the theme must survive at least the next Moodle major version without a rewrite.
- Non-exotic solutions preferred — if there is a standard Moodle way and a clever way, recommend the standard one.

## Preferred answer shapes

### Explaining a theme concept
1. Classification
2. Why it matters in Moodle specifically
3. Technical perspective (extension points, files involved)
4. Typical mistakes
5. Recommended approach

### Architecture question
1. Target picture
2. Correct technical jurisdiction
3. Sensible extension points
4. Recommended implementation path
5. Risks and limits
6. The maintainable variant

### Theme review
Use the Review checklist above as the structure of the answer. Architecture → UX → Frontend system → Accessibility → Maintainability. Close with a prioritized fix list.

### Implementation advice
1. Goal
2. Moodle-correct approach
3. Concrete technical steps
4. Likely pitfalls
5. Recommended priorization

## Answer rules

- Precise, not vague.
- Separate concept from implementation.
- Always think in Moodle extension points, not in generic CSS.
- No exotic solutions unless they are the only correct ones.
- Actively surface maintainability cost.
- Name limits and grey zones openly.
- Connect every recommendation to a concrete impact on a real Moodle project. Abstract answers are not acceptable.

## LernHive-specific hooks

When the theme in question is `theme_lernhive` (the primary LernHive product theme), additionally respect:

- **ADR-01** — Course content lives in `format_lernhive_*` plugins, not in theme templates. Never add course-content Mustache partials to the theme after 0.9.3.
- **ADR-02** — Block regions are the 6 neutral regions introduced in 0.9.3 (`content-top`, `content-bottom`, `sidebar-bottom`, `footer-left`, `footer-center`, `footer-right`). No new regions without an ADR.
- **ADR-P02** — Findability is Flavour-owned, not theme-owned. Regions stay semantically neutral. If the user asks for semantic region names, redirect them to `local_lernhive_flavour` and `product/09-ux-navigation.md` (Findability contract).
- **DevFlow docs** — Plugin-level decisions belong in `plugins/theme_lernhive/docs/` (00-master / 03-dev-doc). Product-level decisions belong in `product/`. Don't create new top-level docs for things that fit in an existing file.
- **Version bumps** — Never guess `version.php` numbers. Read `origin/main` first (see `feedback_version_bumps` memory).
