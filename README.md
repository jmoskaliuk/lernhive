# LernHive

LernHive is a Moodle-based product layer focused on better UX, faster adoption, and modular learning experiences.

It is built on top of Moodle without forking Moodle core. Functional extensions are implemented as plugins, while UX and visual design are handled separately through the theme and shared UX patterns.

## Vision

LernHive turns Moodle into a more intuitive, guided, and flexible learning platform.

Core goals:

- reduce complexity for teachers and learners
- provide strong out-of-the-box defaults through Flavours
- support both classic LMS use cases and an optional LXP Flavour
- stay compatible with Moodle plugin conventions
- keep function and design clearly separated

## Core principles

- **No Moodle fork**
- **English first**
- **Moodle first where sensible**
- **Function and design are separate**
- **Flavours are recommended starting points, not hardcoded products**
- **LXP is optional, not mandatory**
- **Simple UX first, advanced features later**

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
