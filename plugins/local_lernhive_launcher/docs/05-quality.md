# local_lernhive_launcher — Quality

## Quality goals
- terminology is consistent
- UX stays simple
- strings are reusable and localizable
- feature works on desktop and smaller screens
- no unnecessary duplication of Moodle core logic
- role-aware visibility is predictable and explainable

## Checks
- accessibility basics
- responsive checks
- role/permission checks where relevant
- language string review

## Release 1 QA focus
- launcher opens from the intended global trigger on desktop and mobile
- visible actions match role and capability expectations
- hidden actions do not leave broken or unreachable gaps in the user flow
- `ContentHub`, `Reports`, `Snack`, and `Community` labels follow canonical product terminology
- `Reports` appears only when `local_lernhive_reporting` is available and the user has `local/lernhive_reporting:view`
- generic terms reuse Moodle core strings where suitable
- keyboard interaction and focus handling work for open, close, and action selection

## Known validation needs
- confirm the launcher still feels lightweight when multiple optional plugins are enabled
- confirm action grouping remains understandable for beginner-level users
- confirm launcher behaviour does not depend on the LernHive theme for functional correctness
