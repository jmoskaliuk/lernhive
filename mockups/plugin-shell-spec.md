# LernHive Plugin Shell — Design Specification

**Status:** Draft v0.1 · 2026-04-11  
**Replaces:** The loose "big card with heading" pattern that caused the card-in-card problem.

---

## Problem: Card-in-Card

The current ContentHub mockup wraps the entire page content in a large white rounded container (`.hero`), and then places content cards inside it. This creates visual nesting where the outer container reads as a card, and the inner cards feel nested — "card in card". The result:

- No clear, stable navigation anchors (no back, no help)
- Page heading floats without a structural role
- Cards cannot be the primary layout metaphor because the page container is already a card

---

## Solution: Plugin Shell Pattern

Every LernHive plugin page uses a **4-layer shell** rendered directly on the page background. There is no outer container card.

```
┌─────────────────────────────────────────────────────────┐
│ ① CONTEXT BAR (sticky, white, border-bottom)            │
│   [← Zurück]  Plugin Name | Tagline  [? Hilfe]          │
├─────────────────────────────────────────────────────────┤
│ ② TAG BAR (white, border-bottom)                        │
│   [Level: Starter] [Typ: Tour] [Status: Aktiv]          │
├─────────────────────────────────────────────────────────┤
│ ③ INFO BAR (white, border-bottom)                       │
│   [Plugin-specific: progress / stats / filter state]    │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  ④ CARD GRID (on page background)                       │
│  ┌──────┐  ┌──────┐  ┌──────┐  ┌──────┐              │
│  │ Card │  │ Card │  │ Card │  │ Card │              │
│  └──────┘  └──────┘  └──────┘  └──────┘              │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

---

## Layer 1: Context Bar

**Purpose:** Stable navigation and orientation anchor for every plugin page.  
**Behavior:** Sticky (stays visible when scrolling).

| Slot | Content | Notes |
|------|---------|-------|
| Left | `← Zurück` button | `arrow-left` icon, outline style, leads to parent surface |
| Center-top | `Plugin Name \| Tagline` | Name in nav-dark, pipe in accent orange, tagline in same weight |
| Center-bottom | Subtitle | 1 short sentence explaining what the plugin does |
| Right | `? Hilfe` button | `circle-help` icon, outline style, opens context help |

**Rules:**
- The `|` separator between name and tagline uses accent orange
- Subtitle is optional but strongly recommended; it replaces the old page-level description block
- Back button always leads one level up (e.g., Dashboard or the triggering surface)
- Help button opens a Moodle User Tour or a help panel, never a new tab

---

## Layer 2: Tag Bar

**Purpose:** Metadata at a glance — level, content type, status. Not for navigation, not for actions.  
**Behavior:** Static row, wraps on mobile.

### Tag types

| Tag layer | Example | Style |
|-----------|---------|-------|
| `level` | `Level: Starter` | Blue pill (`#e8f0fb` / `#2d5fa6`) |
| `type` | `Tour`, `Kurs`, `Snack` | Purple pill (`#ede9fb` / `#5b3fa6`) |
| `state-active` | `In Bearbeitung` | Accent-soft pill |
| `state-done` | `Abgeschlossen` | Green pill |
| `state-locked` | `Gesperrt` | Gray pill |

**Rules:**
- Tags must not look like buttons (no hover color change, no pointer cursor)
- Maximum 4 tags in one row; prefer 2–3
- Omit the tag bar entirely if no tags apply to the current context

---

## Layer 3: Info Bar

**Purpose:** Plugin-specific at-a-glance information. Changes meaning by context.  
**Behavior:** Static bar. Omit entirely when no information applies.

| Plugin | Info Bar content |
|--------|-----------------|
| Onboarding | Progress bar + `X of Y Schritte` + `▶ Weiter` CTA |
| ContentHub | Count stats: X Kurse · Y Snacks · Z Templates |
| Notifications | Active filter state + clear-filter link |
| Reporting | Date range + filter summary |
| Discovery/Explore | Result count + active filter summary |

**CTA in Info Bar:**
- Only one CTA allowed
- Use for the primary "resume" or "continue" action
- Style: accent orange pill button (`lh-btn--accent`)
- Icon: contextual (`play`, `arrow-right`)

---

## Layer 4: Card Grid

**Purpose:** Show content items only. Courses, Snacks, Communities, Tour steps, Templates.  
**Key rule:** Cards sit directly on the page background — never inside another card or container.

### Card anatomy

```
┌────────────────────────────────────┐
│ [Icon]  Type label (small caps)    │
│         Title (bold)               │
│         Subtitle (muted)   [Badge] │
├────────────────────────────────────┤
│ Description text (13px, muted)     │
├────────────────────────────────────┤  ← action strip (shaded bg)
│ [Primary action]  [spacer]  [Info] │
└────────────────────────────────────┘
```

### Grid columns by content type

| Content type | Columns (desktop) |
|-------------|-------------------|
| Selection choices (ContentHub) | 3 |
| Tour steps | 4 |
| Course cards | 3 |
| Snack cards | 4 |
| Community cards | 3 |

### Card states

| State | Visual treatment |
|-------|-----------------|
| Default | Full opacity, shadow |
| Active/current | Accent orange border (2px) |
| Done/completed | 80% opacity |
| Locked | 55% opacity, lock icon, disabled primary action |
| Hover | Slight lift (`translateY(-1px)`), stronger shadow |

---

## Card Action Icons — Standard Set

Every card carries a consistent set of action icons. Not all icons appear on every card — context determines which are shown.

| Action | Icon (Lucide) | Style | When to show |
|--------|--------------|-------|-------------|
| **Start** | `play` | `lh-btn--accent` with label | Active items only |
| **Öffnen** | `arrow-right` | `lh-btn--primary` with label | Navigation to detail |
| **Wiederholen** | `rotate-ccw` | `lh-btn--ghost` with label | Completed tour steps |
| **Kopieren** | `copy` | `lh-btn--ghost` icon-only in lists | Where duplication is allowed |
| **Löschen** | `trash-2` | `lh-btn--danger-ghost` | Admin/Trainer role only; always with confirm dialog |
| **Info** | `info` | `lh-btn--ghost` icon-only | Always present on every card |

**Rules:**
- The `Info` icon button is always the rightmost action, separated by a spacer
- Primary actions (Start, Öffnen) always carry a text label
- Destructive actions (Löschen) are never the default visible action on load — they live in a secondary position or behind a "more" menu for list views
- Icon meanings must not change between plugins

---

## Implementation notes for Moodle

- The 4 layers (Context Bar, Tag Bar, Info Bar, Card Grid) map to Mustache blocks inside the plugin's `view.php` output
- Context Bar is rendered via a shared Mustache partial: `local_lernhive/plugin_context_bar`
- Tag Bar and Info Bar are plugin-specific Mustache blocks passed as template context
- Cards use Bootstrap grid (`row` / `col-md-4`) with the `lh-card` CSS class from `theme_lernhive`
- The `lh-card` card shell CSS lives in `theme_lernhive/scss/_components.scss` (shared)
- Plugin-specific card content is rendered by each plugin's own template

---

## Reference mockup

`mockups/plugin-shell-concept.html` — interactive demo with:
- ContentHub tab (selection cards)
- Onboarding tab (tour step cards with progress)
- Vorher/Nachher comparison (card-in-card vs. plugin shell)
