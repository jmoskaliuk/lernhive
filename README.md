# LernHive Repository Pack

Repo-ready working pack for the LernHive product and plugin ecosystem.

## Purpose

This repository bundles:
- product strategy and target architecture
- plugin map and DevFlow sets
- language and string guidance
- roadmap, backlog, open decisions
- first UX mockups

## Core product idea

LernHive is a SaaS-first Moodle distribution with an experience layer on top of Moodle core.
It improves UX, onboarding, course creation, discovery and flavour-based configuration without forking Moodle.

## Key principles

- Moodle stays the core
- no fork
- function and design are clearly separated
- English first on UX and language-file level
- Moodle core strings are reused whenever they fit
- Flavours are opinionated starting points, not hard product forks
- LXP is an optional Flavour, not the default product model

## Main folders

- `product/` — cross-plugin product architecture and decisions
- `plugins/` — DevFlow sets per plugin
- `mockups/` — first screen concepts and simple HTML mockups
- `assets/` — optional supporting material

## Current status

This repo is a planning and architecture baseline, not a code repository.
It is intended to be pushed to GitHub and collaboratively refined.

## Suggested next use

1. Review the product docs in `product/`
2. Confirm plugin boundaries in `product/02-plugin-map.md`
3. Start implementation tickets from `product/07-next-steps-and-decisions.md`
4. Convert approved plugin DevFlows into code repos
