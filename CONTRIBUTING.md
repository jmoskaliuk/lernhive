# Contributing to LernHive

Thank you for contributing to LernHive.

This repository is the shared product and architecture workspace for LernHive.  
It combines product strategy, UX thinking, plugin architecture, terminology, and DevFlow-based plugin documentation.

Before making changes, please read this guide carefully.

---

## 1. Project mindset

LernHive is:

- a Moodle-based product layer
- an experience layer on top of Moodle
- a modular plugin ecosystem
- not a Moodle fork

Please keep contributions aligned with these principles:

- **reuse Moodle where sensible**
- **do not fork Moodle logic unnecessarily**
- **keep function and design separate**
- **prefer clarity over complexity**
- **document decisions explicitly**
- **avoid hidden terminology drift**

---

## 2. Where to start

If you are new to the project, read in this order:

1. `README.md`
2. `product/00-strategy.md`
3. `product/01-architecture.md`
4. `product/02-plugin-map.md`
5. `product/04-language-guide.md`
6. `product/05-string-inventory.md`
7. `product/06-core-string-reuse-map.md`

Only after that, start working on plugin-specific documentation.

---

## 3. Repository structure

### Product-level documents
Located in `product/`

Use these files for:
- product strategy
- architecture
- plugin boundaries
- roadmap
- terminology
- cross-plugin UX decisions
- open product questions

### Plugin-level documents
Located in `plugins/<pluginname>/`

Each plugin follows the DevFlow structure:

- `00-master.md`
- `01-features.md`
- `02-user-doc.md`
- `03-dev-doc.md`
- `04-tasks.md`
- `05-quality.md`

Use these files for:
- plugin-specific scope
- implementation decisions
- QA
- tasks
- user-facing plugin behaviour

### Mockups
Located in `mockups/`

Use these for:
- UI explorations
- wireframes
- interaction patterns
- layout validation

---

## 4. Contribution types

Typical contributions include:

- clarifying product scope
- refining architecture
- improving plugin boundaries
- extending DevFlow documentation
- improving terminology consistency
- reviewing Moodle core reuse opportunities
- adding UX notes or mockups
- documenting open decisions
- tightening Release 1 vs Release 2 scope

---

## 5. Rules for terminology

Terminology consistency is mandatory.

### Core rules
- English is the canonical language
- Moodle terminology should be reused where it is correct and user-friendly
- LernHive system terms should remain stable across Flavours
- flavour-specific wording changes should be rare and intentional

### Before introducing a new term
Ask:
1. Does Moodle already provide a good term for this?
2. Is this already defined in the language guide?
3. Is this a real new concept, or just a wording preference?
4. Does this affect UX, documentation, code, and marketing?

### If terminology changes
You must also review and update:
- `product/04-language-guide.md`
- `product/05-string-inventory.md`
- `product/06-core-string-reuse-map.md`

Do not rename concepts casually.

---

## 6. Rules for strings

LernHive follows Moodle conventions for strings.

### General rule
Only create plugin-specific strings if:
- Moodle core does not already provide a suitable string
- or LernHive requires a clearly distinct product term

### Before adding a new plugin string
Check:
- can a Moodle core string be reused?
- is the wording semantically correct?
- is the term already in the reuse map?

Relevant references:
- `product/05-string-inventory.md`
- `product/06-core-string-reuse-map.md`

Do not introduce duplicate string concepts across plugins.

---

## 7. Rules for architecture changes

If your change affects:
- more than one plugin
- the product model
- UX principles
- Flavour behaviour
- terminology
- navigation
- notifications
- discovery logic

then you must document it first in `product/`.

Do not hide cross-cutting changes only inside one plugin folder.

---

## 8. Rules for plugin documentation

Each plugin should stay focused.

Before expanding plugin scope, ask:
- is this still one plugin responsibility?
- should this be a separate plugin?
- is this orchestration logic or business logic?
- does this belong in the theme, the plugin, or the product layer?

### Keep DevFlow documents aligned
For each plugin, the following must not drift apart:

- `00-master.md` — overview
- `01-features.md` — what it should do
- `02-user-doc.md` — how it is experienced
- `03-dev-doc.md` — how it is built
- `04-tasks.md` — what is still open
- `05-quality.md` — what is tested and known

A feature is not properly documented if only one of these files changes.

---

## 9. Rules for UX contributions

LernHive UX should stay:

- simple
- guided
- explainable
- responsive
- touch-friendly
- calm
- Moodle-aligned where useful

### Important UX distinction
- **Launcher** = actions
- **Navigation** = access to content

Do not blur these without a product-level discussion.

### LXP-specific UX
The LXP Flavour should remain:
- focused
- lightweight
- content- and community-driven
- not noisy
- not overloaded with social signals

Avoid:
- like-driven patterns
- unnecessary social feed mechanics
- too many feed blocks
- unexplained ranking behaviour

---

## 10. Rules for Release 1 scope

Release 1 should stay disciplined.

When proposing features, always mark them as one of:

- `Release 1`
- `Release 2`
- `Later / backlog`

### General principle
If a feature adds too much complexity, it should probably move to Release 2.

Examples already considered later-phase topics:
- advanced personalization
- learning recommendations
- refined Audience circle UX
- stronger content lifecycle
- advanced discovery intelligence

---

## 11. Branching and pull requests

Recommended Git workflow:

- `main` = stable reviewed state
- use feature branches for changes
- open pull requests for review
- keep pull requests focused
- document reasoning, not just edits

### Suggested branch naming
- `feature/discovery-devflow`
- `feature/language-guide-update`
- `docs/plugin-map-review`
- `ux/explore-mockups`

### Pull request expectations
A good pull request should explain:
- what changed
- why it changed
- which files were updated
- whether terminology changed
- whether this affects Release 1 or Release 2
- whether open questions remain

---

## 12. Issues and labels

Please use issues for:
- open decisions
- architecture questions
- plugin boundary questions
- release planning
- terminology problems
- UX reviews
- backlog ideas

Suggested labels:
- `product`
- `architecture`
- `plugin`
- `ux`
- `language`
- `decision`
- `release-1`
- `release-2`
- `backlog`

---

## 13. Good contribution patterns

Good contributions:
- reduce ambiguity
- simplify plugin scope
- improve reuse of Moodle concepts
- make terminology more consistent
- move hidden assumptions into documentation
- clearly separate current scope and future ideas

Weak contributions:
- invent new terms without checking the guide
- add flavour-specific wording too quickly
- duplicate Moodle concepts unnecessarily
- mix product decisions with low-level implementation details
- expand Release 1 scope without justification

---

## 14. If you are unsure

If you are unsure whether a change belongs in:
- product docs
- plugin docs
- language guide
- mockups
- backlog

prefer to:
1. document the uncertainty
2. open an issue
3. keep the change small and explicit

Do not silently solve conceptual questions in isolation.

---

## 15. Definition of a good contribution

A strong contribution to LernHive should be:

- understandable
- consistent
- minimal
- Moodle-aware
- well documented
- easy to review
- aligned with the product vision

---

## 16. Final reminder

LernHive is not about adding more complexity to Moodle.

It is about making Moodle:
- easier to use
- easier to understand
- easier to adopt
- more flexible through Flavours
- more human in experience

Contribute accordingly.
