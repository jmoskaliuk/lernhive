# LernHive

LernHive is a Moodle-based product layer focused on better UX, faster adoption, and modular learning experiences.

It is built on top of Moodle without forking Moodle core. Functional extensions are implemented as plugins, while UX and visual design are handled separately through the theme and shared UX patterns.

---

## Vision

LernHive turns Moodle into a more intuitive, guided, and flexible learning platform.

Core goals:

- reduce complexity for teachers and learners
- provide strong out-of-the-box defaults through Flavours
- support both classic LMS use cases and an optional LXP Flavour
- stay compatible with Moodle plugin conventions
- keep function and design clearly separated

---

## Core principles

- **No Moodle fork**
- **English first**
- **Moodle first where sensible**
- **Function and design are separate**
- **Flavours are recommended starting points, not hardcoded products**
- **LXP is optional, not mandatory**
- **Simple UX first, advanced features later**

---

## Start here

If you are new to the project, follow this order.

### 1. Understand the product

Read these files first:

1. `product/00-strategy.md`
2. `product/01-architecture.md`
3. `product/02-plugin-map.md`

These documents explain:
- what LernHive is
- what it is not
- how the system is structured
- which plugins exist and why

### 2. Understand the language

Before writing UI, code, or documentation, read:

1. `product/04-language-guide.md`
2. `product/05-string-inventory.md`
3. `product/06-core-string-reuse-map.md`

Important rules:
- English first
- reuse Moodle core strings where possible
- keep LernHive terms stable
- do not introduce flavour-specific wording lightly

### 3. Understand release scope

Then read:

1. `product/03-roadmap.md`
2. `product/07-next-steps.md`
3. `product/08-backlog.md`

This tells you:
- what belongs to Release 1
- what belongs to Release 2
- which decisions are still open

### 4. Work on plugins

Each plugin has its own DevFlow set in `plugins/<pluginname>/`.

Use this order:

1. `00-master.md`
2. `01-features.md`
3. `02-user-doc.md`
4. `03-dev-doc.md`
5. `04-tasks.md`
6. `05-quality.md`

### 5. Before changing anything

Check these questions:

- Is this already covered by Moodle core?
- Is there already a LernHive term for this?
- Does this belong in product docs or plugin docs?
- Is this Release 1 or later?
- Does this affect more than one plugin?

---

## Repository structure

```text
product/
  00-strategy.md
  01-architecture.md
  02-plugin-map.md
  03-roadmap.md
  04-language-guide.md
  05-string-inventory.md
  06-core-string-reuse-map.md
  07-next-steps.md
  08-backlog.md
  09-ux-navigation.md

plugins/
  local_lernhive/
  local_lernhive_launcher/
  local_lernhive_contenthub/
  local_lernhive_copy/
  local_lernhive_library/
  local_lernhive_flavour/
  local_lernhive_configuration/
  local_lernhive_onboarding/
  local_lernhive_contexthelper/
  local_lernhive_discovery/
  local_lernhive_audience/
  local_lernhive_reporting/
  local_lernhive_notifications/
  theme_lernhive/

mockups/
  explore-home.html
  launcher.html
  contenthub.html
  reporting-dashboard.html
  audience-editor.html
  onboarding-levels.html
# Fr 10 Apr 2026 23:04:13 CEST
# Fr 10 Apr 2026 23:04:36 CEST
