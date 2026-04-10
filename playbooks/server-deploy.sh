#!/usr/bin/env bash
#
# LernHive Hetzner server-side deploy wrapper.
#
# Installed by playbooks/hetzner-setup.sh as /usr/local/bin/lernhive-deploy.
# Called via SSH from GitHub Actions (workflow: .github/workflows/deploy-hetzner.yml).
#
# Responsibility: update the workspace from git and run playbooks/deploy.sh.
# Deliberately minimal — all real deploy logic lives in deploy.sh so it stays
# testable and reusable.
#
# Usage (typically via SSH):
#   lernhive-deploy                                   # deploy all plugins
#   lernhive-deploy --plugin=local_lernhive_onboarding # single plugin
#
# Environment:
#   LERNHIVE_REPO   Absolute path to the lernhive workspace checkout on the
#                   server. Default: /opt/lernhive.
#
set -euo pipefail

REPO="${LERNHIVE_REPO:-/opt/lernhive}"

if [[ ! -d "$REPO/.git" ]]; then
  echo "Error: no git repo at $REPO" >&2
  echo "Set LERNHIVE_REPO or run hetzner-setup.sh first." >&2
  exit 1
fi

echo "[server-deploy] updating $REPO from origin/main..."
cd "$REPO"
git fetch --quiet origin main
git reset --hard origin/main

echo "[server-deploy] invoking playbooks/deploy.sh --target=hetzner $*"
exec "$REPO/playbooks/deploy.sh" --target=hetzner "$@"
