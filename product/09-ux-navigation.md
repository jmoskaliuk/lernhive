# UX and Navigation

## Navigation model

- left navigation is primary
- top navigation contains only a few essential global actions
- Launcher is not the main navigation
- Launcher is for actions such as create, manage, and configure
- navigation is for reaching content, profile, and areas

## UX principles

- Release 1 stays simple and guided
- mobile-friendly and touch-friendly
- no app dependency in Release 1
- fewer steps and stronger defaults
- clear primary actions
- no hidden complexity for beginners
- guided progression through tours and levels
- explanatory UX for permissions and availability

## LXP navigation

In the LXP Flavour, Explore replaces Dashboard.

Suggested left navigation:
- Explore
- My Learning
- Communities
- Topics
- Reports
- Admin (role-based)

## Action surfaces

- Launcher = global actions
- Context Helper = relevant next step in the current place
- Follow = star action on cards and details
- Bookmark = separate save-for-later action

## Findability contract

LernHive commits to a **page-level findability contract**: on every page, a user of a given role in a given Flavour must be able to answer one clear question within seconds, and the three semantic info slots (Priority / Progress / Explore) always live in the same relative position on that page type.

This contract is **orthogonal to the theme's block regions**. Theme block regions (`content-top`, `content-bottom`, `sidebar-bottom`, `footer-*`) stay technically neutral — they describe *where* a block can be placed, not *what* belongs there. The semantic decision ("this page's Priority slot is at content-top") is pinned one layer above, at the Flavour level, through `local_lernhive_flavour` defaults.

### Semantic slots

Every primary page in LernHive should have a place for each of these four semantic slots. Slots may be empty on a given page, but the position is stable across pages of the same archetype within a Flavour.

- **Priority** — what is urgent or required right now (due dates, assigned items, unread, action-required)
- **Progress** — what is in flight (continue, resume, recently opened, completion in progress)
- **Explore** — what is new or discoverable (recommendations, catalog, new communities, new snacks)
- **Action** — the one dominant primary action on this page

### Page archetypes

These archetypes define the rows of the findability matrix. Every LernHive page maps to exactly one archetype, regardless of Flavour.

- **Entry** — first page after login (Dashboard or Explore depending on Flavour)
- **Learning overview** — "My Learning" / "My Courses" list
- **Course shell** — a single course from the outside (overview, sections, participants)
- **Course content** — inside an activity/module/snack step
- **Discovery** — catalog, search, Explore feed (where applicable)
- **Operate** — admin, reporting, audience, configuration
- **Profile** — personal settings, notifications, preferences

### Findability matrix by Flavour

The matrix is authoritative for all Flavours — current and planned. Cells marked *(same)* inherit from the School Flavour unless the row explicitly overrides them.

#### Entry page

| Flavour           | Primary question                  | Lead role     | Priority slot           | Progress slot            | Explore slot               | Dominant action           |
|-------------------|-----------------------------------|---------------|-------------------------|--------------------------|----------------------------|---------------------------|
| School            | What do I need to do today?       | Student       | Due today / this week   | Continue current lesson  | Class resources            | Open today's class        |
| LXP               | What is interesting to me now?    | Learner       | Assigned / required     | Continue where I left off| New snacks, new communities| Start an Explore item     |
| Higher Education  | What is due this week?            | Student       | Due this week, exams    | Continue current semester course | Announcements      | Open current course       |
| Corporate Academy | What is mandatory for me?         | Employee      | Compliance due          | Continue required training | Recommended for role      | Complete required training|
| Association       | What is new for members?          | Member        | Member-only actions     | Continue learning        | New events, new topics     | Join next event           |

#### Learning overview ("My Learning")

| Flavour           | Primary question                  | Priority slot         | Progress slot           | Explore slot              | Dominant action           |
|-------------------|-----------------------------------|-----------------------|-------------------------|---------------------------|---------------------------|
| School            | Which class do I open?            | Overdue items         | Continue class          | —                         | Open a class              |
| LXP               | Where did I leave off?            | Assigned              | Continue                | Recommended next          | Resume item               |
| Higher Education  | Which course do I open?           | Deadlines this week   | Continue course         | —                         | Open course               |
| Corporate Academy | What still needs completion?      | Compliance due        | In progress             | Recommended for role      | Finish required item      |
| Association       | What am I currently learning?     | Member actions        | Continue                | New for members           | Continue                  |

#### Course shell (outside a course)

Uniform across all Flavours at the archetype level. Course Format plugins (`format_lernhive_snack`, `format_lernhive_community`, `format_topics`) may override the inside-course rendering but must respect the shell contract.

- **Primary question:** What is this course and what do I do next in it?
- **Priority slot:** next deadline, assigned activity, unread announcement
- **Progress slot:** completion, last visited section
- **Explore slot:** related courses, prerequisites, further reading (optional per Flavour)
- **Dominant action:** Continue / Start next activity

#### Course content (inside an activity or snack)

The **focus-first** rule applies across all Flavours: the primary surface is the learning content itself, chrome (sidebar, block regions) collapses or hides when the user enters a content step. Findability at this level is handled by the course format plugin, not the theme; the contract here only guarantees that a user can always answer "where am I in this course?" and "how do I get back out?".

- **Primary question:** Where am I, and how do I get out?
- **Priority slot:** the next required step
- **Progress slot:** "x of y" or completion marker
- **Explore slot:** not shown on content pages — reappears on the shell
- **Dominant action:** Complete and continue

#### Discovery (LXP Explore, catalog views)

Only fully instantiated in the LXP Flavour. Other Flavours expose a reduced catalog view under the same archetype contract.

- **LXP:** Primary question *"What should I learn next?"* — Priority = assigned-to-me, Progress = continue, Explore = recommendations + feed, dominant action = Start or Follow.
- **School / Higher Education / Corporate / Association:** Primary question *"What else is available to me?"* — catalog-only view, no feed, no recommendations in Release 1.

#### Operate (admin, reporting, audience, configuration)

Lead role is Trainer / Admin, not Learner. The contract reuses the same slots but their meaning shifts:

- **Priority slot:** items needing my review (pending enrolments, failed deliveries, compliance gaps)
- **Progress slot:** ongoing cohorts, assignments in progress, reporting period in flight
- **Explore slot:** new templates, new library items, new reports
- **Dominant action:** the most common next admin task for this page (Create / Assign / Generate report)

All Flavours share this row. Flavour-specific wording differences (e.g. "Compliance overview" in Corporate Academy, "Semester report" in Higher Education) are applied through Flavour string overrides, not through structural differences.

#### Profile

Uniform across all Flavours. Profile pages are not findability-driven; they are destination pages reached from the top bar, not from navigation. They have no Priority / Progress / Explore slots.

### How the contract is enforced

- **Theme (`theme_lernhive`)** owns the block regions as neutral containers. It does not decide what goes into them.
- **Flavour (`local_lernhive_flavour`)** owns the mapping from semantic slot to block region for each page archetype. A School Flavour install and an LXP Flavour install can place the Priority slot in different regions, but within one Flavour the placement is stable.
- **Course Format plugins** own the inside-course contract and must respect the shell contract when the user exits into the shell.
- **`local_lernhive_contexthelper`** surfaces the dominant action per page as the Context Helper target where a flat "primary button" is not enough.
- **Review gate:** every new page added to LernHive (theme layout, plugin view, format renderer) must be able to answer the archetype row it belongs to before it ships. Pages that cannot answer it go back to design.

### Out of scope for Release 1

- no personalized or AI-ranked Explore slot
- no cross-page "resume everywhere" surface beyond Continue on the Entry page
- no role-switching UI — the lead role per page is determined by the user's active role, not a chooser
