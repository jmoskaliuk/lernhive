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
#   # …or with overrides:
#   MOODLE_BRANCH=MOODLE_405_STABLE \
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
#   MOODLE_REPO            default: https://git.moodle.org/moodle.git
#   MOODLE_BRANCH          default: MOODLE_405_STABLE
#                          (for 5.2 beta use: MOODLE_502_STABLE)
#
#   MOODLE_COMPOSE_SERVICE default: webserver
#                          (becomes CONTAINER suffix, e.g. <stack>-webserver-1)
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
MOODLE_REPO="${MOODLE_REPO:-https://git.moodle.org/moodle.git}"
MOODLE_BRANCH="${MOODLE_BRANCH:-MOODLE_405_STABLE}"

MOODLE_COMPOSE_SERVICE="${MOODLE_COMPOSE_SERVICE:-webserver}"

LERNHIVE_DIR="$INSTALL_DIR/lernhive"
MOODLE_DOCKER_DIR="$INSTALL_DIR/moodle-docker"
MOODLE_DIR="$INSTALL_DIR/moodle"

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
  if [[ -d "$dir/.git" ]]; then
    skip "repo $dir already present (git fetch --quiet)"
    git -C "$dir" fetch --quiet origin
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

if docker exec -u www-data "$CONTAINER_NAME" test -f /var/www/html/config.php 2>/dev/null; then
  skip "Moodle already installed (config.php present)"
else
  # moodle-docker provides a helper for first-time install.
  if [[ -x "$MOODLE_DOCKER_DIR/bin/moodle-docker-wait-for-db" ]]; then
    "$MOODLE_DOCKER_DIR/bin/moodle-docker-wait-for-db"
  fi
  # Run install.php in non-interactive mode.
  docker exec -u www-data "$CONTAINER_NAME" php /var/www/html/admin/cli/install.php \
    --non-interactive \
    --agree-license \
    --wwwroot="https://$(hostname -f 2>/dev/null || echo localhost)" \
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
    --adminemail=admin@lernhive.de \
  || warn "install.php failed — may need manual attention"
  ok "Moodle install attempted"
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

cat <<EOF

  LernHive workspace : $LERNHIVE_DIR
  Moodle source      : $MOODLE_DIR ($MOODLE_BRANCH)
  moodle-docker      : $MOODLE_DOCKER_DIR
  Docker stack       : lernhive-*
  Webserver container: $CONTAINER_NAME
  Deploy user        : $DEPLOY_USER
  Deploy command     : $TARGET_LINK
  UFW                : $(ufw status | head -1)

  Next steps:
    1. Verify playbooks/deploy.hetzner.env matches actual paths:
         docker exec -it $CONTAINER_NAME ls /var/www
       Adjust MOODLE_ROOT / MOODLE_CLI_ROOT if needed.

    2. Run a first deploy manually (as $DEPLOY_USER):
         sudo -u $DEPLOY_USER lernhive-deploy

    3. Configure reverse proxy + HTTPS (Caddy/Nginx) — separate step,
       not handled by this script.

    4. Add the GitHub Actions deploy key to $DEPLOY_USER's authorized_keys:
         # on the server:
         echo 'ssh-ed25519 AAAA…' >> /home/$DEPLOY_USER/.ssh/authorized_keys

  Admin user created (if Moodle install ran): admin / ChangeMe!1
  CHANGE THE ADMIN PASSWORD immediately.

EOF
