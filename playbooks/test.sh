#!/usr/bin/env bash
#
# LernHive Moodle test runner.
#
# Runs PHPUnit and/or Behat inside the LernHive Moodle container on a
# given target. Target-specific values (container name, Moodle root,
# Behat wwwroot, Selenium URL, …) live in sibling .env files
# (test.local.env / test.hetzner.env), so the same script works on a
# developer laptop (OrbStack) and on Hetzner without conditional
# branches.
#
# Usage:
#   playbooks/test.sh --target=hetzner
#   playbooks/test.sh --target=hetzner --suite=phpunit
#   playbooks/test.sh --target=hetzner --suite=behat --tags=@local_lernhive_contenthub
#   playbooks/test.sh --target=hetzner --component=local_lernhive_contenthub
#   playbooks/test.sh --target=hetzner --reinit
#
# Exit codes:
#   0  all suites green (or none selected)
#   1  usage / config error
#   2  container not running or init failed
#   3  tests ran but failed
#
# Design notes:
#   - Production Moodle data is not touched: PHPUnit uses its own DB
#     prefix + dataroot; Behat uses another set. Both are configured in
#     config.php via $CFG->phpunit_prefix/$CFG->phpunit_dataroot resp.
#     $CFG->behat_prefix/$CFG->behat_dataroot/$CFG->behat_wwwroot.
#   - The script assumes config.php has been pre-edited to add those
#     entries. See playbooks/testing-hetzner.md for the exact snippet.
#   - PHPUnit init is cheap and idempotent (~5s when already set up).
#   - Behat init is slower (~1-2 min) because it rebuilds the test
#     dataroot. --reinit forces a rebuild; otherwise the script only
#     runs init when the diag step reports the environment is stale.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$ROOT"

# ---------------------------------------------------------------------------
# Arg parsing.
# ---------------------------------------------------------------------------
TARGET=""
SUITE="all"             # all | phpunit | behat
COMPONENT_FILTER=""     # frankenstyle, e.g. local_lernhive_contenthub
BEHAT_TAGS=""           # e.g. @local_lernhive_contenthub
FORCE_REINIT=0

usage() {
  cat <<EOF
Usage: playbooks/test.sh --target=<local|hetzner> [options]

Options:
  --target=ENV         Test target. Loads playbooks/test.ENV.env. Required.
  --suite=NAME         phpunit | behat | all (default: all).
  --component=NAME     Frankenstyle component filter
                       (e.g. local_lernhive_contenthub). Applies to both
                       PHPUnit --testsuite and Behat --tags.
  --tags=TAGS          Behat tag expression (overrides --component for Behat).
  --reinit             Force re-init of the test environment before running.
                       Useful when fixtures or DB schema changed.
  -h, --help           Show this help and exit.

Examples:
  playbooks/test.sh --target=hetzner
  playbooks/test.sh --target=hetzner --suite=phpunit --component=local_lernhive_contenthub
  playbooks/test.sh --target=hetzner --suite=behat --tags=@javascript
EOF
}

for arg in "$@"; do
  case "$arg" in
    --target=*)    TARGET="${arg#--target=}" ;;
    --suite=*)     SUITE="${arg#--suite=}" ;;
    --component=*) COMPONENT_FILTER="${arg#--component=}" ;;
    --tags=*)      BEHAT_TAGS="${arg#--tags=}" ;;
    --reinit)      FORCE_REINIT=1 ;;
    -h|--help)     usage; exit 0 ;;
    *)             echo "Unknown argument: $arg" >&2; usage; exit 1 ;;
  esac
done

if [[ -z "$TARGET" ]]; then
  echo "Error: --target is required" >&2
  usage
  exit 1
fi

case "$SUITE" in
  all|phpunit|behat) ;;
  *) echo "Error: --suite must be one of: all, phpunit, behat" >&2; exit 1 ;;
esac

CONF="$SCRIPT_DIR/test.$TARGET.env"
if [[ ! -f "$CONF" ]]; then
  echo "Error: no config file found at $CONF" >&2
  exit 1
fi

# shellcheck disable=SC1090
source "$CONF"

# Required config vars.
: "${CONTAINER:?CONTAINER not set in $CONF}"
: "${MOODLE_CLI_ROOT:?MOODLE_CLI_ROOT not set in $CONF}"
# MOODLE_REPO_ROOT is the directory containing composer.json, vendor/,
# config.php and the generated phpunit.xml. For a classic flat Moodle
# layout it equals MOODLE_CLI_ROOT; for a Moodle 5.x public/ split it
# is one level above MOODLE_CLI_ROOT.
MOODLE_REPO_ROOT="${MOODLE_REPO_ROOT:-$MOODLE_CLI_ROOT}"
MOODLE_USER="${MOODLE_USER:-www-data}"
PHPUNIT_BIN="${PHPUNIT_BIN:-$MOODLE_REPO_ROOT/vendor/bin/phpunit}"
BEHAT_BIN="${BEHAT_BIN:-$MOODLE_REPO_ROOT/vendor/bin/behat}"
PHPUNIT_MEM="${PHPUNIT_MEM:-512M}"

# ---------------------------------------------------------------------------
# Helpers.
# ---------------------------------------------------------------------------
log()  { printf '\033[1;34m[test]\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m[test]\033[0m %s\n' "$*" >&2; }
err()  { printf '\033[1;31m[test]\033[0m %s\n' "$*" >&2; }
ok()   { printf '\033[1;32m[test]\033[0m %s\n' "$*"; }

in_container() {
  docker exec -u "$MOODLE_USER" -i "$CONTAINER" "$@"
}

in_container_workdir() {
  docker exec -u "$MOODLE_USER" -w "$MOODLE_CLI_ROOT" -i "$CONTAINER" "$@"
}

# Run inside the container with CWD = Moodle repo root (where phpunit.xml,
# composer.json and vendor/ live). Needed for the actual phpunit binary
# invocation so that relative paths inside phpunit.xml resolve correctly
# against the repo root, not the public/ docroot.
in_container_repo_root() {
  docker exec -u "$MOODLE_USER" -w "$MOODLE_REPO_ROOT" -i "$CONTAINER" "$@"
}

container_check() {
  if ! docker ps --format '{{.Names}}' | grep -qx "$CONTAINER"; then
    err "Container '$CONTAINER' is not running on this host."
    err "Check 'docker ps' and verify CONTAINER in $CONF."
    exit 2
  fi
}

# Map a frankenstyle component to a Moodle testsuite name.
# Convention: Moodle registers a PHPUnit testsuite named exactly like
# the component (e.g. local_lernhive_contenthub_testsuite) after
# util.php --buildcomponentconfigs has run.
component_testsuite() {
  echo "${1}_testsuite"
}

# ---------------------------------------------------------------------------
# PHPUnit.
# ---------------------------------------------------------------------------
phpunit_diag_and_init() {
  local diag_rc=0

  # util.php --diag exit codes (see admin/tool/phpunit/cli/util.php):
  #   0  everything ready
  #   129 config not set (phpunit_prefix / phpunit_dataroot missing)
  #   130 dataroot missing
  #   131 DB not initialised
  #   132 DB schema out of date
  #   other != 0 → treat as "needs init"
  log "PHPUnit: diag"
  if in_container_workdir php admin/tool/phpunit/cli/util.php --diag; then
    diag_rc=0
  else
    diag_rc=$?
  fi

  if [[ "$FORCE_REINIT" -eq 1 || "$diag_rc" -ne 0 ]]; then
    if [[ "$diag_rc" -eq 129 ]]; then
      err "PHPUnit config keys missing in config.php."
      err "See playbooks/testing-hetzner.md → 'PHPUnit config.php block'."
      exit 2
    fi
    log "PHPUnit: init (diag rc=$diag_rc, force=$FORCE_REINIT)"
    if ! in_container_workdir php admin/tool/phpunit/cli/init.php; then
      err "PHPUnit init failed. Check container logs."
      exit 2
    fi
  else
    # Make sure testsuites and fixtures are picked up after a deploy
    # that added new tests. This is cheap (~1s) and safe to run even
    # when diag is already clean.
    log "PHPUnit: rebuild component configs"
    in_container_workdir php admin/tool/phpunit/cli/util.php --buildcomponentconfigs >/dev/null
  fi
}

run_phpunit() {
  phpunit_diag_and_init

  local args=()
  if [[ -n "$COMPONENT_FILTER" ]]; then
    args+=(--testsuite "$(component_testsuite "$COMPONENT_FILTER")")
  fi

  log "PHPUnit: running ${args[*]:-<full suite>}"
  # Memory limit bumped — Moodle's PHPUnit run can exceed the default
  # 128M on larger components. XDEBUG is disabled in the container by
  # default; if it's on, this flag is a no-op.
  #
  # We run phpunit from the Moodle repo root (MOODLE_REPO_ROOT), NOT
  # from the public/ docroot, because:
  #   - Moodle generates phpunit.xml at MOODLE_REPO_ROOT/phpunit.xml
  #     (next to composer.json and vendor/).
  #   - The <testsuite> directories inside phpunit.xml are relative
  #     to the phpunit.xml location, so CWD and -c must both sit at
  #     the repo root for those paths to resolve.
  if in_container_repo_root \
      env PHPUNIT_MEMORY_LIMIT="$PHPUNIT_MEM" \
      php -d memory_limit="$PHPUNIT_MEM" \
      "$PHPUNIT_BIN" \
      -c "$MOODLE_REPO_ROOT/phpunit.xml" \
      "${args[@]}"; then
    ok "PHPUnit: passed"
    return 0
  else
    err "PHPUnit: failed"
    return 3
  fi
}

# ---------------------------------------------------------------------------
# Behat.
# ---------------------------------------------------------------------------
behat_diag_and_init() {
  local diag_rc=0

  log "Behat: diag"
  if in_container_workdir php admin/tool/behat/cli/util.php --diag; then
    diag_rc=0
  else
    diag_rc=$?
  fi

  if [[ "$FORCE_REINIT" -eq 1 || "$diag_rc" -ne 0 ]]; then
    if [[ "$diag_rc" -eq 129 ]]; then
      err "Behat config keys missing in config.php (behat_prefix / behat_dataroot / behat_wwwroot)."
      err "See playbooks/testing-hetzner.md → 'Behat config.php block'."
      exit 2
    fi
    log "Behat: init (diag rc=$diag_rc, force=$FORCE_REINIT)"
    # --add-core-features-to-theme keeps core/theme Behat features in
    # sync when new steps land via a Moodle core upgrade.
    if ! in_container_workdir php admin/tool/behat/cli/init.php --add-core-features-to-theme; then
      err "Behat init failed. Check container logs and Selenium reachability."
      err "Selenium URL expected in config.php: \$CFG->behat_profiles['default']['wd_host']"
      exit 2
    fi
  else
    log "Behat: enable test environment"
    # util.php --enable is idempotent; it's cheap and guarantees the
    # behat data is in the "ready to run" state after a redeploy.
    in_container_workdir php admin/tool/behat/cli/util.php --enable >/dev/null
  fi
}

run_behat() {
  behat_diag_and_init

  # Determine tag expression:
  #   explicit --tags beats --component
  local tags=""
  if [[ -n "$BEHAT_TAGS" ]]; then
    tags="$BEHAT_TAGS"
  elif [[ -n "$COMPONENT_FILTER" ]]; then
    tags="@$COMPONENT_FILTER"
  fi

  # Moodle generates a per-run Behat config at
  # $CFG->behat_dataroot/behatrun/behat/behat.yml — use that. The php
  # one-liner runs from MOODLE_REPO_ROOT so the relative require of
  # config.php resolves correctly on both flat and public/-split
  # layouts (config.php always sits at MOODLE_REPO_ROOT).
  local configpath
  configpath="$(in_container_repo_root php -r '
    define("CLI_SCRIPT", true);
    require("config.php");
    echo $CFG->behat_dataroot . "/behatrun/behat/behat.yml";
  ' 2>/dev/null)"

  if [[ -z "$configpath" ]]; then
    err "Behat: could not resolve behat.yml path from config.php"
    return 3
  fi

  local args=(--config "$configpath")
  if [[ -n "$tags" ]]; then
    args+=(--tags "$tags")
  fi

  log "Behat: running ${args[*]}"
  # Behat is invoked from the repo root (same reason as phpunit):
  # the composer autoloader lives next to vendor/ at MOODLE_REPO_ROOT.
  if in_container_repo_root "$BEHAT_BIN" "${args[@]}"; then
    ok "Behat: passed"
    return 0
  else
    err "Behat: failed"
    return 3
  fi
}

# ---------------------------------------------------------------------------
# Main.
# ---------------------------------------------------------------------------
log "target=$TARGET  container=$CONTAINER  suite=$SUITE  component=${COMPONENT_FILTER:-<all>}  reinit=$FORCE_REINIT"
container_check

FAIL=0

if [[ "$SUITE" == "all" || "$SUITE" == "phpunit" ]]; then
  if ! run_phpunit; then
    FAIL=1
  fi
fi

if [[ "$SUITE" == "all" || "$SUITE" == "behat" ]]; then
  if ! run_behat; then
    FAIL=1
  fi
fi

if [[ "$FAIL" -ne 0 ]]; then
  err "One or more suites failed."
  exit 3
fi

ok "All selected suites passed."
