# Core String Reuse Map

## Rule

Only add plugin language strings when no suitable Moodle core string exists.

## Reuse from core

| Concept | Suggested call |
|---|---|
| Home | `get_string('home', 'moodle')` |
| Dashboard | `get_string('dashboard', 'moodle')` |
| Search | `get_string('search', 'moodle')` |
| Notifications | `get_string('notifications', 'message')` or matching existing component |
| Course | `get_string('course')` |
| Courses | `get_string('courses')` |
| Activity | `get_string('activity')` |
| Section | `get_string('section')` |
| Participants | `get_string('participants')` |
| Import | `get_string('import')` |
| Export | `get_string('export')` |
| Course settings | `get_string('settings')` or dedicated course setting string where the exact context fits |
| Teacher | reuse Moodle role string where shown |
| Student | reuse Moodle role string where shown |
| Administrator | reuse Moodle core |
| Overview | reuse Moodle core |
| Progress | reuse Moodle core |
| Report | reuse Moodle core |

## Add LernHive strings

| Concept | Component |
|---|---|
| Explore | `local_lernhive_discovery` |
| Grow | `local_lernhive_onboarding` |
| Follow | `local_lernhive_follow` |
| Bookmark / Save for later | `local_lernhive_follow` |
| Launcher | `local_lernhive_launcher` |
| Context Helper | `local_lernhive_contexthelper` |
| ContentHub | `local_lernhive_contenthub` |
| Community | `local_lernhive_discovery` |
| Snack | `local_lernhive_discovery` |
| Template | `local_lernhive_contenthub` |
| Library | `local_lernhive_library` |
| Audience | `local_lernhive_audience` |
| Daily digest | `local_lernhive_notifications` |
| Weekly digest | `local_lernhive_notifications` |

## Development note

When implementing a screen, first inspect the core string library and Moodle language customization tools before introducing a new plugin string.
