#!/usr/bin/env bash
#
# Publish a single test result (PHPUnit or Behat) to the
# ci/test-results branch of this repo.
#
# Background / why this script exists:
#   The Claude Cowork sandbox that drives LernHive development has a
#   narrow network egress allowlist: github.com via git is reachable,
#   but api.github.com, raw.githubusercontent.com and objects.github-
#   usercontent.com are blocked. That means Claude cannot use the
#   GitHub REST API (or gh CLI) to read workflow run logs after a
#   push. To close the feedback loop Claude needs the test output to
#   be published as plain files on a branch it *can* fetch — hence
#   this publish-to-branch approach.
#
# Called from .github/workflows/test-hetzner.yml after each test job
# (phpunit, behat) finishes. Reads the streamed test log from a
# well-known tmp file and commits it — plus a compact summary.json —
# to an orphan branch named ci/test-results.
#
# Layout of the ci/test-results branch after this script runs:
#   runs/latest-<job>.log        Full streamed output of the most
#                                recent run (overwritten each time).
#   runs/latest-<job>.json       Machine-readable summary of the same.
#   runs/archive/<ts>-<sha>-<job>.log
#                                Permanent per-run copy so history is
#                                not lost across overwrites.
#
# Env contract (all required unless marked optional):
#   JOB                   "phpunit" | "behat"
#   SSH_RC                Integer exit code of the lernhive-test SSH run
#   GH_TOKEN              Token with contents:write on the repo (we use
#                         the workflow's GITHUB_TOKEN)
#   GITHUB_REPOSITORY     e.g. "jmoskaliuk/lernhive" — standard GH ctx
#   GITHUB_RUN_ID         GH Actions run id — standard GH ctx
#   GITHUB_RUN_NUMBER     GH Actions run number — standard GH ctx
#   GITHUB_SHA            Commit sha — standard GH ctx
#   GITHUB_REF_NAME       Branch name — standard GH ctx
#   GITHUB_ACTOR          Triggerer login — standard GH ctx
#   GITHUB_EVENT_NAME     e.g. "push" | "workflow_dispatch"
#
# Expected input file: /tmp/test-output-${JOB}.log
#   (written by the preceding "Run … over SSH" step via tee).

set -euo pipefail

: "${JOB:?JOB env var required}"
: "${SSH_RC:?SSH_RC env var required}"
: "${GH_TOKEN:?GH_TOKEN env var required}"
: "${GITHUB_REPOSITORY:?GITHUB_REPOSITORY env var required}"
: "${GITHUB_RUN_ID:?GITHUB_RUN_ID env var required}"
: "${GITHUB_RUN_NUMBER:?GITHUB_RUN_NUMBER env var required}"
: "${GITHUB_SHA:?GITHUB_SHA env var required}"
: "${GITHUB_REF_NAME:?GITHUB_REF_NAME env var required}"
: "${GITHUB_ACTOR:?GITHUB_ACTOR env var required}"
: "${GITHUB_EVENT_NAME:?GITHUB_EVENT_NAME env var required}"

LOG_SRC="/tmp/test-output-${JOB}.log"
if [[ ! -f "$LOG_SRC" ]]; then
  echo "::warning::No log file at $LOG_SRC — writing placeholder"
  printf '(no output captured — SSH step likely did not run)\n' > "$LOG_SRC"
fi

TS=$(date -u +%Y%m%dT%H%M%SZ)
SHORT_SHA="${GITHUB_SHA:0:8}"

# Derive human-readable status from the exit code of playbooks/test.sh:
#   0  success (all green, possibly with warnings that test.sh treated
#      as non-fatal since commit e79dfd6)
#   1  usage / config error in test.sh
#   2  container not running or init failed
#   3  tests ran but failed
case "$SSH_RC" in
  0)  STATUS="passed" ;;
  1)  STATUS="usage_error" ;;
  2)  STATUS="init_failed" ;;
  3)  STATUS="failed" ;;
  *)  STATUS="error" ;;
esac

# "Pass with warnings" is semantically distinct from a clean pass:
# tests all green, but PHPUnit triggered warnings/deprecations. We
# surface this as a boolean so the sandbox-side reader can decide
# whether to investigate without having to parse the log itself.
HAS_WARNINGS="false"
if grep -q 'passed, but phpunit reported warnings' "$LOG_SRC" 2>/dev/null; then
  HAS_WARNINGS="true"
fi

# Best-effort extraction of PHPUnit counts from the final summary
# line ("Tests: 10, Assertions: 51, ..."). Missing or unparseable
# counts are reported as JSON null, never as 0 — 0 would be a lie.
TESTS=""
ASSERTIONS=""
if summary_line=$(grep -oE 'Tests: [0-9]+, Assertions: [0-9]+' "$LOG_SRC" | tail -1); then
  TESTS=$(printf '%s' "$summary_line" | grep -oE 'Tests: [0-9]+' | grep -oE '[0-9]+' || true)
  ASSERTIONS=$(printf '%s' "$summary_line" | grep -oE 'Assertions: [0-9]+' | grep -oE '[0-9]+' || true)
fi

# Work inside a scratch directory so we don't disturb whatever the
# main job checkout contains. All git operations are scoped to $WORK.
WORK=$(mktemp -d)
trap 'rm -rf "$WORK"' EXIT

git -C "$WORK" init -q
git -C "$WORK" remote add origin \
  "https://x-access-token:${GH_TOKEN}@github.com/${GITHUB_REPOSITORY}.git"

# Try to pull the existing branch. On first-ever run it does not
# exist yet; in that case we fall through to creating an orphan.
if git -C "$WORK" fetch -q --depth=1 origin ci/test-results 2>/dev/null; then
  git -C "$WORK" checkout -q -B ci/test-results FETCH_HEAD
else
  echo "ci/test-results branch not found on origin — creating as orphan"
  git -C "$WORK" checkout -q --orphan ci/test-results
  git -C "$WORK" rm -rfq . 2>/dev/null || true
fi

mkdir -p "$WORK/runs/archive"
LOG_FILE="runs/latest-${JOB}.log"
JSON_FILE="runs/latest-${JOB}.json"
ARCHIVE_LOG="runs/archive/${TS}-${SHORT_SHA}-${JOB}.log"

cp "$LOG_SRC" "$WORK/$LOG_FILE"
cp "$LOG_SRC" "$WORK/$ARCHIVE_LOG"

# jq -n is the safe way to emit structured JSON from shell vars
# without quoting landmines. Tests/assertions are numeric-or-null.
jq -n \
  --arg     job           "$JOB" \
  --arg     status        "$STATUS" \
  --argjson exit_code     "$SSH_RC" \
  --argjson has_warnings  "$HAS_WARNINGS" \
  --arg     run_id        "$GITHUB_RUN_ID" \
  --argjson run_number    "$GITHUB_RUN_NUMBER" \
  --arg     sha           "$GITHUB_SHA" \
  --arg     short_sha     "$SHORT_SHA" \
  --arg     ref           "$GITHUB_REF_NAME" \
  --arg     actor         "$GITHUB_ACTOR" \
  --arg     event         "$GITHUB_EVENT_NAME" \
  --arg     timestamp     "$TS" \
  --arg     log_path      "$LOG_FILE" \
  --arg     archive_path  "$ARCHIVE_LOG" \
  --arg     tests_str     "$TESTS" \
  --arg     assertions_str "$ASSERTIONS" \
  '{
    job: $job,
    status: $status,
    exit_code: $exit_code,
    has_warnings: $has_warnings,
    run_id: $run_id,
    run_number: $run_number,
    sha: $sha,
    short_sha: $short_sha,
    ref: $ref,
    actor: $actor,
    event: $event,
    timestamp: $timestamp,
    log_path: $log_path,
    archive_path: $archive_path,
    tests:      (if $tests_str      == "" then null else ($tests_str      | tonumber) end),
    assertions: (if $assertions_str == "" then null else ($assertions_str | tonumber) end)
  }' > "$WORK/$JSON_FILE"

# Commit metadata uses the bot name pattern so GitHub renders a
# neutral avatar rather than attributing the commit to whichever
# person triggered the push.
git -C "$WORK" config user.name  "lernhive-ci[bot]"
git -C "$WORK" config user.email "lernhive-ci@users.noreply.github.com"
git -C "$WORK" add runs/

if git -C "$WORK" diff --cached --quiet; then
  echo "No changes to commit for job=${JOB} — skipping push"
  exit 0
fi

# [skip ci] in the subject prevents the publish commit from
# retriggering test-hetzner.yml on push. Even though ci/test-results
# is outside the paths: filter of test-hetzner.yml, other path-less
# workflows might react otherwise — belt and braces.
git -C "$WORK" commit -q -m "ci(${JOB}): ${STATUS} for ${SHORT_SHA} [skip ci]"

push_once() { git -C "$WORK" push -q origin ci/test-results; }

if ! push_once; then
  echo "Push rejected — fetching, rebasing and retrying once"
  git -C "$WORK" fetch -q origin ci/test-results
  if ! git -C "$WORK" rebase -q origin/ci/test-results; then
    echo "::error::Rebase conflict on ci/test-results — aborting, no force-push"
    git -C "$WORK" rebase --abort || true
    exit 1
  fi
  push_once
fi

echo "Published ${JOB} result: status=${STATUS} exit=${SSH_RC} has_warnings=${HAS_WARNINGS}"
