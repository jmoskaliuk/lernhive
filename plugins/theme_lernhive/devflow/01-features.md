# theme_lernhive — Features

## Feature summary
LernHive visual layer and design system for a calm, guided Moodle experience.

## Planned feature set
- design tokens for color, typography, spacing, and surfaces
- responsive layout with a left-oriented navigation model
- clear card patterns for Explore, reporting, and content entry points
- touch-friendly UI with simple actions and readable spacing
- distinct treatment for action surfaces such as Launcher and Context Helper
- reusable Explore shell pieces for the optional LXP Flavour, without moving discovery logic into the theme
- reusable ContentHub shell pieces that keep Copy, Template, and Library visually aligned but conceptually separate

## Theme rules
- the theme does not contain business logic
- Moodle core strings are reused where suitable
- LernHive product terms stay stable and English-first
- Launcher is action-oriented, not full navigation
- Explore replaces Dashboard only in the optional LXP Flavour

## Release 1 UX scope
- left navigation is primary
- top navigation stays small and utility-focused
- cards stay calm and easy to scan
- mobile behavior stays responsive and touch-friendly
- visual hierarchy should reduce Moodle complexity rather than add more options
- Explore presentation should support the fixed Release 1 feed blocks and explainable ranking hints
- ContentHub presentation should keep orchestration separate from Copy and Library implementation details

## Mockup targets
- global shell for desktop and mobile
- navigation model for School and LXP contexts
- Explore cards and feed presentation
- Launcher panel and action grouping
- ContentHub entry screen
- reporting tiles and calm dashboard treatment

## Acceptance direction
- feature behaviour should be explicit and understandable
- strings should reuse Moodle core when possible
- UX should stay simple and mobile-friendly
- flavour-specific wording should be used only if really necessary
- visual design should make the product feel calmer and clearer than standard Moodle without forking Moodle behavior

## Release note
Target release: R1
