# Running PHPUnit and Behat on Hetzner

This runbook covers the **one-time server setup** required before the
`test-hetzner.yml` GitHub Actions workflow can drive PHPUnit and Behat
runs against the LernHive Moodle container on Hetzner.

> Target: `lernhive-webserver-1` on `dev.lernhive.de`
> Moodle: 5.1 with the public/ split (see `hetzner_moodle_layout.md`)

## Architecture

```
GitHub push main
      │
      ├─► deploy-hetzner.yml ──SSH──► lernhive-deploy ──► playbooks/deploy.sh
      │                                                        ↓
      │                                                   plugin files
      │                                                        ↓
      │                                             /var/www/html/public/...
      │
      └─► test-hetzner.yml ──SSH──► lernhive-test ──► playbooks/test.sh
                                                           ↓
                                        phpunit  ┌────────┴────────┐  behat
                                                 ▼                 ▼
                                      phpunit_prefix DB    bht_prefix DB
                                      phpunit dataroot     behat dataroot
                                                                   ↕
                                                            Selenium container
```

Both deploy and test use the same repo checkout on the server
(`/opt/lernhive` by default) and the same `lernhive-webserver-1`
container; they just call different admin/cli scripts inside it.
Production Moodle data is never touched because PHPUnit and Behat live
on their own DB prefixes and their own datarooots.

## Prerequisites (one-time)

### 1. `config.php` test block

The authoritative `config.php` lives at `/var/www/html/config.php`
(not `/var/www/html/public/config.php` — that one is only the public/
loader stub). SSH into the server, open that file with your editor
of choice, and add the following block **before** the final
`require_once(__DIR__ . '/lib/setup.php');` line:

```php
// ---- PHPUnit ----------------------------------------------------------
// Separate DB prefix + dataroot so tests never touch production data.
$CFG->phpunit_prefix   = 'phpu_';
$CFG->phpunit_dataroot = '/var/www/moodledata_phpunit';

// ---- Behat ------------------------------------------------------------
$CFG->behat_prefix     = 'bht_';
$CFG->behat_dataroot   = '/var/www/moodledata_behat';
// Behat spins up a dedicated test site under this URL. It must be
// reachable from inside the Selenium container. Use the internal
// docker network hostname, not the public dev.lernhive.de URL — the
// public URL goes through Caddy and serves the production Moodle.
$CFG->behat_wwwroot    = 'http://lernhive-webserver-1';

// Selenium profile — points at the selenium container on the shared
// docker network. Adjust the hostname if your docker-compose names it
// differently.
$CFG->behat_profiles = [
    'default' => [
        'browser' => 'chrome',
        'wd_host' => 'http://selenium:4444/wd/hub',
        'capabilities' => [
            'extra_capabilities' => [
                'goog:chromeOptions' => [
                    'args' => ['--no-sandbox', '--disable-dev-shm-usage'],
                ],
            ],
        ],
    ],
];
```

Then, still as root:

```bash
# Create and hand the datarooots to the Moodle user.
install -d -o www-data -g www-data /var/www/moodledata_phpunit
install -d -o www-data -g www-data /var/www/moodledata_behat
```

### 2. Verify composer dependencies inside the container

```bash
docker exec -u www-data lernhive-webserver-1 bash -lc '
  cd /var/www/html && \
  test -x vendor/bin/phpunit && \
  test -x vendor/bin/behat && \
  echo "ok: phpunit + behat available"
'
```

If either binary is missing:

```bash
docker exec -u www-data lernhive-webserver-1 bash -lc '
  cd /var/www/html && composer install --no-interaction
'
```

### 3. Verify the Selenium container is reachable

```bash
docker exec lernhive-webserver-1 curl -fsS http://selenium:4444/status | head
```

Expected: `"ready":true`. If the hostname differs (e.g. `selenium-hub`
or `chrome`), update `$CFG->behat_profiles['default']['wd_host']`
accordingly.

### 4. Install the `lernhive-test` wrapper

If the server was provisioned **before** this runbook landed:

```bash
cd /opt/lernhive
git fetch origin main && git reset --hard origin/main
sudo bash playbooks/provision.sh
# provision.sh is idempotent — it only re-creates the symlinks.
```

Verify:

```bash
which lernhive-test
ls -l /usr/local/bin/lernhive-test
```

Expected: symlink pointing at
`/opt/lernhive/playbooks/server-test.sh`.

### 5. First manual init run

```bash
sudo -u deploy lernhive-test --suite=phpunit --reinit
sudo -u deploy lernhive-test --suite=behat   --reinit
```

This builds the initial PHPUnit/Behat test DBs and datarooots. Expect
~5–10s for PHPUnit, ~1–2 min for Behat. Re-runs after plugin deploys
are much faster because the envs are cached.

## Running tests from GitHub

### Automatic — push to main

Any push to `main` that touches `plugins/**`, `playbooks/test*`, or
the test workflow itself kicks off `.github/workflows/test-hetzner.yml`
which runs PHPUnit and Behat back-to-back on Hetzner. Deploy and test
run in parallel (different concurrency groups) so a green deploy never
blocks a red test (or vice versa).

### Manual — workflow_dispatch

Actions tab → "Test on Hetzner" → Run workflow. Available inputs:

| Input | Purpose |
|---|---|
| `suite` | `all`, `phpunit`, or `behat` — default `all` |
| `component` | Frankenstyle filter, e.g. `local_lernhive_contenthub`. Applies to both suites. |
| `tags` | Behat-only tag expression; overrides `component` for Behat. Example: `@javascript&&@local_lernhive_contenthub` |
| `reinit` | Force rebuild of the test envs. Use after schema/fixture changes. |

## Troubleshooting

**`PHPUnit config keys missing in config.php`** — step 1 was skipped or
edited the wrong file. Make sure you edited `/var/www/html/config.php`,
not the `public/` stub.

**`Behat init failed`** — almost always either missing datarooot (step
1 bottom), Selenium unreachable (step 3), or `behat_wwwroot` pointing
at a URL Selenium can't resolve. Use `docker exec selenium curl -v
$CFG->behat_wwwroot` inside the selenium container to debug.

**Stale DB after plugin schema change** — run with `--reinit` (from CI:
check the `reinit` input in workflow_dispatch).

**`lernhive-test: command not found`** — the symlink wasn't installed.
Re-run `sudo bash playbooks/provision.sh`.

## Why two separate workflows

`deploy-hetzner.yml` and `test-hetzner.yml` intentionally live side by
side rather than chained via `needs:`. Chaining would mean a red test
blocks the deploy pipeline, and a broken deploy poisons the test graph
with red that isn't really the code's fault. Keeping them separate:

- Deploy is the fast loop: plugin files land on the server in seconds.
- Test is the slower, more thorough loop that validates the same
  commit post-deploy.
- A failed PHPUnit run is visible in Actions next to the successful
  deploy, which is the correct mental model for "deploy worked, tests
  caught a regression".
- `concurrency: deploy-hetzner` and `concurrency: test-hetzner` are
  separate groups, so a deploy and a test can run simultaneously
  without contention (different docker commands, different DB
  prefixes).
