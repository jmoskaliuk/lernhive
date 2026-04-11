# local_lernhive_contenthub — User Documentation

## User value
A single, calm entry screen that answers the question *"how would you
like to start?"* when an author wants to create or bring in content.
The screen avoids hidden complexity by showing every path (Copy,
Template, Library, and optionally AI) as a card with a clear status.

## What the user should experience
- simple, clear labels reused from Moodle core where possible
- a list of content-creation paths shown as cards in a fixed order
- each card has a heading, a one-line description, and a status
  badge that says either "Available", "Coming soon", or "Unavailable"
- the call-to-action on a card is only clickable when the underlying
  sibling plugin is installed and the current user holds the relevant
  capability
- no hidden complexity: if a path is unavailable the card is still
  visible but clearly marked, so the user is never confused about
  missing options
- terminology that matches the rest of LernHive product communication
  (ContentHub, Template, Library — not "wizard", "importer", etc.)

## Main user journeys
- **Site administrator** reaches the ContentHub via
  Site administration → Plugins → Local plugins → LernHive ContentHub
  → Open ContentHub.
- **Course creator** reaches the ContentHub directly at
  `/local/lernhive_contenthub/index.php` (the launcher will link here
  once it lands; for R1 the URL is used directly).
- From either entry point the user picks one of the available cards
  and is taken to the sibling plugin's own page — from there on the
  sibling plugin owns the flow.

## Accessibility notes
- cards are rendered as `role="listitem"` inside a `role="list"` so
  assistive tech reads them as a list, not unrelated buttons
- status badges are plain text, not icon-only
- disabled call-to-actions set both the `disabled` attribute and
  `aria-disabled="true"`
- the layout collapses to a single column below 600px so touch
  targets stay comfortable
