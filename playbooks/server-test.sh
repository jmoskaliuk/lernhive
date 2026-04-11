#!/usr/bin/env bash
#
# LernHive Hetzner server-side test wrapper.
#
# Installed by playbooks/provision.sh as /usr/local/bin/lernhive-test.
# Called via SSH from GitHub Actions (workflow: .github/workflows/test-hetzner.yml).
#
# Responsibility: update the workspace from git and run playbooks/test.sh.
# Deliberately minimal — all real test logic lives in test.sh so it stays
# testable and reusable.
#
# Usage (typically via SSH):
#   lernhive-test                                            # PHPUnit + Behat, all components
#   lernhive-test --suite=phpunit                            # only PHPUnit
#   lernhive-test --suite=behat --tags=@local_lernhive_contenthub
#   lernhive-test --component=local_lernhive_contenthub      # filter both suites
#   lernhive-test --reinit                                   # force rebuild of test envs
#
# Environment:
#   LERNHIVE_REPO   Absolute path to the lernhive workspace checkout on the
#                   server. Default: /opt/lernhive.
#
set -euo pipefail

REPO="${LERNHIVE_REPO:-/opt/lernhive}"

if [[ ! -d "$REPO/.git" ]]; then
  echo "Error: no git repo at $REPO" >&2
  echo "Set LERNHIVE_REPO or run provision.sh first." >&2
  exit 1
fi

echo "[server-test] updating $REPO from origin/main..."
cd "$REPO"
git fetch --quiet origin main
git reset --hard origin/main

echo "[server-test] invoking playbooks/test.sh --target=hetzner $*"
exec "$REPO/playbooks/test.sh" --target=hetzner "$@"
