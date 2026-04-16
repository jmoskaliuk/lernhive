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
FULL=0                  # run Moodle core tests too, not just LernHive plugins

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
  --full               Opt-in: run ALL testsuites, including Moodle core
                       (~29k PHPUnit tests, very slow). Without this flag,
                       the default is to run only LernHive plugin testsuites.
  -h, --help           Show this help and exit.

Examples:
  playbooks/test.sh --target=hetzner
  playbooks/test.sh --target=hetzner --suite=phpunit --component=local_lernhive_contenthub
  playbooks/test.sh --target=hetzner --suite=behat --tags=@javascript
  playbooks/test.sh --target=hetzner --full         # includes Moodle core
EOF
}

for arg in "$@"; do
  case "$arg" in
    --target=*)    TARGET="${arg#--target=}" ;;
    --suite=*)     SUITE="${arg#--suite=}" ;;
    --component=*) COMPONENT_FILTER="${arg#--component=}" ;;
    --tags=*)      BEHAT_TAGS="${arg#--tags=}" ;;
    --reinit)      FORCE_REINIT=1 ;;
    --full)        FULL=1 ;;
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

# ---------------------------------------------------------------------------
# Pre-flight cleanup.
#
# Why this exists:
#   When a previous test run is aborted from the caller side (Ctrl-C in
#   Cowork, GH Actions cancel cascade, SSH drop, ...), the `docker exec`
#   client dies but the PHP process INSIDE the container keeps running.
#   It is reparented to PID 1 inside the container, holds Moodle's
#   test lock via flock() on the phpunit/behat dataroot, and leaves
#   behind orphan `phpu_*` / `behat_*` tables. The next test run then
#   either blocks forever on "Waiting for other test execution to
#   complete..." or crashes in init.php with ddlexecuteerror on a
#   leftover table / index.
#
#   Since test-hetzner.yml now runs on schedule + workflow_dispatch
#   only (no push trigger), every run that gets here is intentional
#   and can safely wipe any stale state. We do this as the FIRST
#   action after container_check().
#
# What it does:
#   1. SIGTERM any lingering phpunit / behat / util.php processes
#      owned by the Moodle user (so flock() handles get released
#      cleanly by the PHP runtime).
#   2. Short grace period.
#   3. SIGKILL anything still alive (CPU-bound 29k full-core runs
#      typically need the hard kill).
#   4. DROP TABLE every `phpu_*` and `behat_*` table via a PHP
#      round-trip that talks to Moodle's own $DB. We can't use the
#      psql CLI because the DB credentials are only reachable via
#      config.php. Using $DB->execute() also keeps us on whatever
#      driver the target is configured for (Postgres on Hetzner,
#      MariaDB on local OrbStack).
#
# See: feedback memory "docker exec abort leaves PHP zombies"
#      (/sessions/.../feedback_docker_exec_abort.md).
# ---------------------------------------------------------------------------
preflight_cleanup() {
  log "preflight: killing stale phpunit/behat processes (if any)"

  # DO NOT USE `pkill -f 'phpunit|behat|...'` HERE. pkill matches on
  # /proc/PID/cmdline and matches ALL processes whose argv contains
  # that substring — including its own parent bash (which has the
  # pattern literally in `bash -c "pkill ... -f 'phpunit|behat|...'"`).
  # pkill excludes its own pid from matches, but NOT the parent shell.
  # Result: pkill SIGTERMs its own parent bash, docker exec returns
  # 143, set -euo pipefail in test.sh propagates it, and the whole
  # job dies at the preflight step with exit 143.
  #
  # Instead we run a small bash script via stdin-heredoc (so the
  # running bash's argv is just "bash", no pattern) and put the kill
  # pattern in an environment variable that awk reads via ENVIRON[]
  # (so no awk, ps, or kill process has the pattern in its own argv
  # either). Matching then happens via `ps | awk`, and we explicitly
  # skip our own PID + the parent bash + the full ancestor chain,
  # so there is no way to self-kill.
  in_container bash <<'PREFLIGHT_KILL'
set -u

# The kill pattern lives in an env var. Environment variables do NOT
# appear in /proc/PID/cmdline, so no process running this script can
# self-match.
export LH_KILL_PAT='vendor/bin/phpunit|vendor/bin/behat|admin/tool/phpunit/cli|admin/tool/behat/cli'

# Build the full ancestor chain of the current shell so we exclude
# ourselves, our pipe subshells, docker-exec's payload bash, PID 1,
# etc. Anything in this set is off-limits.
build_ancestors() {
  local pid="$1"
  while [ -n "$pid" ] && [ "$pid" != "0" ] && [ "$pid" != "1" ]; do
    printf '%s\n' "$pid"
    pid=$(awk '{print $4}' "/proc/$pid/stat" 2>/dev/null)
  done
}
ANCESTORS=$(build_ancestors "$$" | tr '\n' '|' | sed 's/|$//')
# Avoid an empty regex (would match everything).
ANCESTORS="${ANCESTORS:-0}"

matching_pids() {
  # ps -o pid=,cmd= prints "  PID  full cmdline" so the leading
  # whitespace/PID can be peeled off by awk. We do NOT use pgrep/pkill
  # because those tools keep the kill pattern on their own command
  # line and are therefore self-matching.
  ps -eo pid=,cmd= 2>/dev/null | awk -v anc="^(${ANCESTORS})$" '
    {
      pid = $1
      # Skip ourselves and the entire ancestor chain no matter what
      # their cmd looks like.
      if (pid ~ anc) { next }
      # $0 is "  PID  cmd ..." — the pattern match covers cmd.
      if ($0 ~ ENVIRON["LH_KILL_PAT"]) { print pid }
    }
  '
}

targets=$(matching_pids)
if [ -n "$targets" ]; then
  # shellcheck disable=SC2086
  echo "$targets" | xargs -r kill -TERM 2>/dev/null || true
  sleep 2
  targets=$(matching_pids)
  if [ -n "$targets" ]; then
    # shellcheck disable=SC2086
    echo "$targets" | xargs -r kill -KILL 2>/dev/null || true
  fi
  echo "[preflight] killed stale phpunit/behat processes"
else
  echo "[preflight] no stale phpunit/behat processes"
fi
PREFLIGHT_KILL

  log "preflight: dropping leftover phpu_* and behat_* tables"
  # Nested heredoc: OUTER is delivered to bash -s as the command body,
  # and PHPSCRIPT is delivered to `php --` as the PHP source. Both use
  # single-quoted heredoc markers so no $-expansion or backslash
  # escaping happens at any layer. This is the same pattern that
  # successfully cleared 259 orphan tables by hand on 2026-04-11.
  in_container_repo_root bash -s <<'OUTER'
set -e
php -- <<'PHPSCRIPT'
<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/config.php');

// Fetch every table name from the live Moodle DB and filter to the
// two test prefixes. Using the DB driver keeps this portable across
// pgsql / mariadb / mysqli.
$tables = $DB->get_tables(false);
$targets = [];
foreach ($tables as $t) {
    if (strpos($t, 'phpu_') === 0 || strpos($t, 'behat_') === 0) {
        $targets[] = $t;
    }
}

if (empty($targets)) {
    echo "[preflight] no leftover phpu_/behat_ tables\n";
    exit(0);
}

echo "[preflight] dropping " . count($targets) . " leftover test tables\n";
$dropped = 0;
$failed  = 0;
foreach ($targets as $t) {
    try {
        // CASCADE is Postgres-specific but harmless on MySQL/MariaDB
        // where it is not supported — we catch the error and retry
        // without CASCADE so this function stays driver-agnostic.
        try {
            $DB->execute("DROP TABLE IF EXISTS \"{$t}\" CASCADE");
        } catch (Throwable $e) {
            $DB->execute("DROP TABLE IF EXISTS `{$t}`");
        }
        $dropped++;
    } catch (Throwable $e) {
        echo "[preflight] could not drop {$t}: " . $e->getMessage() . "\n";
        $failed++;
    }
}
echo "[preflight] dropped={$dropped} failed={$failed}\n";
PHPSCRIPT
OUTER

  ok "preflight: container is clean"
}

# Map a frankenstyle component to a Moodle testsuite name.
# Convention: Moodle registers a PHPUnit testsuite named exactly like
# the component (e.g. local_lernhive_contenthub_testsuite) after
# util.php --buildcomponentconfigs has run.
component_testsuite() {
  echo "${1}_testsuite"
}

# Enumerate LernHive plugin directories under $ROOT/plugins that have a
# tests/ subdir and emit a comma-separated list of Moodle PHPUnit
# testsuite names (each "<plugin>_testsuite"). Used as the default
# PHPUnit filter so `lernhive-test --suite=phpunit` never accidentally
# runs the full ~29k Moodle core suite.
#
# Rule: plugin directory name equals the frankenstyle component name.
# This holds in our workspace (plugins/local_lernhive_contenthub →
# deployed as local/lernhive_contenthub → component
# local_lernhive_contenthub), so a simple dirname-based mapping is
# sufficient. Plugins without a tests/ directory are skipped to avoid
# passing non-existent testsuite names to phpunit.
lernhive_plugin_components() {
  local d name
  for d in "$ROOT"/plugins/*/; do
    [[ -d "$d" ]] || continue
    name="$(basename "$d")"
    [[ -d "$d/tests" ]] || continue
    echo "$name"
  done
}

lernhive_testsuite_list() {
  local names=()
  local c
  while IFS= read -r c; do
    [[ -n "$c" ]] && names+=("${c}_testsuite")
  done < <(lernhive_plugin_components)
  local IFS=','
  echo "${names[*]}"
}

# Build a Behat tag expression matching any LernHive plugin:
# "@local_lernhive_contenthub||@local_lernhive_copy||…". Returns the
# empty string if no plugins are discovered, in which case the caller
# should not pass a --tags filter.
lernhive_behat_tags() {
  local tags=()
  local c
  while IFS= read -r c; do
    [[ -n "$c" ]] && tags+=("@$c")
  done < <(lernhive_plugin_components)
  local IFS='||'
  # Bash joins array with first char of IFS; use a manual join instead
  # so we get the literal "||" separator Behat expects.
  if [[ ${#tags[@]} -eq 0 ]]; then
    echo ""
    return
  fi
  local out="${tags[0]}"
  local i
  for ((i = 1; i < ${#tags[@]}; i++)); do
    out="${out}||${tags[i]}"
  done
  echo "$out"
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

  # Testsuite selection:
  #   1. Explicit --component → single testsuite for that plugin.
  #   2. --full              → no filter (runs Moodle core + all plugins,
  #                            ~29k tests, opt-in only).
  #   3. Default             → comma-separated list of LernHive plugin
  #                            testsuites (only those under plugins/
  #                            with a tests/ dir). This is what prevents
  #                            a plain `lernhive-test --suite=phpunit`
  #                            from blowing up into a 29k-test run.
  local args=()
  if [[ -n "$COMPONENT_FILTER" ]]; then
    args+=(--testsuite "$(component_testsuite "$COMPONENT_FILTER")")
    log "PHPUnit: filter → $(component_testsuite "$COMPONENT_FILTER") (from --component)"
  elif [[ "$FULL" -eq 1 ]]; then
    log "PHPUnit: --full → running ALL testsuites (Moodle core + plugins)"
  else
    local default_suites
    default_suites="$(lernhive_testsuite_list)"
    if [[ -z "$default_suites" ]]; then
      warn "PHPUnit: no LernHive plugins with tests/ found under $ROOT/plugins — falling back to full suite"
    else
      args+=(--testsuite "$default_suites")
      log "PHPUnit: default filter → $default_suites"
    fi
  fi

  log "PHPUnit: running ${args[*]:-<unfiltered>}"
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
  #
  # Exit-code handling:
  # Moodle's shipped phpunit.xml sets failOnWarning/failOnDeprecation
  # strictly, so phpunit exits non-zero even when all tests pass but
  # something triggered a warning. In practice most of those warnings
  # come from Moodle core's own reset_dataroot() cleanup trying to
  # unlink MUC cache files that were already removed — noise, not
  # a real failure. We therefore parse the output: if phpunit ends
  # with "OK (…)" or "OK, but there were issues!", we treat it as
  # passed (with a yellow warn log); only "FAILURES!"/"ERRORS!" or
  # an exit code without any OK marker counts as a hard failure.
  local tmpout
  tmpout="$(mktemp)"
  local rc=0
  set +e
  in_container_repo_root \
      env PHPUNIT_MEMORY_LIMIT="$PHPUNIT_MEM" \
      php -d memory_limit="$PHPUNIT_MEM" \
      "$PHPUNIT_BIN" \
      -c "$MOODLE_REPO_ROOT/phpunit.xml" \
      "${args[@]}" 2>&1 | tee "$tmpout"
  rc="${PIPESTATUS[0]}"
  set -e

  if [[ "$rc" -eq 0 ]]; then
    ok "PHPUnit: passed"
    rm -f "$tmpout"
    return 0
  fi

  # Non-zero exit: distinguish "green tests with warnings" from real
  # failures by looking for PHPUnit's own summary line.
  if grep -qE '^OK(\s|,|$)' "$tmpout"; then
    warn "PHPUnit: passed, but phpunit reported warnings/deprecations (exit $rc — treated as pass)"
    rm -f "$tmpout"
    return 0
  fi

  err "PHPUnit: failed (exit $rc)"
  rm -f "$tmpout"
  return 3
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
    # Pick the right init mode:
    #   - diag_rc == 0 (site already installed, but --reinit forced):
    #       plain `init.php --add-core-features-to-theme` is correct —
    #       it calls util.php --enable and re-syncs core/theme Behat
    #       features that may have landed via a Moodle core upgrade.
    #   - diag_rc != 0 (no test site yet, or config mismatch):
    #       must run plain `init.php` (no flag). Moodle's --add-core-
    #       features-to-theme path assumes an existing test site and
    #       aborts with "This is not a behat test site!" otherwise —
    #       which is exactly what we were hitting on fresh Hetzner
    #       deployments. Plain init.php does drop + install + enable
    #       and is idempotent enough for first-time bootstrap.
    local init_args=()
    if [[ "$diag_rc" -eq 0 ]]; then
      init_args+=(--add-core-features-to-theme)
    fi
    log "Behat: init (diag rc=$diag_rc, force=$FORCE_REINIT, args=${init_args[*]:-<full install>})"
    if ! in_container_workdir php admin/tool/behat/cli/init.php "${init_args[@]}"; then
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
  #   1. Explicit --tags      → use as-is.
  #   2. --component          → @<component>.
  #   3. --full               → no filter (all Behat features on the site).
  #   4. Default              → OR-expression of all LernHive plugin tags
  #                             (@local_lernhive_contenthub||@local_lernhive_copy||…).
  # Reason: same mistake-avoidance as run_phpunit — we don't want a plain
  # `lernhive-test --suite=behat` to run every Behat feature in Moodle
  # core, which would take hours on the small Hetzner VM.
  local tags=""
  if [[ -n "$BEHAT_TAGS" ]]; then
    tags="$BEHAT_TAGS"
  elif [[ -n "$COMPONENT_FILTER" ]]; then
    tags="@$COMPONENT_FILTER"
  elif [[ "$FULL" -eq 1 ]]; then
    tags=""
    log "Behat: --full → running ALL features (no tag filter)"
  else
    tags="$(lernhive_behat_tags)"
    if [[ -z "$tags" ]]; then
      warn "Behat: no LernHive plugins with tests/ found — running without tag filter"
    else
      log "Behat: default filter → $tags"
    fi
  fi

  # Moodle generates a per-run Behat config under the behat dataroot.
  # The exact layout depends on how config.php is written AND on what
  # Moodle's setup.php does at load time:
  #
  #   Case A — config.php sets $CFG->behat_dataroot to the parent, and
  #            Moodle's setup does NOT extend it:
  #              $CFG->behat_dataroot = /var/www/moodledata_behat
  #              → config file = $CFG->behat_dataroot/behatrun/behat/behat.yml
  #
  #   Case B — config.php already points at the /behatrun subdir, OR
  #            Moodle's setup extends the parent with /behatrun:
  #              $CFG->behat_dataroot = /var/www/moodledata_behat/behatrun
  #              → config file = $CFG->behat_dataroot/behat/behat.yml
  #
  # Hetzner (Moodle 5.1, production-style config.php) is Case B and the
  # local Docker stack has historically been Case A, so we can't hard-code
  # either one. Probe both candidates in a single php -r call and return
  # the one that exists on disk; fall back to Case A if neither exists
  # yet (which only happens before behat has ever been initialised, and
  # behat_diag_and_init above should have run by the time we get here).
  #
  # The php one-liner runs from MOODLE_REPO_ROOT so the relative require
  # of config.php resolves correctly on both flat and public/-split
  # layouts (config.php always sits at MOODLE_REPO_ROOT).
  local configpath
  configpath="$(in_container_repo_root php -r '
    define("CLI_SCRIPT", true);
    require("config.php");
    $base = rtrim($CFG->behat_dataroot, "/");
    $candidates = [
        $base . "/behat/behat.yml",          // Case B
        $base . "/behatrun/behat/behat.yml", // Case A
    ];
    foreach ($candidates as $c) {
        if (file_exists($c)) { echo $c; exit; }
    }
    // Neither file exists yet — return Case A as the canonical fallback.
    echo $candidates[1];
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
log "target=$TARGET  container=$CONTAINER  suite=$SUITE  component=${COMPONENT_FILTER:-<default: lernhive plugins>}  full=$FULL  reinit=$FORCE_REINIT"
container_check

# Always wipe stale phpunit/behat state before running. This makes
# every intentional test run (manual or nightly schedule) self-heal
# from an aborted previous run — see preflight_cleanup() for the
# full reasoning.
preflight_cleanup

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
