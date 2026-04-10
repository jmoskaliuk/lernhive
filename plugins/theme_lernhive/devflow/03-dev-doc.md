# theme_lernhive — Developer Documentation

## Architecture note
LernHive theme implementation target on top of Moodle theme APIs.

## Technical direction
- keep boundaries clean
- use Moodle APIs where possible
- prefer existing core strings over plugin-specific duplicates
- keep data model minimal for release 1
- document release 2 complexity separately

## Theme implementation boundaries
- styling, layout regions, navigation treatment, and component rendering belong here
- business logic remains in LernHive plugins and Moodle core
- theme decisions must support both School and optional LXP usage without splitting into separate products

## Mockup-to-implementation path
- phase 1 defines visual tokens, shells, and key component patterns
- phase 2 maps those decisions to Moodle theme templates, SCSS, renderers, and settings where needed
- implementation should start from stable page shells and reusable components rather than page-by-page exceptions

## Release 1 technical scope
- left navigation as the primary navigation pattern
- utility-focused top bar
- card and panel system for LernHive-specific surfaces
- responsive behavior for desktop and mobile
- flavour-aware styling differences only where the product docs explicitly require them
- Launcher base pattern implemented in the theme as a compact flyout with an optional dock-style enhancement

## Current dependencies
- Moodle theme APIs
- Moodle navigation and rendering system
- LernHive product documentation and plugin output structures

## Integration points
- Moodle core APIs
- Moodle theme configuration and rendering hooks
- LernHive shared services as needed
- theme integration only for styling, not for business logic

## Implementation notes
- prefer Moodle region and renderer extension points over custom structural hacks
- keep component names and tokens consistent so mockups can be translated into SCSS/templates later
- avoid theme features that would force custom plugin behavior just to render correctly
- use Mustache partials for reusable shell pieces such as sidebar, launcher, and cards
- keep Launcher interaction simple and accessible; use lightweight native patterns before adding JS
- prepare generic card partials that plugins can reuse for Explore, reporting tiles, and content surfaces without moving plugin logic into the theme
