# local_lernhive_library — User Documentation

## User value
External managed content access for LernHive customers.

## What the user should experience
- simple, clear labels
- minimal steps
- no hidden complexity
- terminology that also works in product communication

## Main user journeys
- open the feature from the relevant LernHive entry point
- browse managed catalog entries from the remote feed (title, description, version, language, updated date)
- optionally power template selection in `local_lernhive_copy` when entries define `sourcecourseid`
- see a clear empty state if no valid remote/fallback data is configured yet
- prepare import decisions (actual import action follows in later R2 phase)

## What changes next (R2 phase 3)
- users can trigger controlled import execution from the catalog flow
- update-relevant version hints are shown before import decisions
