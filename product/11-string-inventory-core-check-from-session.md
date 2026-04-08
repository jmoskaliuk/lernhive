# LernHive String Inventory v2 — Core Check, Plugin String Plan, Canonical Vocabulary

## Scope

This document does three things:
1. Checks the current LernHive term inventory against the provided `en.zip` language pack and identifies **Moodle core strings** that can be reused.
2. Defines a **plugin string plan** for terms that should remain LernHive-specific.
3. Defines a **canonical vocabulary list** for UX, docs, marketing, and sales.

## Working rules

1. **Reuse Moodle core strings** whenever they are semantically correct and UX-appropriate.
2. **Create plugin strings** only when Moodle core has no suitable equivalent or when LernHive needs a distinct product term.
3. **English first**: canonical terms are defined in English; language packs localize them later.
4. **Flavour-specific terminology only restrictively**.
5. **One term per concept**.

---

## 1) Core check against Moodle core language strings

Checked against the provided `en.zip` language pack, focusing on core-like components such as `moodle.php`, `admin.php`, `message.php`, `course.php`, `calendar.php`, `group.php`, `completion.php`, `search.php`, and related standard files.

### A. Reuse Moodle core strings

| Canonical term | Recommended source component | String identifier | English value found | Decision | Notes |
|---|---|---:|---|---|---|
| Home | core / `moodle.php` | `home` | Home | Reuse | Good generic navigation term |
| Dashboard | core / `moodle.php` | `myhome` | Dashboard | Reuse | Strong fit for LMS flavours |
| Search | core / `moodle.php` | `search` | Search | Reuse | Standard action |
| Notifications | core / `moodle.php` or `message.php` | `notifications` | Notifications | Reuse | Standard system term |
| Course | core / `moodle.php` | `course` | Course | Reuse | Central Moodle term |
| Activity | core / `moodle.php` | `activity` | Activity | Reuse | Central Moodle term |
| Section | core / `moodle.php` | `section` | Section | Reuse | Standard course structure term |
| Participants | core / `moodle.php` | `participants` | Participants | Reuse | Strong default; decide DE label later |
| Import | core / `moodle.php` | `import` | Import | Reuse | Standard action |
| Export | core / `calendar.php` and others | `export` | Export | Reuse | Standard action |
| Progress | core / `moodle.php` | `progress` | Progress | Reuse | Strong fit for onboarding/reporting |
| Topic | core / `moodle.php` | `topic` | Topic | Reuse cautiously | Useful if semantically correct |
| Report | core / `moodle.php` | `report` | Report | Reuse | Standard reporting term |
| Overview | core / `group.php` and others | `overview` | Overview | Reuse | Strong generic label |
| Teacher | core / `moodle.php` | `defaultcourseteacher` | Teacher | Reuse cautiously | Better as base source than a new LernHive term |
| Student | core / `moodle.php` | `defaultcoursestudent` | Student | Reuse cautiously | Base source for learner role |
| Administrator | core / `moodle.php` | `administrator` | Administrator | Reuse | Standard system term |
| Completion | core / `completion.php` | `completionmenuitem` and related | Completion | Reuse cautiously | Core has the concept, but identifiers vary |

### B. Reuse only with caution / only if exact UX fits

| Canonical term | Core evidence | Decision | Notes |
|---|---|---|---|
| Course settings | Found in a report component, not clearly as a strong universal core UI string | Usually create in plugin or reuse page title contextually | Avoid depending on a weak/non-core-like occurrence |
| Email | Found broadly, but source identifiers differ by component | Reuse when tied to existing Moodle forms; otherwise rely on standard mail UI | Avoid duplicating if existing form already provides the label |
| Event | Found in reporting-related components | Reuse only if modelling a generic event | If `Event` becomes a LernHive content type, create a specific term |
| Manager | Not confirmed as a strong single reusable core label in the checked set | Prefer role mapping strategy | Likely better handled as role label mapping than as a new product term |

### C. No suitable Moodle core string found in the checked set → create LernHive strings

| Canonical term | Decision | Reason |
|---|---|---|
| Discover | Create | Needed as LXP entry point replacing Dashboard |
| Follow | Create | Core does not provide the product concept |
| Bookmark / Save for later | Create | Core does not provide the desired discovery/bookmark UX |
| Launcher | Create | LernHive product concept |
| Context Helper | Create | LernHive UX concept |
| Community | Create | Central LXP concept |
| Snack | Create | Central LXP concept |
| ContentHub | Create | Product concept |
| Template | Create | Product/content architecture concept |
| Library | Create | Product/content architecture concept |
| Feed | Create | LXP discovery concept |
| Audience | Create | Even though `Audience` appears in `reportbuilder.php`, this should remain a LernHive product term because it is semantically central and may need stable product-level wording |
| New for you | Create | Discovery block label |
| From your community | Create | Discovery block label |
| Short and useful | Create | Discovery block label |
| Save for later | Create | Bookmark action / CTA |
| Daily digest | Create | LernHive notification extension |
| Weekly digest | Create | LernHive notification extension |
| In-app only | Create | LernHive notification preference concept |
| New snack | Create | LXP event type |
| New member | Create | LXP event type |
| New discussion | Create | LXP event type |
| Onboarding | Create | Product-level concept |
| Level | Create | LernHive progression model |
| Explorer / Creator / Pro / Expert / Master | Create | Fixed LernHive level names |
| Unlock feature | Create | LernHive progression CTA |
| Next level | Create | LernHive progression UI |

### D. Special note on `Audience`

The checked pack contains `reportbuilder.php: audience = Audience`. However, because `Audience` is intended to be a **stable, visible, cross-plugin LernHive concept** (and not merely a reused internal label), it should still get a **LernHive-owned string**. This avoids coupling a product-critical term to a possibly unrelated subsystem label.

---

## 2) Plugin String Plan

### Strategy

- Use **core strings** directly in code whenever the label already exists and fits.
- Introduce **plugin strings** only for LernHive-specific concepts.
- Keep plugin strings grouped by **feature prefix** for maintainability.

### Recommended string prefixes

| Prefix | Scope |
|---|---|
| `core_` | LernHive-wide helper strings not covered by Moodle core |
| `launcher_` | Launcher UI |
| `contenthub_` | ContentHub UI |
| `discovery_` | LXP Discovery and feed |
| `audience_` | Audience UI and rule builder |
| `follow_` | Follow / bookmark concepts |
| `notification_` | Notification extensions, digest logic |
| `reporting_` | Reporting tiles and drilldowns |
| `onboarding_` | Onboarding and level system |
| `level_` | Level names and level-related CTAs |

### Recommended plugin-owned strings

#### `local_lernhive`
- `pluginname`
- common product strings only if they are truly global to the plugin

#### `local_lernhive_launcher`
- `launcher_title`
- `launcher_open`
- `launcher_create_course`
- `launcher_create_snack`
- `launcher_create_community`
- `launcher_manage_users`

#### `local_lernhive_contenthub`
- `contenthub_title`
- `contenthub_start_how`
- `contenthub_copy_course`
- `contenthub_browse_library`
- `contenthub_use_template`
- `contenthub_start_with_ai`

#### `local_lernhive_discovery`
- `discovery_title`
- `discovery_search_placeholder`
- `discovery_block_newforyou`
- `discovery_block_fromcommunity`
- `discovery_block_shortanduseful`
- `discovery_block_popular`
- `discovery_type_snack`
- `discovery_type_community`
- `discovery_type_learningoffer` (only if used as UX layer)

#### `local_lernhive_audience`
- `audience_title`
- `audience_create`
- `audience_members`
- `audience_add_rule`
- `audience_rule_if`
- `audience_rule_then`
- `audience_dynamic`
- `audience_static`

#### `local_lernhive_follow`
- `follow_action`
- `follow_stop`
- `follow_following`
- `bookmark_action`
- `bookmark_saved`
- `bookmark_savedforlater`

#### `local_lernhive_notifications`
- `notification_digest_daily`
- `notification_digest_weekly`
- `notification_inapponly`
- `notification_newsnack`
- `notification_newmember`
- `notification_newdiscussion`
- `notification_reason_fromcommunity`
- `notification_reason_following`

#### `local_lernhive_onboarding`
- `onboarding_title`
- `onboarding_tour`
- `onboarding_continue`
- `onboarding_unlockfeature`
- `onboarding_nextlevel`

#### `local_lernhive_levels` or within onboarding/local core plugin
- `level_explorer`
- `level_creator`
- `level_pro`
- `level_expert`
- `level_master`

---

## 3) Canonical Vocabulary List (shortlist)

This is the shortlist of terms that should remain stable across UX, docs, product, marketing, and sales.

### A. Reuse from Moodle core

| Canonical term | Use it as | Notes |
|---|---|---|
| Home | Home | Generic navigation |
| Dashboard | Dashboard | LMS default entry |
| Search | Search | Generic search action |
| Notifications | Notifications | Standard system term |
| Course | Course | Central Moodle object |
| Activity | Activity | Standard Moodle object |
| Section | Section | Standard Moodle object |
| Participants | Participants | Default enrolment/people term |
| Import | Import | Generic action |
| Export | Export | Generic action |
| Progress | Progress | Strong for reporting and onboarding |
| Report | Report | Standard analytics/reporting term |
| Overview | Overview | Generic dashboard/report section |
| Teacher | Teacher | Base canonical role term |
| Student | Student | Base canonical role term |
| Administrator | Administrator | Base canonical admin term |

### B. Stable LernHive product terms

| Canonical term | Why it must stay stable |
|---|---|
| Discover | Defines the LXP entry experience |
| Audience | Core personalization and segmentation concept |
| Follow | Core subscription concept |
| Bookmark | Core save-for-later concept |
| Launcher | Core action entry point |
| ContentHub | Core content entry point |
| Community | Core LXP concept |
| Snack | Core lightweight content concept |
| Template | Core content structure concept |
| Library | Core curated-content concept |
| Feed | Core discovery concept |
| Onboarding | Core adoption concept |
| Level | Core progression concept |
| Explorer / Creator / Pro / Expert / Master | Fixed level names |

### C. Restrictively flavour-sensitive terms

These may vary **only if there is a strong UX reason**.

| Canonical term | Possible variation |
|---|---|
| Dashboard | May be replaced by `Discover` in LXP |
| Teacher | May surface as Trainer / Lecturer / similar in localized UI |
| Student | May surface as Learner / Member / similar in localized UI |
| Participants | May vary lightly by flavour if necessary |
| Topic | Could remain Topic, or use a more context-specific label only if strictly needed |

---

## 4) Practical implementation rules

1. In code, call **Moodle core strings first** whenever they fit.
2. Add plugin strings only for LernHive-owned concepts.
3. Do **not** create flavour-specific language packs.
4. Keep flavour terminology changes at the UI-configuration layer, not in the canonical vocabulary.
5. The **Language Guide** remains the semantic source of truth; this inventory is the implementation map.

---

## 5) Recommended next step

Create a **Core String Reuse Map** in implementation form, for example:

- `Dashboard` → `get_string('myhome', 'moodle')`
- `Search` → `get_string('search', 'moodle')`
- `Course` → `get_string('course', 'moodle')`
- `Participants` → `get_string('participants', 'moodle')`
- `Progress` → `get_string('progress', 'moodle')`

And then create plugin language files only for the LernHive-owned concepts listed above.
