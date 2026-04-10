# theme_lernhive — Tasks

## Phase 1 tasks
- define the visual direction for LernHive as a mockup system
- confirm navigation behavior for left-oriented layouts
- define action surface patterns for Launcher and Context Helper
- define card styles for Explore, reporting, and content entry points
- define responsive behavior for desktop and mobile
- prepare mockup-ready tokens, page shells, and component rules

## Phase 2 tasks
- map approved mockup decisions to Moodle theme structure
- define template, SCSS, and renderer responsibilities
- prepare implementation tickets for the final `theme_lernhive` plugin
- stabilize reusable Mustache partials for sidebar, launcher, and cards
- decide where Lucide-compatible icon rendering should be introduced in the real implementation
- test compact flyout and optional dock launcher patterns against Moodle navigation output
- connect the new Explore shell components to real `local_lernhive_discovery` output without hardcoding feed data in the theme
- connect a real ContentHub screen to the new orchestration shell without re-merging Copy, Template, and Library responsibilities
- validate the new course layout against standard Moodle course pages and later against Snack-specific constraints

## Open questions
- exact Moodle theme extension points for the left navigation treatment
- which page shells need custom treatment first
- how much flavour-specific visual variation is useful without creating drift
- whether icon rendering should stay text-placeholder-based in the first technical iteration or move to a proper pix/icon mapping next

## Next step
Turn the scaffold into a reusable component system and then connect those components to real Moodle and LernHive screen outputs.
