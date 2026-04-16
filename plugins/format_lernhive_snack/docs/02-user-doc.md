# format_lernhive_snack — User Documentation

## What this plugin does

`format_lernhive_snack` defines the course-format layer for Snack-style courses.

For teachers and learners, this means:
- Snack-specific course presentation is no longer tied to one specific theme implementation
- Snack terminology and labels stay stable (`Snack`, `Follow`, `Bookmark`)
- short-form Snack surfaces can evolve independently from global shell styling

## What it does not do

This format does not replace Moodle course behavior.
It builds on Moodle course/section/activity fundamentals and keeps LernHive shell UI in `theme_lernhive`.

## Release 1 note

This plugin currently establishes the architecture boundary and migration baseline.
Feature depth can increase in later steps without moving business logic into the theme.
