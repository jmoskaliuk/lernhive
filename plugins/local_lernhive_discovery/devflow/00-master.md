# local_lernhive_discovery — Master Document

**Plugin type:** local plugin
**Release target:** R1 (LXP)

## Purpose

Explore feed and content discovery system for the optional LXP Flavour.

## Role in LernHive

This plugin is part of the LernHive ecosystem and should fit the core rules:
- Moodle stays the core
- English-first terminology
- use Moodle core strings wherever possible
- no business logic in the theme
- must stay understandable for UX, docs, marketing and sales

## Main dependencies
- `local_lernhive_follow`
- `local_lernhive_audience`
- `local_lernhive_notifications` for digest-relevant event context

## Main features
- Explore start page
- slim feed blocks
- content cards for Courses, Snacks, and Communities
- explainable ranking
- Follow and Bookmark entry points on cards

## Release scope

### Release 1
- Explore exists only in the LXP Flavour
- Explore replaces Dashboard only in the LXP Flavour
- feed blocks stay fixed and slim
- ranking stays explainable and non-social-noisy

### Release 2
- stronger personalization may refine Explore logic later
- richer ranking inputs may be added only if they stay explainable
- Release 2 work must not change the simple Release 1 baseline silently

## DevFlow files
- 00-master.md
- 01-features.md
- 02-user-doc.md
- 03-dev-doc.md
- 04-tasks.md
- 05-quality.md
