# AGENTS.md

## Project

LernHive is a Moodle-based product layer and plugin ecosystem.

This repository is not just a code repository. It is a shared product, UX, architecture, terminology, and documentation workspace for LernHive.

LernHive improves Moodle without forking Moodle core.

It combines:
- product strategy
- plugin architecture
- DevFlow documentation
- UX and terminology guidance
- mockups
- implementation preparation

This repository should help humans and coding agents work consistently on the same product vision.

---

## Core project identity

LernHive is:

- a Moodle-based product layer
- an experience layer on top of Moodle
- a modular plugin ecosystem
- a SaaS-first product concept
- a system with optional Flavours
- not a Moodle fork

LernHive should make Moodle:
- easier to understand
- easier to adopt
- easier to configure
- more guided in UX
- more flexible through Flavours
- stronger in both classic LMS and optional LXP scenarios

---

## Non-negotiable principles

Always preserve these principles:

1. **No Moodle fork**
   - Moodle remains the technical core.
   - Do not redesign the product as a replacement for Moodle.
   - Do not assume custom core patches unless explicitly documented.

2. **Function and design are separate**
   - Functional behavior belongs in plugins.
   - UX, layout, and visual behavior belong in the theme and UX layer.
   - Do not move business logic into the theme.

3. **English first**
   - Canonical language is English.
   - All terminology should be defined in English first.
   - Default plugin language should follow Moodle conventions.

4. **Moodle first where sensible**
   - Reuse Moodle concepts, strings, roles, and capabilities when they are semantically correct and UX-appropriate.
   - Do not invent new concepts where Moodle already provides a strong foundation.

5. **Simple UX first**
   - Favor clarity over cleverness.
   - Favor fewer visible choices over technical completeness.
   - Favor guided flows over expert overload.

6. **Release 1 must stay disciplined**
   - Do not silently move later ideas into Release 1.
   - Keep advanced personalization, refined Audience UX, advanced lifecycle/versioning, and more speculative systems in later phases unless the documentation explicitly changes.

---

## Product model

### Moodle core
Moodle provides the technical foundation:
- courses
- activities
- groups
- enrolment
- roles and capabilities
- reporting
- notifications
- competencies
- learning plans
- analytics
- language string infrastructure

### LernHive layer
LernHive adds an experience and orchestration layer:
- Flavours
- level-based onboarding and complexity reduction
- Launcher
- Context Helper
- ContentHub
- Explore (LXP Flavour only)
- Audience UX and rule system
- reporting UX
- configuration tracking
- digest logic for LXP notifications

### Flavours
Flavours are recommended starting configurations, not hardcoded permanent product modes.

Current priority Flavours:
- School
- LXP

Important Flavour rule:
- Flavours are best-guess starting points
- systems can be adapted afterwards
- changes should be documented through configuration tracking
- flavour-specific terminology changes should be minimal and intentional

---

## Canonical current decisions

These decisions are already made and should be treated as authoritative unless explicitly changed in product docs.

### Terminology
- Use **Explore**, not **Discover**
- Use **Audience** as a stable system term
- Use **Follow** as the system concept
- Use **Bookmark** / "Save for later" as separate from Follow
- Use **ContentHub** as the central content access/create concept
- Use **Snack** as a stable LernHive term
- Use **Community** as a stable LernHive term
- Use **Launcher** as a stable LernHive term

### UX decisions
- Follow should appear directly on a content card
- Preferred interaction icon for Follow is **star**
- Bookmark is separate from Follow
- Explore replaces the dashboard only in the **LXP Flavour**
- Launcher is for actions, not full navigation
- Main navigation is primarily left-based
- Top navigation should contain only a few important actions
- Mobile must be responsive and touch-friendly, but no app-first scope in Release 1

### Discovery / Explore
- Explore is only active in the LXP Flavour
- Explore shows:
  - Courses
  - Snacks
  - Communities
- Explore should remain calm, simple, and not social-noisy
- Feed should remain slim and clear
- Current feed logic includes:
  - New in your community
  - New in content you follow
  - Popular snacks
  - Explore
- Ranking should be:
  - audience/community-first
  - snack-friendly
  - based on recent relevance (last 7 days)
  - explainable
- No ranking based on likes or meaningless clicks

### Follow / Bookmark
- Enrolment should automatically trigger Follow
- Bookmark is always explicit
- Do not auto-bookmark content
- Follow means updates and relevance
- Bookmark means save for later only

### Notifications
- Reuse Moodle core notification and messaging architecture wherever possible
- Extend only where necessary
- LernHive adds digest logic for LXP-relevant events
- Default notification logic:
  - Community updates → digest
  - Followed content updates → digest
- Avoid noisy micro-events
- Do not build a fully separate notification platform if Moodle core already provides the mechanism

### Audience
- Audience should build on Moodle structures rather than replacing them
- Initial rule types:
  - time-based
  - activity-based
  - profile-based
- Logic should support:
  - AND
  - OR
- Refined Circle UX is a later-phase topic

### Snacks
- Snacks are lightweight and user-generated
- Expected duration: 10–30 minutes
- Release 1 constraints:
  - no course sections
  - no right-side course navigation
  - wizard-only creation
  - limited structure
  - ideally not more than three activities

### Reporting
- Reporting should build on Moodle reporting and analytics rather than replacing them
- MVP report priorities:
  1. How many users are in my course?
  2. What are the most popular courses?
  3. Course completion report
- Inactive users is a valid extension candidate

### Roles
- Stay close to Moodle role logic
- Do not create an unnecessary new role system
- If labels differ in UX, keep the underlying role model aligned with Moodle

---

## Documentation hierarchy

When making decisions or edits, use this hierarchy.

### Highest-level repo guidance
1. `README.md`
2. `CONTRIBUTING.md`
3. `AGENTS.md`

### Product source of truth
1. `product/00-strategy.md`
2. `product/01-architecture.md`
3. `product/02-plugin-map.md`
4. `product/03-roadmap.md`
5. `product/07-next-steps.md`
6. `product/08-backlog.md`
7. `product/09-ux-navigation.md`

### Language source of truth
1. `product/04-language-guide.md`
2. `product/05-string-inventory.md`
3. `product/06-core-string-reuse-map.md`

### Plugin source of truth
Each plugin folder in `plugins/<pluginname>/` must use:
- `00-master.md`
- `01-features.md`
- `02-user-doc.md`
- `03-dev-doc.md`
- `04-tasks.md`
- `05-quality.md`

### Mockups
Use `mockups/` only to visualize or test documented product decisions.
Do not let mockups introduce product concepts that are undocumented elsewhere.

---

## Repository structure expectations

The repository is expected to contain three major layers:

### `product/`
Cross-plugin and product-level truth.
Use this for:
- strategy
- architecture
- roadmap
- language rules
- string policy
- UX and navigation principles
- backlog
- product-wide open decisions

### `plugins/`
Plugin-specific DevFlow documentation.
Use this for:
- plugin scope
- user experience of the plugin
- implementation details
- plugin tasks
- QA and known issues

### `mockups/`
HTML or markdown wireframes and interaction explorations.
Use this for:
- validating UX decisions
- showing expected behavior
- design discussion support

Do not duplicate product decisions inside mockups without aligning product docs.

---

## DevFlow rules

Every plugin follows the eLeDia OS DevFlow model.

For every plugin:
- `00-master.md` explains what it is
- `01-features.md` defines what it should do
- `02-user-doc.md` explains how users experience it
- `03-dev-doc.md` explains how it is built
- `04-tasks.md` lists active work and open questions
- `05-quality.md` documents QA, tests, and known issues

### DevFlow consistency rule
If one file changes in a meaningful way, check whether the corresponding other files also need updates.

Examples:
- If a feature is added in `01-features.md`, check `02-user-doc.md`, `03-dev-doc.md`, `04-tasks.md`, and `05-quality.md`
- If a terminology decision changes, update product language documents and affected plugin docs
- If release scope changes, reflect it in roadmap and relevant plugin DevFlows

Do not leave DevFlow files inconsistent.

---

## Plugin boundary rules

LernHive uses multiple plugins on purpose.

Do not merge concepts into one plugin just because they are visually related.

### Preferred pattern
- orchestration UI may sit in one plugin
- distinct business domains should remain separate where practical

Example:
- ContentHub may orchestrate content creation entry points
- copy, library, and AI-backed creation should remain separate responsibilities if their logic differs

Before broadening a plugin, ask:
- is this still one responsibility?
- should this be a separate plugin?
- does this belong in the product layer instead?
- is this theme logic, plugin logic, or orchestration logic?

When in doubt, prefer clear plugin boundaries over giant catch-all plugins.

---

## Terminology rules

Terminology consistency is mandatory.

### Core rules
- English is canonical
- Moodle terminology should be reused when semantically correct and UX-appropriate
- LernHive product terms should stay stable across Flavours where possible
- Flavour-specific wording changes should be minimal and carefully justified

### Terms that should remain stable
- Explore
- Audience
- Follow
- Bookmark
- ContentHub
- Snack
- Community
- Launcher

### Avoid
- Discover
- duplicate synonyms for the same concept
- highly technical UI wording where a simpler product term exists
- casual flavour-based renaming of the same concept

### If terminology changes
Always review and update:
- `product/04-language-guide.md`
- `product/05-string-inventory.md`
- `product/06-core-string-reuse-map.md`

Do not rename concepts casually.

---

## String rules

LernHive follows Moodle string conventions.

### String policy
Before creating a plugin-specific string:
1. Check whether Moodle core already provides a suitable string
2. Confirm that the core string is semantically correct for UX
3. Only create a new plugin string if:
   - no suitable Moodle core string exists
   - or LernHive requires a distinct product term

### Canonical references
Use:
- `product/05-string-inventory.md`
- `product/06-core-string-reuse-map.md`

### Do not
- duplicate Moodle core strings unnecessarily
- create multiple string identifiers for the same concept across plugins
- introduce terminology that contradicts the language guide

---

## Release rules

### Release 1
Must stay simple, focused, and deliverable.

Release 1 includes:
- core UX and theme
- level system
- launcher
- onboarding
- ContentHub
- copy workflow
- managed library delivery
- School Flavour
- LXP Flavour with Explore
- basic Audience rules
- basic reporting dashboard
- Moodle-based notifications with digest extension
- stable terminology and string reuse model

### Release 2 and later
Includes more advanced or refined systems:
- stronger personalization
- learning/recommendation system
- refined Audience circle UX
- stronger content versioning UX
- expanded reporting
- content lifecycle improvements
- more intelligent discovery ranking

### Rule
Do not move Release 2 ideas into Release 1 unless product docs explicitly change.

If in doubt, keep scope out of Release 1 and document it in backlog or next steps.

---

## UX rules

LernHive UX should remain:
- simple
- guided
- explainable
- calm
- responsive
- touch-friendly
- Moodle-aware where useful

### Navigation rule
- Navigation is mainly for accessing content
- Launcher is for actions
- Do not blur these without product-level documentation changes

### Explore / LXP rule
The LXP Flavour should feel meaningfully different from a course list, but should not become a noisy social feed.

Avoid:
- like-driven mechanics
- social-media-style noise
- too many blocks on the home screen
- unexplained ranking
- complex personalization in Release 1

### Snack rule
Snacks must stay clearly short-form and expectation-safe.
Do not allow Snacks to drift into full course complexity without explicit product change.

---

## Notification rules

Use Moodle’s existing notification architecture wherever possible.

### Reuse first
Use Moodle core for:
- notification providers
- user preferences
- channel settings
- admin defaults

### Extend only where needed
LernHive should add digest/frequency logic for LXP-relevant events where Moodle core does not already provide the exact required pattern.

### Keep notifications calm
Good notifications:
- new Snack in my community
- relevant updates in content I follow
- meaningful community changes

Bad notifications:
- likes
- meaningless clicks
- low-value social noise

---

## Reporting rules

Build on Moodle reporting and analytics rather than replacing them.

When working on reporting:
- prioritize simple questions with clear value
- prefer dashboard tiles + detail drilldown
- avoid starting with broad reporting ambition

Current MVP reporting priorities:
1. users in course
2. popular courses
3. completion report

If additional reporting is proposed, classify it clearly as Release 1 or later.

---

## Performance and feasibility rules

Be aware of feasibility, but do not overdesign prematurely.

### Acceptable assumptions
- Moodle itself is already a strong baseline
- release scope is intentionally simplified
- simple ranking and digest logic are acceptable

### Avoid
- speculative architecture bloat
- solving future scale problems before product shape is validated
- introducing heavy new systems without product justification

If performance is a concern, document it in:
- plugin `04-tasks.md`
- plugin `05-quality.md`
- product `07-next-steps.md` or `08-backlog.md` if cross-cutting

---

## Working behavior for coding agents

When making changes:

1. Read the relevant high-level files first
2. Prefer updating existing files over creating duplicates
3. Keep changes focused and reviewable
4. Do not silently reinterpret product decisions
5. If a concept is unclear:
   - document the uncertainty
   - do not invent a speculative final answer
6. If changing cross-plugin logic:
   - update product docs first or in the same change
7. If changing terminology:
   - update language and string files too
8. If changing plugin scope:
   - check plugin map and DevFlow alignment

### Preferred behavior
- small, explicit changes
- low-risk improvements
- consistency over novelty
- documented open questions instead of hidden assumptions

### Avoid
- broad speculative rewrites
- introducing undocumented new concepts
- changing release boundaries without documentation
- creating redundant files because the repo was not read carefully

---

## Typical good tasks for a coding agent

Examples of useful tasks:
- normalize and align product docs
- fill missing DevFlow files
- improve one plugin’s DevFlow set
- create implementation-ready string guidance
- update mockups to match product decisions
- check terminology consistency across repo
- add missing placeholders where structure is incomplete

Examples of poor tasks:
- “rewrite the whole repo”
- “invent the missing product strategy”
- “merge all plugin concepts into one system”
- “replace Moodle terminology broadly”
- “turn Release 2 ideas into Release 1 silently”

---

## If you are unsure

If uncertain whether something belongs in:
- product docs
- plugin docs
- language guide
- backlog
- roadmap
- mockups

then:
1. keep the change small
2. document the uncertainty
3. do not hide conceptual decisions inside low-level docs

Prefer an explicit open question over a silent wrong assumption.

---

## Final reminder

LernHive is not about making Moodle bigger.

It is about making Moodle:
- easier to start with
- easier to navigate
- easier to understand
- better guided
- more flexible through Flavours
- stronger in UX without breaking Moodle conventions

Contribute accordingly.
