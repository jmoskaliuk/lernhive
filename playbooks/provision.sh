#!/usr/bin/env bash
#
# LernHive server provisioning.
#
# Installs Docker + moodle-docker + a LernHive workspace + the deploy
# pipeline on a fresh Ubuntu host (22.04 / 24.04). Designed for the Hetzner
# production server but portable to any Ubuntu VM.
#
# Idempotent: safe to re-run. Each step checks its own preconditions and
# skips if already done.
#
# Usage (on the target server, as root):
#
#   curl -fsSL https://raw.githubusercontent.com/jmoskaliuk/lernhive/main/playbooks/provision.sh | bash
#
#   # …or with overrides for a TLS-enabled staging box:
#   LERNHIVE_DOMAIN=dev.lernhive.de \
#   LETSENCRYPT_EMAIL=admin@lernhive.de \
#   DEPLOY_SSH_PUBKEY="ssh-ed25519 AAAA… github-actions@lernhive" \
#   bash provision.sh
#
# Environment overrides (all optional):
#
#   LERNHIVE_REPO_URL      default: https://github.com/jmoskaliuk/lernhive.git
#   LERNHIVE_REPO_BRANCH   default: main
#   INSTALL_DIR            default: /opt
#   DEPLOY_USER            default: deploy
#   DEPLOY_SSH_PUBKEY      optional; if set, added to deploy user's authorized_keys
#
#   MOODLE_DOCKER_REPO     default: https://github.com/moodlehq/moodle-docker.git
#   MOODLE_DOCKER_BRANCH   default: main
#   MOODLE_REPO            default: https://github.com/moodle/moodle.git
#   MOODLE_BRANCH          default: MOODLE_501_STABLE
#                          (for LTS use: MOODLE_405_STABLE; for bleeding edge: main)
#   MOODLE_DOCROOT         in-container path to Moodle's web docroot.
#                          default: /var/www/html/public   (Moodle 5.x layout)
#                          set to   /var/www/html            for Moodle 4.x
#
#   MOODLE_COMPOSE_SERVICE default: webserver
#                          (becomes CONTAINER suffix, e.g. <stack>-webserver-1)
#   WWW_DATA_UID           default: 33   (uid of www-data inside the container)
#
#   LERNHIVE_DOMAIN        optional; if set, installs Caddy reverse proxy with
#                          Let's Encrypt TLS for this FQDN and rewrites the
#                          Moodle wwwroot to https://$LERNHIVE_DOMAIN.
#   LETSENCRYPT_EMAIL      required iff LERNHIVE_DOMAIN is set.
#
set -euo pipefail

# ---------------------------------------------------------------------------
# Defaults.
# ---------------------------------------------------------------------------
LERNHIVE_REPO_URL="${LERNHIVE_REPO_URL:-https://github.com/jmoskaliuk/lernhive.git}"
LERNHIVE_REPO_BRANCH="${LERNHIVE_REPO_BRANCH:-main}"
INSTALL_DIR="${INSTALL_DIR:-/opt}"
DEPLOY_USER="${DEPLOY_USER:-deploy}"
DEPLOY_SSH_PUBKEY="${DEPLOY_SSH_PUBKEY:-}"

MOODLE_DOCKER_REPO="${MOODLE_DOCKER_REPO:-https://github.com/moodlehq/moodle-docker.git}"
MOODLE_DOCKER_BRANCH="${MOODLE_DOCKER_BRANCH:-main}"
MOODLE_REPO="${MOODLE_REPO:-https://github.com/moodle/moodle.git}"
MOODLE_BRANCH="${MOODLE_BRANCH:-MOODLE_501_STABLE}"
MOODLE_DOCROOT="${MOODLE_DOCROOT:-/var/www/html/public}"

MOODLE_COMPOSE_SERVICE="${MOODLE_COMPOSE_SERVICE:-webserver}"
WWW_DATA_UID="${WWW_DATA_UID:-33}"

LERNHIVE_DOMAIN="${LERNHIVE_DOMAIN:-}"
LETSENCRYPT_EMAIL="${LETSENCRYPT_EMAIL:-}"

LERNHIVE_DIR="$INSTALL_DIR/lernhive"
MOODLE_DOCKER_DIR="$INSTALL_DIR/moodle-docker"
MOODLE_DIR="$INSTALL_DIR/moodle"
CADDY_DIR="$INSTALL_DIR/caddy"

# ---------------------------------------------------------------------------
# Output helpers.
# ---------------------------------------------------------------------------
c_blue='\033[1;34m'
c_green='\033[1;32m'
c_yellow='\033[1;33m'
c_red='\033[1;31m'
c_reset='\033[0m'

step() { printf "\n${c_blue}==>${c_reset} %s\n" "$*"; }
ok()   { printf "${c_green}✓${c_reset} %s\n" "$*"; }
skip() { printf "${c_yellow}·${c_reset} %s\n" "$*"; }
warn() { printf "${c_yellow}!${c_reset} %s\n" "$*" >&2; }
die()  { printf "${c_red}✗${c_reset} %s\n" "$*" >&2; exit 1; }

# ---------------------------------------------------------------------------
# Preflight.
# ---------------------------------------------------------------------------
step "Preflight checks"

[[ $EUID -eq 0 ]] || die "provision.sh must run as root (use sudo)"

if ! grep -qE '^(ID=ubuntu|ID_LIKE=.*ubuntu)' /etc/os-release 2>/dev/null; then
  warn "This script is designed for Ubuntu. Other distros may work but are untested."
fi

UBUNTU_VERSION="$(. /etc/os-release 2>/dev/null && echo "${VERSION_ID:-unknown}")"
case "$UBUNTU_VERSION" in
  22.04|24.04) ok "Ubuntu $UBUNTU_VERSION detected" ;;
  *)           warn "Ubuntu $UBUNTU_VERSION is not in the tested list (22.04 / 24.04)" ;;
esac

ARCH="$(dpkg --print-architecture 2>/dev/null || echo unknown)"
[[ "$ARCH" == "amd64" ]] || warn "Tested on amd64 only; detected: $ARCH"

# Caddy requires both a domain and an e-mail or none at all.
if [[ -n "$LERNHIVE_DOMAIN" && -z "$LETSENCRYPT_EMAIL" ]]; then
  die "LERNHIVE_DOMAIN is set but LETSENCRYPT_EMAIL is empty. Provide both or neither."
fi
if [[ -z "$LERNHIVE_DOMAIN" && -n "$LETSENCRYPT_EMAIL" ]]; then
  die "LETSENCRYPT_EMAIL is set but LERNHIVE_DOMAIN is empty. Provide both or neither."
fi
if [[ -n "$LERNHIVE_DOMAIN" ]]; then
  ok "Caddy/TLS enabled for $LERNHIVE_DOMAIN (LE contact: $LETSENCRYPT_EMAIL)"
else
  warn "No LERNHIVE_DOMAIN set — Moodle will be HTTP-only on localhost:8000."
fi

# ---------------------------------------------------------------------------
# 1. Base packages.
# ---------------------------------------------------------------------------
step "Installing base packages"

export DEBIAN_FRONTEND=noninteractive
apt-get update -q
apt-get install -y --no-install-recommends \
  ca-certificates \
  curl \
  git \
  gnupg \
  lsb-release \
  ufw \
  rsync \
  tar \
  jq \
  unattended-upgrades
ok "Base packages installed"

# ---------------------------------------------------------------------------
# 2. Docker CE (via official Docker apt repo).
# ---------------------------------------------------------------------------
step "Installing Docker CE"

if command -v docker >/dev/null 2>&1; then
  skip "docker already installed ($(docker --version))"
else
  install -m 0755 -d /etc/apt/keyrings
  curl -fsSL https://download.docker.com/linux/ubuntu/gpg \
    | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
  chmod a+r /etc/apt/keyrings/docker.gpg

  UBUNTU_CODENAME="$(. /etc/os-release && echo "$VERSION_CODENAME")"
  echo \
    "deb [arch=$ARCH signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu $UBUNTU_CODENAME stable" \
    > /etc/apt/sources.list.d/docker.list

  apt-get update -q
  apt-get install -y \
    docker-ce \
    docker-ce-cli \
    containerd.io \
    docker-buildx-plugin \
    docker-compose-plugin
  systemctl enable --now docker
  ok "Docker installed: $(docker --version)"
fi

# ---------------------------------------------------------------------------
# 3. Deploy user with SSH access.
# ---------------------------------------------------------------------------
step "Creating deploy user: $DEPLOY_USER"

if id "$DEPLOY_USER" >/dev/null 2>&1; then
  skip "user $DEPLOY_USER already exists"
else
  useradd --create-home --shell /bin/bash "$DEPLOY_USER"
  ok "user $DEPLOY_USER created"
fi

usermod -aG docker "$DEPLOY_USER"
ok "$DEPLOY_USER added to docker group"

install -d -m 0700 -o "$DEPLOY_USER" -g "$DEPLOY_USER" \
  "/home/$DEPLOY_USER/.ssh"

AUTH_KEYS="/home/$DEPLOY_USER/.ssh/authorized_keys"
touch "$AUTH_KEYS"
chown "$DEPLOY_USER:$DEPLOY_USER" "$AUTH_KEYS"
chmod 0600 "$AUTH_KEYS"

if [[ -n "$DEPLOY_SSH_PUBKEY" ]]; then
  if grep -qxF "$DEPLOY_SSH_PUBKEY" "$AUTH_KEYS"; then
    skip "SSH pubkey already authorised for $DEPLOY_USER"
  else
    echo "$DEPLOY_SSH_PUBKEY" >> "$AUTH_KEYS"
    ok "SSH pubkey added to $AUTH_KEYS"
  fi
else
  warn "DEPLOY_SSH_PUBKEY not set — $DEPLOY_USER has no SSH key yet."
  warn "Add one later with: echo 'KEY' >> $AUTH_KEYS"
fi

# ---------------------------------------------------------------------------
# 4. Clone repositories.
# ---------------------------------------------------------------------------
step "Cloning repositories into $INSTALL_DIR"

install -d -m 0755 "$INSTALL_DIR"

clone_or_update() {
  local url="$1" dir="$2" branch="$3"
  # Run git with a per-command safe.directory override so root can fetch
  # on repos owned by the deploy user (or uid 33 for the Moodle tree)
  # without needing to edit global gitconfig.
  if [[ -d "$dir/.git" ]]; then
    skip "repo $dir already present (git fetch --quiet)"
    git -c safe.directory="$dir" -C "$dir" fetch --quiet origin
  else
    git clone --quiet --branch "$branch" "$url" "$dir"
    ok "cloned $url → $dir ($branch)"
  fi
}

clone_or_update "$LERNHIVE_REPO_URL"   "$LERNHIVE_DIR"      "$LERNHIVE_REPO_BRANCH"
clone_or_update "$MOODLE_DOCKER_REPO"  "$MOODLE_DOCKER_DIR" "$MOODLE_DOCKER_BRANCH"
clone_or_update "$MOODLE_REPO"         "$MOODLE_DIR"        "$MOODLE_BRANCH"

# Make the lernhive workspace owned by deploy so it can git pull.
chown -R "$DEPLOY_USER:$DEPLOY_USER" "$LERNHIVE_DIR"

# ---------------------------------------------------------------------------
# 4b. Permission wiring for the Moodle bind-mount.
# ---------------------------------------------------------------------------
# moodle-docker bind-mounts $MOODLE_DIR into the webserver container at
# /var/www/html. Inside the container, php-fpm / apache runs as www-data
# (uid 33 by default). For install.php to be able to create config.php and
# for deploy.sh to tar plugin files into the Moodle tree, uid 33 must own
# (or at least be able to write to) $MOODLE_DIR on the host.
#
# We also create a matching www-data group on the host (if missing) and add
# the deploy user to it, so plugin deploys run by the deploy user can still
# modify files that end up owned by uid 33.
step "Wiring host permissions for Moodle bind-mount"

if ! getent group "$WWW_DATA_UID" >/dev/null; then
  groupadd -g "$WWW_DATA_UID" www-data
  ok "created host group www-data (gid $WWW_DATA_UID)"
else
  skip "host group $(getent group "$WWW_DATA_UID" | cut -d: -f1) (gid $WWW_DATA_UID) already exists"
fi

if ! id -nG "$DEPLOY_USER" | tr ' ' '\n' | grep -qx "$(getent group "$WWW_DATA_UID" | cut -d: -f1)"; then
  usermod -aG "$WWW_DATA_UID" "$DEPLOY_USER"
  ok "$DEPLOY_USER added to host group gid $WWW_DATA_UID"
else
  skip "$DEPLOY_USER already in gid $WWW_DATA_UID group"
fi

# Give uid 33 ownership of the Moodle tree and flip on setgid so new files
# inherit the group — otherwise deploy.sh's tar-extract would write root-owned
# files and leave $MOODLE_DIR in a mixed-ownership state.
chown -R "$WWW_DATA_UID:$WWW_DATA_UID" "$MOODLE_DIR"
find "$MOODLE_DIR" -type d -exec chmod 2775 {} \;
find "$MOODLE_DIR" -type f -exec chmod g+rw {} \;
ok "$MOODLE_DIR chowned to uid $WWW_DATA_UID, setgid dirs, group-writable"

# ---------------------------------------------------------------------------
# 5. moodle-docker environment config.
# ---------------------------------------------------------------------------
step "Configuring moodle-docker env"

ENV_FILE="$MOODLE_DOCKER_DIR/.env.local"
if [[ -f "$ENV_FILE" ]]; then
  skip ".env.local already present — leaving untouched"
else
  cat > "$ENV_FILE" <<EOF
# Generated by playbooks/provision.sh on $(date -Iseconds)
MOODLE_DOCKER_DB=pgsql
MOODLE_DOCKER_WWWROOT=$MOODLE_DIR
MOODLE_DOCKER_PHP_VERSION=8.2
COMPOSE_PROJECT_NAME=lernhive
EOF
  ok ".env.local written"
fi

# ---------------------------------------------------------------------------
# 6. Start the stack.
# ---------------------------------------------------------------------------
step "Starting docker-compose stack"

cd "$MOODLE_DOCKER_DIR"
# moodle-docker ships bin/moodle-docker-compose which sources .env.local.
# shellcheck disable=SC1091
set -a; source "$ENV_FILE"; set +a

if docker ps --format '{{.Names}}' | grep -q '^lernhive-'; then
  skip "lernhive-* containers already running"
else
  bin/moodle-docker-compose up -d
  ok "docker-compose stack started"
fi

CONTAINER_NAME="lernhive-${MOODLE_COMPOSE_SERVICE}-1"

# Wait for the webserver container to be healthy.
for _ in {1..30}; do
  if docker ps --format '{{.Names}}' | grep -qx "$CONTAINER_NAME"; then
    break
  fi
  sleep 1
done

if ! docker ps --format '{{.Names}}' | grep -qx "$CONTAINER_NAME"; then
  warn "Container $CONTAINER_NAME not visible after 30s — check 'docker ps'"
fi

# ---------------------------------------------------------------------------
# 7. Install Moodle (first run only).
# ---------------------------------------------------------------------------
step "Initial Moodle install (first run only)"

# The in-container path to config.php depends on Moodle version:
#   Moodle 4.x: /var/www/html/config.php
#   Moodle 5.x: /var/www/html/public/config.php
CONFIG_PHP_PATH="$MOODLE_DOCROOT/config.php"
CLI_INSTALL_PATH="$MOODLE_DOCROOT/admin/cli/install.php"

# Initial wwwroot is deliberately localhost — we'll rewrite it to the public
# FQDN in the Caddy/TLS step below. Using hostname -f would bake an invalid
# hostname (e.g. ubuntu-8gb-nbg1-1) into config.php.
INITIAL_WWWROOT="http://localhost:8000"

if docker exec -u www-data "$CONTAINER_NAME" test -f "$CONFIG_PHP_PATH" 2>/dev/null; then
  skip "Moodle already installed ($CONFIG_PHP_PATH present)"
else
  # moodle-docker provides a helper for first-time install.
  if [[ -x "$MOODLE_DOCKER_DIR/bin/moodle-docker-wait-for-db" ]]; then
    "$MOODLE_DOCKER_DIR/bin/moodle-docker-wait-for-db"
  fi

  # Run install.php in non-interactive mode. Failure here is fatal — a
  # half-installed Moodle is worse than a clean error.
  if ! docker exec -u www-data "$CONTAINER_NAME" php "$CLI_INSTALL_PATH" \
      --non-interactive \
      --agree-license \
      --wwwroot="$INITIAL_WWWROOT" \
      --dataroot=/var/www/moodledata \
      --dbtype=pgsql \
      --dbhost=db \
      --dbname=moodle \
      --dbuser=moodle \
      --dbpass=m@0dl3ing \
      --fullname="LernHive" \
      --shortname="lernhive" \
      --adminuser=admin \
      --adminpass="ChangeMe!1" \
      --adminemail=admin@lernhive.de; then
    die "install.php failed. Inspect the output above, then re-run provision.sh."
  fi
  ok "Moodle installed at $CONFIG_PHP_PATH"
fi

# ---------------------------------------------------------------------------
# 8. Install server-deploy wrapper as /usr/local/bin/lernhive-deploy.
# ---------------------------------------------------------------------------
step "Installing lernhive-deploy command"

TARGET_LINK="/usr/local/bin/lernhive-deploy"
SOURCE_SCRIPT="$LERNHIVE_DIR/playbooks/server-deploy.sh"

if [[ ! -f "$SOURCE_SCRIPT" ]]; then
  warn "$SOURCE_SCRIPT not found — skipping symlink (old repo layout?)"
else
  chmod +x "$SOURCE_SCRIPT"
  ln -sfn "$SOURCE_SCRIPT" "$TARGET_LINK"
  ok "$TARGET_LINK → $SOURCE_SCRIPT"
fi

# Also ensure deploy.sh is executable.
if [[ -f "$LERNHIVE_DIR/playbooks/deploy.sh" ]]; then
  chmod +x "$LERNHIVE_DIR/playbooks/deploy.sh"
fi

# ---------------------------------------------------------------------------
# 8b. Caddy reverse proxy (only if LERNHIVE_DOMAIN is set).
# ---------------------------------------------------------------------------
# We install Caddy as its own docker-compose stack in $CADDY_DIR that
# attaches to the moodle-docker network. This keeps TLS termination out of
# the moodle-docker stack and means re-running `moodle-docker-compose up`
# won't wipe our Caddy config.
if [[ -n "$LERNHIVE_DOMAIN" ]]; then
  step "Installing Caddy reverse proxy ($LERNHIVE_DOMAIN)"

  CADDY_SRC="$LERNHIVE_DIR/playbooks/caddy"
  if [[ ! -f "$CADDY_SRC/docker-compose.yml" || ! -f "$CADDY_SRC/Caddyfile" ]]; then
    die "Caddy template files missing in $CADDY_SRC — pull latest main?"
  fi

  install -d -m 0755 "$CADDY_DIR"
  install -m 0644 "$CADDY_SRC/docker-compose.yml" "$CADDY_DIR/docker-compose.yml"
  install -m 0644 "$CADDY_SRC/Caddyfile"          "$CADDY_DIR/Caddyfile"
  ok "Caddy template files copied to $CADDY_DIR"

  # Discover the moodle-docker network name (e.g. "lernhive_default").
  MOODLE_NETWORK="$(docker network ls --format '{{.Name}}' \
                     | grep -E "^${COMPOSE_PROJECT_NAME:-lernhive}_" \
                     | head -1 || true)"
  if [[ -z "$MOODLE_NETWORK" ]]; then
    # Fallback: inspect the running webserver container directly.
    MOODLE_NETWORK="$(docker inspect -f \
      '{{range $k, $_ := .NetworkSettings.Networks}}{{$k}}{{"\n"}}{{end}}' \
      "$CONTAINER_NAME" 2>/dev/null | head -1 || true)"
  fi
  [[ -n "$MOODLE_NETWORK" ]] || die "Could not detect moodle-docker Docker network."
  ok "moodle-docker network: $MOODLE_NETWORK"

  # Webserver listens on port 8000 inside the container (moodle-docker default).
  MOODLE_UPSTREAM="${MOODLE_COMPOSE_SERVICE}:8000"

  cat > "$CADDY_DIR/.env" <<EOF
# Generated by provision.sh on $(date -Iseconds). Do not commit.
LERNHIVE_DOMAIN=$LERNHIVE_DOMAIN
LETSENCRYPT_EMAIL=$LETSENCRYPT_EMAIL
MOODLE_NETWORK=$MOODLE_NETWORK
MOODLE_UPSTREAM=$MOODLE_UPSTREAM
EOF
  chmod 0600 "$CADDY_DIR/.env"
  ok "$CADDY_DIR/.env rendered"

  # Stop any host service occupying port 80 (the Hetzner Ubuntu image ships
  # with nothing on 80 by default, but we double-check with lsof).
  if command -v lsof >/dev/null && lsof -iTCP:80 -sTCP:LISTEN -P -n 2>/dev/null \
      | grep -qv '^COMMAND'; then
    warn "Something is already listening on :80. Caddy may fail to start."
  fi

  # Bring Caddy up (idempotent).
  if docker ps --format '{{.Names}}' | grep -qx "lernhive-caddy"; then
    (cd "$CADDY_DIR" && docker compose up -d)
    skip "lernhive-caddy already running — reloaded config"
  else
    (cd "$CADDY_DIR" && docker compose up -d)
    ok "Caddy stack started"
  fi
else
  step "Skipping Caddy install (no LERNHIVE_DOMAIN)"
fi

# ---------------------------------------------------------------------------
# 8c. Patch Moodle config.php for the public FQDN + SSL proxy.
# ---------------------------------------------------------------------------
if [[ -n "$LERNHIVE_DOMAIN" ]]; then
  step "Patching Moodle config.php for https://$LERNHIVE_DOMAIN"

  # Rewrite wwwroot. Moodle writes it as:
  #   $CFG->wwwroot   = 'http://localhost:8000';
  # We replace the single-quoted URL with our public HTTPS URL.
  docker exec "$CONTAINER_NAME" sh -c "
    set -e
    f=$CONFIG_PHP_PATH
    # wwwroot:
    sed -i \"s|\\\$CFG->wwwroot\\s*=\\s*'[^']*';|\\\$CFG->wwwroot   = 'https://$LERNHIVE_DOMAIN';|\" \"\$f\"
    # sslproxy: insert right after wwwroot if not already present.
    grep -q 'sslproxy' \"\$f\" || \
      sed -i \"/\\\$CFG->wwwroot/a \\\\\$CFG->sslproxy  = true;\" \"\$f\"
  " && ok "config.php rewritten (wwwroot + sslproxy)"
fi

# ---------------------------------------------------------------------------
# 9. Firewall.
# ---------------------------------------------------------------------------
step "Configuring UFW firewall"

ufw --force default deny incoming
ufw --force default allow outgoing
ufw allow 22/tcp    comment 'ssh'   >/dev/null
ufw allow 80/tcp    comment 'http'  >/dev/null
ufw allow 443/tcp   comment 'https' >/dev/null

if ufw status | grep -q "Status: active"; then
  skip "ufw already active"
else
  ufw --force enable
  ok "ufw enabled"
fi

# ---------------------------------------------------------------------------
# 10. Summary.
# ---------------------------------------------------------------------------
step "Provisioning complete"

if [[ -n "$LERNHIVE_DOMAIN" ]]; then
  PUBLIC_URL="https://$LERNHIVE_DOMAIN"
  TLS_LINE="Caddy              : lernhive-caddy (Let's Encrypt for $LERNHIVE_DOMAIN)"
else
  PUBLIC_URL="http://localhost:8000 (via SSH tunnel only)"
  TLS_LINE="Caddy              : not installed (no LERNHIVE_DOMAIN)"
fi

cat <<EOF

  LernHive workspace : $LERNHIVE_DIR
  Moodle source      : $MOODLE_DIR ($MOODLE_BRANCH)
  Moodle docroot     : $MOODLE_DOCROOT
  moodle-docker      : $MOODLE_DOCKER_DIR
  Docker stack       : lernhive-*
  Webserver container: $CONTAINER_NAME
  Deploy user        : $DEPLOY_USER
  Deploy command     : $TARGET_LINK
  $TLS_LINE
  Public URL         : $PUBLIC_URL
  UFW                : $(ufw status | head -1)

  Next steps:
    1. Change the admin password RIGHT NOW:
         docker exec -u www-data $CONTAINER_NAME \\
           php $MOODLE_DOCROOT/admin/cli/reset_password.php \\
           --username=admin --password='<something-long-and-random>'

    2. Deploy the LernHive plugins (as $DEPLOY_USER):
         sudo -u $DEPLOY_USER lernhive-deploy

    3. Smoke-test SSH from your workstation:
         ssh -i ~/.ssh/lernhive-deploy $DEPLOY_USER@<server-ip> \\
             'whoami && which lernhive-deploy'

    4. Configure the four GitHub Actions secrets and push to main to
       trigger the CI/CD loop. See playbooks/README.md for the full list.

  Admin user created (if Moodle install ran): admin / ChangeMe!1
  CHANGE THE ADMIN PASSWORD immediately — this default is baked into
  the provisioning script and known to anyone reading it.

EOF
