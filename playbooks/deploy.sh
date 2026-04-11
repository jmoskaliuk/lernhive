#!/usr/bin/env bash
#
# LernHive generic deploy script.
#
# Deploys every plugin under plugins/<name>/ that ships a version.php into a
# running Moodle Docker container. Target-specific values (container name,
# Moodle root, …) live in sibling .env files (deploy.local.env / deploy.hetzner.env),
# so the same script works on a developer laptop (OrbStack) and on the
# Hetzner production server without conditional branches.
#
# Usage:
#   playbooks/deploy.sh --target=local
#   playbooks/deploy.sh --target=hetzner --plugin=local_lernhive_onboarding
#   playbooks/deploy.sh --target=local --no-upgrade --no-purge
#
# Exit codes:
#   0  success
#   1  usage / config error
#   2  container not running
#   3  plugin not found / invalid type
#
set -euo pipefail

# ---------------------------------------------------------------------------
# Locate repo root (one level above this script).
# ---------------------------------------------------------------------------
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$ROOT"

# ---------------------------------------------------------------------------
# Arg parsing.
# ---------------------------------------------------------------------------
TARGET=""
PLUGIN_FILTER=""
DO_UPGRADE=1
DO_PURGE=1

usage() {
  cat <<EOF
Usage: playbooks/deploy.sh --target=<local|hetzner> [options]

Options:
  --target=ENV         Deploy target. Loads playbooks/deploy.ENV.env. Required.
  --plugin=NAME        Only deploy this plugin (e.g. local_lernhive_onboarding).
                       Repeat flag not supported — use multiple invocations.
  --no-upgrade         Skip admin/cli/upgrade.php.
  --no-purge           Skip admin/cli/purge_caches.php.
  -h, --help           Show this help and exit.

Plugins are auto-discovered under plugins/<name>/ (must contain version.php).
Subfolder plugins/<name>/docs/ is always excluded from the deploy payload.
EOF
}

for arg in "$@"; do
  case "$arg" in
    --target=*)    TARGET="${arg#--target=}" ;;
    --plugin=*)    PLUGIN_FILTER="${arg#--plugin=}" ;;
    --no-upgrade)  DO_UPGRADE=0 ;;
    --no-purge)    DO_PURGE=0 ;;
    -h|--help)     usage; exit 0 ;;
    *)             echo "Unknown argument: $arg" >&2; usage; exit 1 ;;
  esac
done

if [[ -z "$TARGET" ]]; then
  echo "Error: --target is required" >&2
  usage
  exit 1
fi

CONF="$SCRIPT_DIR/deploy.$TARGET.env"
if [[ ! -f "$CONF" ]]; then
  echo "Error: no config file found at $CONF" >&2
  exit 1
fi

# shellcheck disable=SC1090
source "$CONF"

# Required config vars.
: "${CONTAINER:?CONTAINER not set in $CONF}"
: "${MOODLE_ROOT:?MOODLE_ROOT not set in $CONF (path to Moodle docroot inside container)}"
: "${MOODLE_CLI_ROOT:?MOODLE_CLI_ROOT not set in $CONF (path from which admin/cli/*.php runs)}"
MOODLE_USER="${MOODLE_USER:-www-data}"

# ---------------------------------------------------------------------------
# Helpers.
# ---------------------------------------------------------------------------
log()  { printf '\033[1;34m[deploy]\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m[deploy]\033[0m %s\n' "$*" >&2; }
err()  { printf '\033[1;31m[deploy]\033[0m %s\n' "$*" >&2; }

in_container() {
  # Run as Moodle user (for PHP CLI).
  docker exec -u "$MOODLE_USER" -i "$CONTAINER" "$@"
}

in_container_root() {
  # Run as root (for file ops that need ownership changes).
  docker exec -i "$CONTAINER" "$@"
}

container_check() {
  if ! docker ps --format '{{.Names}}' | grep -qx "$CONTAINER"; then
    err "Container '$CONTAINER' is not running on this host."
    err "Check 'docker ps' and verify CONTAINER in $CONF."
    exit 2
  fi
}

# Map Moodle frankenstyle component → in-Moodle subpath.
# Accepts anything following type_shortname convention.
component_to_path() {
  local comp="$1"
  local type rest
  type="${comp%%_*}"
  rest="${comp#*_}"

  # Guard against malformed names (no underscore).
  if [[ "$type" == "$comp" ]]; then
    return 1
  fi

  case "$type" in
    local)    echo "local/$rest" ;;
    theme)    echo "theme/$rest" ;;
    mod)      echo "mod/$rest" ;;
    block)    echo "blocks/$rest" ;;
    auth)     echo "auth/$rest" ;;
    enrol)    echo "enrol/$rest" ;;
    filter)   echo "filter/$rest" ;;
    qtype)    echo "question/type/$rest" ;;
    qformat)  echo "question/format/$rest" ;;
    qbehaviour) echo "question/behaviour/$rest" ;;
    report)   echo "report/$rest" ;;
    repository) echo "repository/$rest" ;;
    tool)     echo "admin/tool/$rest" ;;
    format)   echo "course/format/$rest" ;;
    gradeexport) echo "grade/export/$rest" ;;
    gradeimport) echo "grade/import/$rest" ;;
    gradereport) echo "grade/report/$rest" ;;
    *)        return 1 ;;
  esac
}

# Auto-discover plugins: any plugins/*/ that contains a version.php.
discover_plugins() {
  local d name
  for d in plugins/*/; do
    [[ -d "$d" ]] || continue
    [[ -f "${d}version.php" ]] || continue
    name="$(basename "$d")"
    printf '%s\n' "$name"
  done
}

# Safety check — never wipe a suspiciously broad path.
is_safe_wipe_path() {
  local p="$1"
  case "$p" in
    "" | "/" | "/var" | "/var/www" | "/var/www/html" | "$MOODLE_ROOT" | "$MOODLE_CLI_ROOT")
      return 1 ;;
  esac
  # Must have at least two path segments after MOODLE_ROOT.
  local rel="${p#"$MOODLE_ROOT"/}"
  if [[ "$rel" == "$p" || "$rel" != */* ]]; then
    return 1
  fi
  return 0
}

# Sync one plugin into the container.
deploy_plugin() {
  local comp="$1"
  local src="plugins/$comp"
  local target_sub target_abs

  if [[ ! -f "$src/version.php" ]]; then
    warn "skip $comp — no version.php (docs-only scaffold?)"
    return 0
  fi

  if ! target_sub="$(component_to_path "$comp")"; then
    err "skip $comp — unknown plugin type (first segment of name)"
    return 0
  fi
  target_abs="$MOODLE_ROOT/$target_sub"

  if ! is_safe_wipe_path "$target_abs"; then
    err "refusing to deploy $comp — unsafe target path: $target_abs"
    exit 3
  fi

  log "  → $comp  →  $target_abs"

  # Ensure target exists.
  in_container_root mkdir -p "$target_abs"

  # Wipe existing contents so removed files actually disappear.
  # (Keeps the dir itself, only removes children.)
  in_container_root find "$target_abs" -mindepth 1 -delete 2>/dev/null || true

  # Tar the source contents (not the dir itself) and pipe into container.
  tar -cf - \
    --exclude='docs' \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='.DS_Store' \
    --exclude='*.swp' \
    --exclude='*.bak' \
    -C "$src" . \
  | in_container_root tar -xf - -C "$target_abs"

  # Ensure Moodle user owns the files.
  in_container_root chown -R "$MOODLE_USER":"$MOODLE_USER" "$target_abs"
}

run_moodle_upgrade() {
  log "Running admin/cli/upgrade.php..."
  if ! in_container php "$MOODLE_CLI_ROOT/admin/cli/upgrade.php" --non-interactive --no-cli-maintenance; then
    warn "upgrade.php returned non-zero (ok if no DB changes)"
  fi
}

run_purge_caches() {
  log "Running admin/cli/purge_caches.php..."
  if ! in_container php "$MOODLE_CLI_ROOT/admin/cli/purge_caches.php"; then
    warn "purge_caches.php returned non-zero"
  fi
}

# ---------------------------------------------------------------------------
# Main.
# ---------------------------------------------------------------------------
log "target = $TARGET   container = $CONTAINER   MOODLE_ROOT = $MOODLE_ROOT"
container_check

mapfile -t ALL_PLUGINS < <(discover_plugins)

if [[ ${#ALL_PLUGINS[@]} -eq 0 ]]; then
  warn "No plugins with version.php found under plugins/*/ — nothing to deploy."
  exit 0
fi

if [[ -n "$PLUGIN_FILTER" ]]; then
  # Verify the filter matches an actual discovered plugin.
  FOUND=0
  for p in "${ALL_PLUGINS[@]}"; do
    if [[ "$p" == "$PLUGIN_FILTER" ]]; then
      FOUND=1
      break
    fi
  done
  if [[ "$FOUND" -eq 0 ]]; then
    err "Plugin '$PLUGIN_FILTER' not found (or has no version.php)."
    err "Available: ${ALL_PLUGINS[*]}"
    exit 3
  fi
  PLUGINS=("$PLUGIN_FILTER")
else
  PLUGINS=("${ALL_PLUGINS[@]}")
fi

log "Deploying ${#PLUGINS[@]} plugin(s): ${PLUGINS[*]}"

for comp in "${PLUGINS[@]}"; do
  deploy_plugin "$comp"
done

if [[ "$DO_UPGRADE" -eq 1 ]]; then
  run_moodle_upgrade
fi

if [[ "$DO_PURGE" -eq 1 ]]; then
  run_purge_caches
fi

log "Done."
