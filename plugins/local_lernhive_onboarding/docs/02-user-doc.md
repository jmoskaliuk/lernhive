# local_lernhive_onboarding - User Documentation

## Who this is for

Current primary audience:
- users assigned the `lernhive_trainer` system role
- users with `local/lernhive_onboarding:receivelearningpath`

## What users can do today (0.2.8)

1. See a dashboard banner on `/my/` while Level 1 is incomplete
2. Open the onboarding catalog (`/local/lernhive_onboarding/tours.php`)
3. Start or restart tours from category cards
4. Track completion per category and across the current level

## User journey

1. User opens dashboard and sees the learning-path banner
2. User opens "Learning Path"
3. User expands a category and starts a tour
4. User is redirected to the exact target page
5. Tour auto-plays and progress updates after completion
6. After tour end, an overlay offers:
   - return to onboarding overview
   - stay on current page

## Current constraints

- Level-1 pack is the only fully wired runtime pack
- Level unlock button is currently a visual affordance, not a complete self-service level-up flow
- Some advanced multi-page flows are still single-tour placeholders until chaining is implemented

## Support notes

- If a tour opens on a wrong or invalid target, verify plugin settings and placeholder config first
- If sandbox course was deleted, run upgrade to reprovision and restore `{DEMOCOURSEID}` targets
