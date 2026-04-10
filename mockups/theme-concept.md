# LernHive Theme UX Concept

## Purpose

This concept defines the long-term UX direction for the LernHive theme so mockups, plugin UX, and the later Moodle implementation all move toward one consistent system.

## Core design goal

LernHive should feel calmer, clearer, and more guided than standard Moodle without hiding Moodle core logic behind a completely new product model.

## Brand basis

- use eLeDia core colors and accent colors consistently
- use `Open Sans` as the primary typeface
- keep contrast strong and visual hierarchy clear
- use orange as a deliberate emphasis color, not as background noise

## Long-term UX principles

- left navigation is the primary navigation pattern
- top navigation stays small and utility-focused
- Launcher is an action surface, not a second navigation system
- cards, tiles, and helper panels should feel calm and readable
- the interface should reduce visible complexity before adding new features
- School and optional LXP should feel like one product family, not two disconnected themes

## Information architecture

### Navigation
- left rail for areas and orientation
- top bar for a few global utilities
- contextual actions should live near the relevant content, not in unrelated global menus

### Action surfaces
- Launcher for create/manage/configure actions
- Context Helper for the next useful action in the current place
- card actions for content relationships like Follow and Bookmark

### Launcher pattern
- Launcher should start as a single icon in the left navigation
- clicking the icon should open a small flyout with the most important actions
- for frequent tasks, the launcher may also expose a compact dock-like row of icons
- the launcher must remain action-oriented and must not become a second site navigation
- if too many actions are needed, route the user into a dedicated surface such as ContentHub

## Component system

### Page shell
- stable left rail
- broad readable content area
- generous spacing
- strong content grouping through panels and section headings

### Cards
- white surface
- soft border
- small but clear metadata
- one clear primary call to action
- optional secondary relationship actions

### Buttons
- primary actions should be visually distinct and used sparingly
- secondary actions should stay calm
- utility pills should not compete with core actions
- icon-first actions should still remain understandable and accessible

### Status and emphasis
- orange for primary emphasis and important action cues
- dark blue for navigation and strong structural cues
- light blue and pale orange for soft surfaces and supporting highlights
- gray tones for borders, layout separation, and quiet UI states

## Flavour behavior

### School
- more conventional Moodle orientation
- focus on clarity, onboarding, and guided teaching workflows

### LXP
- Explore can replace Dashboard only in this Flavour
- feed remains slim, explainable, and not socially noisy
- relationship actions like Follow and Bookmark are prominent but still calm

## Accessibility direction

- strong contrast must be preserved with the eLeDia palette
- focus states should be visible and consistent
- the theme should not rely on color alone for meaning
- spacing and typography should support zoom, reflow, and touch usage

## Implementation goal for Moodle

- create a reusable token layer for colors, spacing, radii, and shadows
- map the shell and components to Moodle theme regions and templates
- avoid theme behavior that forces plugin-side logic changes
- keep the visual system reusable across plugin screens, not page-specific only

## Success criteria

- a user understands where they are within a few seconds
- primary actions are visible without creating visual overload
- the interface feels recognizably eLeDia and recognizably LernHive at the same time
- mockups can be translated into Moodle theme code with minimal conceptual drift
