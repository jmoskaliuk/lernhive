# playbooks/

Executable automation for the LernHive workspace — deploy scripts and Claude
prompt playbooks. "Playbook" here means "a reusable procedure", regardless of
whether it's executed by bash or by Claude.

## What lives here

| File                  | Purpose                                              |
|-----------------------|------------------------------------------------------|
| `provision.sh`        | One-shot server provisioning: Docker + Moodle + workspace |
| `deploy.sh`           | Generic plugin deploy script (local + Hetzner)       |
| `deploy.local.env`    | Config for `--target=local` (OrbStack dev setup)     |
| `deploy.hetzner.env`  | Config for `--target=hetzner` (production)           |
| `server-deploy.sh`    | Thin wrapper installed on the server as `lernhive-deploy` |

Claude prompt playbooks will be added here as `.md` files over time
(e.g. `release-checklist.md`, `onboard-new-plugin.md`).

## provision.sh — one-shot server bootstrap

Run this on a **fresh Ubuntu 22.04 / 24.04 host** (as root) to turn it into
a working LernHive Moodle server. It's idempotent — re-running it is safe.

```bash
# On the target server, as root:
curl -fsSL https://raw.githubusercontent.com/jmoskaliuk/lernhive/main/playbooks/provision.sh | bash

# …or with overrides (e.g. Moodle 5.2 beta, provide deploy key):
MOODLE_BRANCH=MOODLE_502_STABLE \
DEPLOY_SSH_PUBKEY="ssh-ed25519 AAAA… github-actions@lernhive" \
bash provision.sh
```

What it does:

1. Installs base packages + Docker CE (official Docker apt repo)
2. Creates `deploy` user, adds it to the `docker` group, installs SSH pubkey
3. Clones `lernhive` workspace → `/opt/lernhive`
4. Clones `moodle-docker` → `/opt/moodle-docker` and Moodle source → `/opt/moodle`
5. Writes `moodle-docker/.env.local` with sensible defaults
6. Starts the docker-compose stack (`lernhive-*`)
7. Runs `admin/cli/install.php` non-interactively on first provision
8. Symlinks `playbooks/server-deploy.sh` → `/usr/local/bin/lernhive-deploy`
9. Configures UFW (allow 22/80/443, deny everything else)
10. Prints a summary + next steps

Overridable via environment variables: `LERNHIVE_REPO_URL`,
`LERNHIVE_REPO_BRANCH`, `INSTALL_DIR`, `DEPLOY_USER`, `DEPLOY_SSH_PUBKEY`,
`MOODLE_BRANCH`, `MOODLE_DOCKER_BRANCH`, `MOODLE_COMPOSE_SERVICE`.

After provisioning, reverse-proxy + HTTPS (Caddy / Nginx) is a **separate**
step, because it needs the public DNS entry to be live.

## deploy.sh — how it works

`deploy.sh` is the single source of truth for "ship plugin code into a running
Moodle container". It works identically for local dev and production because
the only things that change between environments live in the sibling `.env`
config files:

- `CONTAINER` — docker container name running Moodle
- `MOODLE_ROOT` — Moodle public docroot inside the container
- `MOODLE_CLI_ROOT` — path for `admin/cli/*.php`
- `MOODLE_USER` — Linux user inside the container (default: `www-data`)

### Plugin auto-discovery

The script scans `plugins/*/` and treats any directory containing a
`version.php` as deployable. Directories without `version.php` (docs-only
scaffolds) are silently skipped. This means you can add a new plugin by
dropping its code into `plugins/<name>/` — no changes to the deploy script
needed.

Currently deployable plugins: `local_lernhive`, `local_lernhive_flavour`,
`local_lernhive_onboarding`, `theme_lernhive`.

### Component → Moodle path mapping

Frankenstyle component names are mapped to Moodle subpaths automatically:

```
local_lernhive              → local/lernhive
local_lernhive_onboarding   → local/lernhive_onboarding
theme_lernhive              → theme/lernhive
mod_foo                     → mod/foo
block_foo                   → blocks/foo    (note the plural)
tool_foo                    → admin/tool/foo
```

Supported prefixes: `local`, `theme`, `mod`, `block`, `auth`, `enrol`,
`filter`, `qtype`, `qformat`, `qbehaviour`, `report`, `repository`, `tool`,
`format`, `gradeexport`, `gradeimport`, `gradereport`. Unknown prefixes are
rejected with a clear error.

### Excluded from deploy payload

Per plugin, `tar --exclude` filters out:

- `docs/` — the plugin's DevFlow docs stay in the workspace, never shipped
- `.git`, `node_modules`, `.DS_Store`, `*.swp`, `*.bak`

### Deploy pipeline

For each plugin (sequentially):

1. Resolve target path and run a safety check (never wipe `/`, `/var`,
   `MOODLE_ROOT`, etc.)
2. `mkdir -p` the target inside the container
3. Wipe existing contents (`find -mindepth 1 -delete`) so removed files
   actually disappear
4. `tar -C plugins/<name> .` piped into `docker exec -i <container> tar -x`
5. `chown -R www-data:www-data` the target

After all plugins are synced:

6. `php admin/cli/upgrade.php --non-interactive --no-cli-maintenance`
7. `php admin/cli/purge_caches.php`

Upgrade and purge can be skipped with `--no-upgrade` / `--no-purge`.

## Usage

### Local dev (OrbStack)

```bash
# Deploy everything
playbooks/deploy.sh --target=local

# Iterate on one plugin
playbooks/deploy.sh --target=local --plugin=local_lernhive_onboarding

# Quick sync without running upgrade (useful if you only changed templates/CSS)
playbooks/deploy.sh --target=local --no-upgrade
```

### Production (Hetzner)

You don't run `deploy.sh` directly on production. Instead:

1. GitHub Actions triggers on push to `main` (filtered to `plugins/**`)
2. It SSHes into the Hetzner server and runs `lernhive-deploy` (which is
   `server-deploy.sh` installed as `/usr/local/bin/lernhive-deploy` by
   `hetzner-setup.sh`)
3. `server-deploy.sh` does `git fetch && git reset --hard origin/main`,
   then `exec`s `playbooks/deploy.sh --target=hetzner`

Manual trigger from your laptop:

```bash
ssh deploy@lernhive.de lernhive-deploy
ssh deploy@lernhive.de lernhive-deploy --plugin=local_lernhive_onboarding
```

## GitHub Actions → Hetzner deploy loop

The `.github/workflows/deploy-hetzner.yml` workflow closes the CI/CD loop:
a `git push` to `main` that touches `plugins/**` or `playbooks/deploy*` auto-triggers
a deploy on the Hetzner server. You can also trigger it manually from the
GitHub UI (Actions → Deploy to Hetzner → Run workflow), optionally restricted
to a single plugin.

### One-time GitHub setup

Before the workflow works, four repository secrets must be configured.
Open **Settings → Secrets and variables → Actions → New repository secret**:

| Secret                 | Value                                                                                      |
|------------------------|--------------------------------------------------------------------------------------------|
| `HETZNER_HOST`         | Server hostname or IP (e.g. `178.104.117.88`, later `lernhive.de`)                         |
| `HETZNER_USER`         | SSH login user (matches `DEPLOY_USER` in `provision.sh`, default `deploy`)                 |
| `HETZNER_SSH_KEY`      | ed25519 **private** key (OpenSSH format, the full `-----BEGIN…-----` block)                |
| `HETZNER_KNOWN_HOSTS`  | Output of `ssh-keyscan -t ed25519 <HETZNER_HOST>` (one line starting with host or IP)      |

### Generating the deploy key pair

On your laptop (or any machine):

```bash
ssh-keygen -t ed25519 -f ~/.ssh/lernhive-deploy -N '' -C 'github-actions@lernhive'
cat ~/.ssh/lernhive-deploy.pub    # → paste into provision.sh DEPLOY_SSH_PUBKEY
cat ~/.ssh/lernhive-deploy        # → paste into GitHub secret HETZNER_SSH_KEY
```

Put the **public** key into the server (either via `provision.sh`'s
`DEPLOY_SSH_PUBKEY` env var, or by appending to `/home/deploy/.ssh/authorized_keys`
afterwards). Put the **private** key into the GitHub secret.

### Pinning the host key

After the server is reachable, capture its host key once so future deploys
can't be man-in-the-middled:

```bash
ssh-keyscan -t ed25519 178.104.117.88
```

Paste the entire output line into the `HETZNER_KNOWN_HOSTS` secret. If you
reinstall the server, refresh this secret — otherwise the workflow will
fail with a host key mismatch error (and that's the point).

### Workflow behaviour

- **Push trigger** — `git push` to `main` that touches these paths auto-deploys:
  - `plugins/**`
  - `playbooks/deploy.sh`, `playbooks/deploy.hetzner.env`, `playbooks/server-deploy.sh`
  - `.github/workflows/deploy-hetzner.yml`

  Other changes (docs in `plugins/*/docs/`, `product/`, `mockups/`) do **not**
  trigger a deploy — the path filter keeps the pipeline quiet when only docs
  move.

- **Manual trigger (workflow_dispatch)** — your "I know what I'm doing"
  button. Supports:
  - `plugin` — deploy only one plugin (e.g. `local_lernhive_onboarding`).
    Empty = deploy all.
  - `skip_upgrade` — pass `--no-upgrade` to `deploy.sh`
  - `skip_purge` — pass `--no-purge` to `deploy.sh`

  The plugin input is validated (`^[a-z][a-z0-9_]*$`) to block shell
  injection via the form field.

- **Concurrency** — only one deploy runs at a time. A second push during an
  in-flight deploy is queued, not cancelled. This prevents tar-pipe races
  inside the Moodle container.

- **Timeout** — 15 minutes. If a deploy legitimately takes longer, raise
  `timeout-minutes` in the workflow.

### Manual trigger without the UI

You can also drive workflow_dispatch from the `gh` CLI:

```bash
gh workflow run deploy-hetzner.yml
gh workflow run deploy-hetzner.yml -f plugin=local_lernhive_onboarding
gh workflow run deploy-hetzner.yml -f plugin=theme_lernhive -f skip_upgrade=true
```

## Adding a new deploy target

Create `playbooks/deploy.<targetname>.env` with the four required vars
(`CONTAINER`, `MOODLE_ROOT`, `MOODLE_CLI_ROOT`, `MOODLE_USER`) and invoke
`deploy.sh --target=<targetname>`. No code changes to `deploy.sh` required.

## Safety

The script refuses to wipe a target path if it equals `MOODLE_ROOT` or any
suspiciously broad path (`/`, `/var`, `/var/www`, `/var/www/html`). It also
requires the target to be at least two path segments deep inside
`MOODLE_ROOT`, so a malformed config can never accidentally `rm -rf` the
entire Moodle install.
