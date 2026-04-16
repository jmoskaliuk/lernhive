#!/usr/bin/env bash
#
# One-shot remediation for a Hetzner Behat environment that is stuck on
# "This is not a behat test site!" / "Behat init failed".
#
# Addresses playbooks/testing-hetzner.md §1 (config.php block) and §3
# (Selenium reachability) in a single idempotent server-side script so
# the repair fits into one SSH trip.
#
# Usage (as root on the Hetzner host):
#
#   curl -fsSL https://raw.githubusercontent.com/jmoskaliuk/lernhive/main/playbooks/hetzner-behat-fix.sh | bash
#
#   # or, after `cd /opt/lernhive && git pull`:
#   sudo bash playbooks/hetzner-behat-fix.sh
#
# What it does (idempotent — safe to re-run):
#   1. Confirms the right config.php path (the one at $MOODLE_ROOT, NOT
#      the public/ loader stub).
#   2. Checks the six $CFG->{phpunit,behat}_* keys. If any are missing,
#      atomically appends the canonical block from testing-hetzner.md §1
#      *before* the final require_once(setup.php) line.
#   3. Creates /var/www/moodledata_{phpunit,behat} and hands them to
#      www-data inside the container.
#   4. Probes Selenium reachability from inside the webserver container
#      and, if the default hostname doesn't resolve, prints the docker
#      network members so you can pick the correct wd_host.
#   5. Runs `admin/tool/behat/cli/init.php` and reports the outcome.
#
# Exit codes:
#   0  environment is healthy / repaired successfully
#   1  invocation / permissions error
#   2  config.php patch applied, but init still fails (Selenium is the
#      most likely remaining cause — read the Selenium section output)
#
set -euo pipefail

# ---------------------------------------------------------------------------
# Config — matches playbooks/deploy.hetzner.env + test.hetzner.env.
# ---------------------------------------------------------------------------
CONTAINER="${CONTAINER:-lernhive-webserver-1}"
MOODLE_ROOT="${MOODLE_ROOT:-/var/www/html}"
CONFIG_PHP="${CONFIG_PHP:-$MOODLE_ROOT/config.php}"
PHPUNIT_DATAROOT="${PHPUNIT_DATAROOT:-/var/www/moodledata_phpunit}"
BEHAT_DATAROOT="${BEHAT_DATAROOT:-/var/www/moodledata_behat}"
BEHAT_WWWROOT="${BEHAT_WWWROOT:-http://lernhive-webserver-1}"
SELENIUM_WD_HOST="${SELENIUM_WD_HOST:-http://selenium:4444/wd/hub}"
SELENIUM_HOST_ONLY="${SELENIUM_WD_HOST#http://}"
SELENIUM_HOST_ONLY="${SELENIUM_HOST_ONLY%%:*}"

c_blue='\033[1;34m'; c_green='\033[1;32m'; c_yellow='\033[1;33m'
c_red='\033[1;31m'; c_reset='\033[0m'
step() { printf "\n${c_blue}==>${c_reset} %s\n" "$*"; }
ok()   { printf "${c_green}✓${c_reset} %s\n" "$*"; }
skip() { printf "${c_yellow}·${c_reset} %s\n" "$*"; }
warn() { printf "${c_yellow}!${c_reset} %s\n" "$*" >&2; }
die()  { printf "${c_red}✗${c_reset} %s\n" "$*" >&2; exit 1; }

[[ $EUID -eq 0 ]] || die "Run as root (sudo bash $0)"

# ---------------------------------------------------------------------------
# 1. Container sanity.
# ---------------------------------------------------------------------------
step "Container sanity"
docker ps --format '{{.Names}}' | grep -qx "$CONTAINER" \
  || die "Container '$CONTAINER' is not running. Check 'docker ps'."
ok "container $CONTAINER is running"

# ---------------------------------------------------------------------------
# 2. config.php path + test block.
# ---------------------------------------------------------------------------
step "Inspecting $CONFIG_PHP inside the container"

docker exec -u www-data -w / "$CONTAINER" test -f "$CONFIG_PHP" \
  || die "$CONFIG_PHP does not exist inside $CONTAINER. Moodle not installed?"

# Guard against editing the public/ loader stub — it only re-requires the
# authoritative file and has no $CFG->wwwroot of its own.
if ! docker exec -u www-data -w / "$CONTAINER" \
      grep -q 'CFG->wwwroot' "$CONFIG_PHP"; then
  die "$CONFIG_PHP looks like a loader stub (no \$CFG->wwwroot). Point CONFIG_PHP at the real file (usually /var/www/html/config.php, NOT /var/www/html/public/config.php)."
fi
ok "$CONFIG_PHP is the authoritative config"

MISSING=()
for key in phpunit_prefix phpunit_dataroot behat_prefix behat_dataroot behat_wwwroot behat_profiles; do
  if ! docker exec -u www-data -w / "$CONTAINER" \
        grep -q "CFG->${key}" "$CONFIG_PHP"; then
    MISSING+=("$key")
  fi
done

if [[ ${#MISSING[@]} -eq 0 ]]; then
  ok "all six \$CFG->{phpunit,behat}_* keys present"
else
  warn "missing keys: ${MISSING[*]}"
  step "Appending canonical test block to $CONFIG_PHP (via container, owned by www-data)"

  # Write the patch into the container as a heredoc so shell escaping
  # stays local and we don't have to round-trip through docker cp.
  docker exec -u www-data -w / -i "$CONTAINER" bash -s "$CONFIG_PHP" \
        "$PHPUNIT_DATAROOT" "$BEHAT_DATAROOT" "$BEHAT_WWWROOT" "$SELENIUM_WD_HOST" \
        <<'INCONTAINER'
set -euo pipefail
CONFIG="$1"
PHPUNIT_DR="$2"
BEHAT_DR="$3"
BEHAT_WR="$4"
WD_HOST="$5"

cp -p "$CONFIG" "${CONFIG}.bak.$(date +%Y%m%dT%H%M%S)"

# Build the canonical block once. We use a PHP script to insert it so we
# can correctly handle the "final require_once" position even if
# whitespace/comments vary across Moodle versions.
TMP=$(mktemp /tmp/lernhive-behat-fix.XXXXXX.php)
cat >"$TMP" <<'PHP'
<?php
[, $file, $phpunit_dr, $behat_dr, $behat_wr, $wd_host] = $argv;
$src = file_get_contents($file);
if ($src === false) { fwrite(STDERR, "read failed\n"); exit(1); }

// Escape single-quoted PHP strings.
$q = fn($s) => "'" . str_replace(['\\', "'"], ['\\\\', "\\'"], $s) . "'";

$block = <<<BLOCK

// ---- LernHive test block (inserted by playbooks/hetzner-behat-fix.sh) ----
// Keep PHPUnit + Behat isolated from production data.
\$CFG->phpunit_prefix   = 'phpu_';
\$CFG->phpunit_dataroot = {$q($phpunit_dr)};
\$CFG->behat_prefix     = 'bht_';
\$CFG->behat_dataroot   = {$q($behat_dr)};
\$CFG->behat_wwwroot    = {$q($behat_wr)};
\$CFG->behat_profiles = [
    'default' => [
        'browser' => 'chrome',
        'wd_host' => {$q($wd_host)},
        'capabilities' => [
            'extra_capabilities' => [
                'goog:chromeOptions' => [
                    'args' => ['--no-sandbox', '--disable-dev-shm-usage'],
                ],
            ],
        ],
    ],
];
// ---- /LernHive test block ----

BLOCK;

// Strip any previous block we inserted so re-runs don't duplicate.
$src = preg_replace(
    '~\n// ---- LernHive test block.*?// ---- /LernHive test block ----\n~s',
    "\n",
    $src
);

// Insert right before the final require_once(...setup.php).
$anchor = '/(\n[^\n]*require_once[^\n]*setup\.php[^\n]*;\s*)$/s';
if (preg_match($anchor, $src)) {
    $src = preg_replace($anchor, $block . "$1", $src, 1);
} else {
    // No anchor: append and add setup require. Shouldn't happen on a
    // working Moodle install, but be defensive.
    $src .= $block . "\nrequire_once(__DIR__ . '/lib/setup.php');\n";
}

file_put_contents($file, $src);
echo "OK\n";
PHP

php "$TMP" "$CONFIG" "$PHPUNIT_DR" "$BEHAT_DR" "$BEHAT_WR" "$WD_HOST"
rm -f "$TMP"

# Syntax-check the result. If this fails the .bak is one line above.
php -l "$CONFIG" >/dev/null
INCONTAINER

  ok "test block inserted"
fi

# ---------------------------------------------------------------------------
# 3. Datarooots.
# ---------------------------------------------------------------------------
step "Ensuring datarooots exist with www-data ownership"

# Datarooots live on the host side of the bind-mount but Moodle accesses
# them from inside the container. We create them inside the container so
# the owner really is container-www-data (uid 33) — host-side www-data
# may not exist.
docker exec -u root -w / "$CONTAINER" bash -s "$PHPUNIT_DATAROOT" "$BEHAT_DATAROOT" \
    <<'INCONTAINER'
set -euo pipefail
for d in "$1" "$2"; do
  if [[ ! -d "$d" ]]; then
    install -d -o www-data -g www-data "$d"
    echo "created $d"
  else
    chown -R www-data:www-data "$d"
    echo "ok      $d"
  fi
done
INCONTAINER
ok "datarooots ready: $PHPUNIT_DATAROOT, $BEHAT_DATAROOT"

# ---------------------------------------------------------------------------
# 4. Selenium reachability (§3).
# ---------------------------------------------------------------------------
step "Probing Selenium at $SELENIUM_WD_HOST"

SEL_STATUS="${SELENIUM_WD_HOST%/wd/hub}/status"
if docker exec -u www-data "$CONTAINER" curl -fsS --max-time 5 "$SEL_STATUS" \
      | grep -q '"ready":true'; then
  ok "Selenium reachable and reports ready:true"
  SELENIUM_OK=1
else
  SELENIUM_OK=0
  warn "Selenium is NOT reachable at $SELENIUM_WD_HOST"

  # Try DNS resolution inside the container.
  echo
  echo "[debug] DNS inside $CONTAINER for '$SELENIUM_HOST_ONLY':"
  docker exec "$CONTAINER" getent hosts "$SELENIUM_HOST_ONLY" 2>&1 \
    || echo "  (hostname does not resolve)"

  echo
  echo "[debug] docker ps — look for a selenium/hub/chrome container:"
  docker ps --format 'table {{.Names}}\t{{.Image}}\t{{.Status}}' \
    | grep -Ei 'selenium|hub|chrome|browser' \
    || echo "  (no selenium-like container found — you need to start one)"

  echo
  echo "[debug] docker networks the webserver container is attached to:"
  docker inspect -f \
    '{{range $k, $_ := .NetworkSettings.Networks}}{{$k}}{{"\n"}}{{end}}' \
    "$CONTAINER"

  echo
  echo "[debug] other containers on those networks (potential selenium hosts):"
  for net in $(docker inspect -f \
        '{{range $k, $_ := .NetworkSettings.Networks}}{{$k}} {{end}}' \
        "$CONTAINER"); do
    echo "  network $net:"
    docker network inspect -f \
      '{{range .Containers}}    {{.Name}} ({{.IPv4Address}}){{"\n"}}{{end}}' \
      "$net"
  done

  echo
  warn "Fix path:"
  warn "  (a) If no Selenium container exists: start one on the same"
  warn "      docker network as $CONTAINER, e.g. via moodle-docker with"
  warn "      MOODLE_DOCKER_BROWSER=chrome in .env.local, then re-run this script."
  warn "  (b) If the Selenium container exists under a different name,"
  warn "      re-run with SELENIUM_WD_HOST overridden:"
  warn "        SELENIUM_WD_HOST=http://<name>:4444/wd/hub sudo -E bash $0"
fi

# ---------------------------------------------------------------------------
# 5. Actually initialise Behat.
# ---------------------------------------------------------------------------
step "Running admin/tool/behat/cli/init.php (full install)"

# The CLI helpers live under public/ on Moodle 5.x and under the repo
# root on Moodle 4.x — probe which layout applies.
if docker exec -u www-data -w "$MOODLE_ROOT/public" "$CONTAINER" \
      test -f admin/tool/behat/cli/init.php; then
  CLI_CWD="$MOODLE_ROOT/public"
else
  CLI_CWD="$MOODLE_ROOT"
fi
ok "using cwd $CLI_CWD for behat CLI"

set +e
docker exec -u www-data -w "$CLI_CWD" "$CONTAINER" \
  php admin/tool/behat/cli/init.php
INIT_RC=$?
set -e

if [[ $INIT_RC -eq 0 ]]; then
  ok "Behat init succeeded — you should now be able to run:"
  echo "    sudo -u deploy lernhive-test --suite=behat"
  echo "  or trigger the 'Test on Hetzner' workflow in GitHub Actions."
  exit 0
fi

warn "Behat init still failed with rc=$INIT_RC"
if [[ $SELENIUM_OK -eq 0 ]]; then
  warn "Selenium is still unreachable — resolve that first (see §4 output above), then re-run this script."
fi
exit 2
