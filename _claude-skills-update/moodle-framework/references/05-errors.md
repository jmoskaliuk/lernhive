# Errors — Catalog of Known Pitfalls and Lessons Learned

**This file is the most important one in the framework.** Per the skill's
top-level rule, it should be loaded first on every Moodle task so that past
mistakes are not repeated. Each entry follows a consistent shape:

- **Symptom** — what you'll see when you hit it
- **Cause** — why it happens
- **Fix** — the minimum change that resolves it
- **Prevention** — the habit that stops it from recurring

The entries are grouped by phase (setup → dev → test → deploy → submission)
so you can scan quickly during active work.

---

## A. Environment & setup

### A1. `composer install` fails for www-data with "HOME not set"

**Symptom**
```
[RuntimeException]
The HOME or COMPOSER_HOME environment variable must be set for composer
to run correctly
```
when running `sudo -u www-data composer install` inside the webserver
container.

**Cause**
Composer requires a writable `$HOME` to cache dependencies. `www-data`
inside a Moodle Docker image has `/var/www` as its shell-less home and
no pre-existing `.composer/` dir.

**Fix**
```bash
docker exec -e HOME=/var/www/.composer <container> \
    sudo -u www-data composer install --no-interaction --no-progress
```
Or permanently: set `HOME=/var/www/.composer` in the container's
environment and `mkdir -p /var/www/.composer && chown www-data:www-data
/var/www/.composer`.

**Prevention**
Always pass `HOME` explicitly in deploy/test scripts when invoking
composer as `www-data`. Never assume `sudo -u` inherits sensible env.

---

### A2. `.claude/skills` is a read-only bindfs mount in Cowork

**Symptom**
`Write` to any file under `/sessions/<session>/mnt/.claude/skills/*`
fails with `EROFS: read-only file system`, even though `.claude/` itself
is writable.

**Cause**
Cowork mounts the skills directory specifically read-only to prevent
accidental corruption of active skill files by tools or the model.
`mount | grep skills` shows `fuse.bindfs (ro,...)`.

**Fix**
Use a staging directory inside a writable repo (e.g.
`<repo>/_claude-skills-update/`) for new/updated skill content. Promote
to the host `~/.claude/skills/` out-of-band using a plain `cp -R` or
`rsync -a` on the user's machine.

**Prevention**
Keep a `_claude-skills-update/` convention in the workspace repo so
iteration on skills never collides with the read-only mount. Always
check write-ability with a `touch` smoke-test before attempting larger
skill edits.

---

### A3. OPcache mask: edited file, old code still runs

**Symptom**
You `docker cp` a fixed PHP file into the Moodle container, reload the
page, and the old behavior is still there. `diff` on the file inside
the container shows the fix is present.

**Cause**
Production-like Moodle Docker images set `opcache.validate_timestamps=0`
to maximize throughput. PHP-FPM keeps the old compiled bytecode until
the worker restarts.

**Fix**
```bash
# Quick, per-deploy
docker exec <container> php -r 'opcache_reset();'

# Or restart PHP-FPM
docker compose restart webserver

# Or temporarily disable the cache for dev
echo 'opcache.validate_timestamps=1' >> /usr/local/etc/php/conf.d/opcache-dev.ini
echo 'opcache.revalidate_freq=0' >> /usr/local/etc/php/conf.d/opcache-dev.ini
```

**Prevention**
Make your deploy script always run `opcache_reset()` (or restart PHP-FPM)
after copying files. `moodle-deploy` and `playbooks/deploy.sh` should
both do this automatically — verify yours does.

---

### A4. Cowork sandbox egress allowlist blocks `api.github.com`

**Symptom**
`gh api`, `gh run view`, or `curl https://api.github.com/...` from
inside the Cowork shell fails with a DNS or timeout error. `curl
https://github.com/...` (git host) succeeds.

**Cause**
Cowork's egress firewall only permits `github.com` (for git protocol)
and a handful of explicitly configured hosts like `dev.lernhive.de`.
`api.github.com`, `raw.githubusercontent.com`, and `codeload.github.com`
are all blocked.

**Fix**
Don't use the GitHub REST API from inside Cowork. For CI result
retrieval, use the `ci/test-results` orphan-branch pattern (see
`01-workflow.md §5`) — the workflow publishes machine-readable results
to a branch that Claude can `git fetch` through the normal git protocol.

**Prevention**
Any workflow that Claude needs to observe must publish its results
through git, not through the API. Treat api.github.com as unreachable.

---

## B. Plugin structure & metadata

### B1. `version.php` regression on edit

**Symptom**
You bump `$plugin->version` to a new number, but after a later edit
you accidentally revert it. Moodle then refuses to upgrade with:
```
Cannot downgrade local_example from X to Y.
```

**Cause**
Rewriting `version.php` from memory instead of reading the current
value first. The model writes a plausible version that's lower than
what's already on disk/in main.

**Fix**
```bash
# Always check what's on main before writing a new version.php
git -C <repo> show origin/main:plugins/<name>/version.php | grep version
# Then write a version >= that one.
```

**Prevention**
**This is a recorded feedback memory:** when rewriting version.php,
the version number must be read from `origin/main` first and only
incremented, never typed from memory. The same applies to
`$plugin->release`.

---

### B2. Frankenstyle component vs directory name mismatch

**Symptom**
`admin/cli/upgrade.php` fails with:
```
Plugin "local_example" is defined as "local_example" but expected at
/path/to/moodle/local/foo
```
or the plugin installs but never appears in the admin UI.

**Cause**
The top-level directory inside your release ZIP (or the folder inside
`local/`) does not match the name portion of the frankenstyle. E.g.
`$plugin->component = 'local_example'` but the folder is `local/foo/`.

**Fix**
Match them exactly:
- Component: `local_example`
- Install path: `local/example/` (the type prefix `local_` is implied by
  the containing `local/` dir, NOT repeated in the leaf name)
- Workspace name: your multi-plugin monorepo SHOULD use `local_example/`
  as the directory name (frankenstyle), because that's what the deploy
  script strips `local_` from to compute the install path.

**Prevention**
Never rename plugin directories. The 1:1 mapping `plugins/local_X/` →
`public/local/X/` is what makes `playbooks/deploy.sh` and
`playbooks/test.sh` work without per-plugin configuration.

---

### B3. Privacy provider missing → QA bot instant bounce

**Symptom**
You submit a plugin and the QA bot posts:
```
Plugin is missing the privacy API implementation.
```

**Cause**
Moodle's privacy API is mandatory since 3.4, even for plugins that store
no user data. You must explicitly declare "I store nothing" via a
`null_provider`.

**Fix**
Create `classes/privacy/provider.php`:

```php
<?php
namespace local_example\privacy;

use core_privacy\local\metadata\null_provider;

class provider implements null_provider {
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
```

And in `lang/en/local_example.php`:
```php
$string['privacy:metadata'] = 'The local_example plugin does not store any personal data.';
```

**Prevention**
Generate `classes/privacy/provider.php` at scaffold time for every new
plugin, even before writing the first feature. Remove it only if you
intentionally upgrade to a real provider.

---

### B4. Capabilities in `access.php` missing lang strings

**Symptom**
`admin/cli/upgrade.php` runs cleanly, but the capability appears as
`local/example:manage` in the role-editing UI instead of a human name.
QA bot complains:
```
Missing string: local_example:manage
```

**Cause**
Every entry in `db/access.php` needs a matching `$string['example:manage']`
in `lang/en/<frankenstyle>.php` with a human-readable capability name,
plus optionally `example:manage_help`.

**Fix**
Add the string:
```php
$string['example:manage'] = 'Manage example items';
```
Purge caches. Bump `$plugin->version`.

**Prevention**
Write the lang string in the same commit as the capability. Run
`moodle-plugin-ci validate` before submission — it grep-checks for this.

---

### B5. `MOODLE_INTERNAL` check in the wrong place

**Symptom**
PHPCS with the Moodle standard emits:
```
moodle.Files.MoodleInternal.MoodleInternalGlobalState
```

**Cause**
`defined('MOODLE_INTERNAL') || die();` is required at the top of
procedural PHP files (`lib.php`, `locallib.php`, `db/*.php`,
`settings.php`). It must **not** be at the top of class files under
`classes/` — those use the namespace declaration instead and Moodle's
autoloader will reject files with a MOODLE_INTERNAL guard.

**Fix**
- Procedural files: `defined('MOODLE_INTERNAL') || die();` right after
  the opening PHP tag.
- Class files: no guard. Just `<?php\nnamespace local_example;\n...`.

**Prevention**
Follow the scaffold template strictly. Let PHPCS catch any drift.

---

## C. Database & upgrade

### C1. `install.xml` vs `upgrade.php` out of sync

**Symptom**
On a fresh install, the schema is X. On an upgrade from an older
version, the schema is Y. Behavior diverges depending on install path.

**Cause**
You added a column to `install.xml` but forgot to add the corresponding
`if ($oldversion < ...)` block to `db/upgrade.php` (or vice versa).
Moodle does not auto-generate either.

**Fix**
After any schema change:
1. Add to `install.xml` (regenerate via Moodle's XMLDB editor at
   `admin/tool/xmldb/`).
2. Add a matching upgrade block:
```php
if ($oldversion < 2026041100) {
    $table = new xmldb_table('local_example_items');
    $field = new xmldb_field('newcol', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'after_some_field');
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }
    upgrade_plugin_savepoint(true, 2026041100, 'local', 'example');
}
```
3. Bump `$plugin->version` to match the savepoint number.

**Prevention**
Run `moodle-plugin-ci savepoints` locally — it grep-checks the pairing.

---

### C2. Savepoint version doesn't match `$plugin->version`

**Symptom**
QA bot rejects with:
```
Savepoint mismatch: upgrade step references 2026041100 but plugin
version is 2026041200.
```

**Cause**
You bumped `$plugin->version` to a new number but the last
`upgrade_plugin_savepoint()` in `db/upgrade.php` uses an older number.
Every version increment needs a matching savepoint (even an empty
block).

**Fix**
Add a block:
```php
if ($oldversion < 2026041200) {
    // No schema changes in this release — just bump the savepoint.
    upgrade_plugin_savepoint(true, 2026041200, 'local', 'example');
}
```

**Prevention**
Every `$plugin->version` bump checklist item includes "add matching
savepoint in `db/upgrade.php`, even if empty".

---

### C3. `field->precision` must match across install.xml and upgrade.php

**Symptom**
```
Table "local_example_items" has column "foo" with different precision/type
```
from `xmldb_structure_check`.

**Cause**
You changed `XMLDB_TYPE_INTEGER` size `10` to `20` in `install.xml` but
the field was originally added with size `10` via `upgrade.php` (or vice
versa). Fresh installs get the new value, upgraded sites keep the old.

**Fix**
Add an upgrade block that calls `$dbman->change_field_precision($table,
$field)` with the new precision, then savepoint.

**Prevention**
Use the XMLDB editor (`admin/tool/xmldb/`) to generate upgrade blocks —
it diffs the on-disk `install.xml` against a canonical representation
and produces the correct PHP for you.

---

## D. Testing

### D1. PHPUnit warnings cause exit-non-zero on all-green runs

**Symptom**
```
OK, but there were issues!
Tests: 42, Assertions: 217, Warnings: 3.
```
then shell exits with status 2, making CI red even though all tests
passed.

**Cause**
PHPUnit 10 with `failOnWarning=true` (which Moodle's `phpunit.xml`
ships with) treats any emitted warning — even Moodle's own
`reset_dataroot()` teardown deprecation noise — as a CI failure.

**Fix**
In `playbooks/test.sh`, capture output and inspect the last lines:

```bash
run_phpunit() {
    local output
    output="$(exec_phpunit "$@" 2>&1)" || true
    echo "$output"
    if echo "$output" | tail -20 | grep -qE '^(OK|OK, but there were issues!)'; then
        return 0
    elif echo "$output" | tail -20 | grep -qE '^(FAILURES|ERRORS)!'; then
        return 1
    fi
    return 1
}
```

Treat `OK` and `OK, but there were issues!` as pass. Treat `FAILURES!`
and `ERRORS!` as fail. Do not remove this parser unless Moodle core's
teardown is fixed.

**Prevention**
Keep the parser in place. Document it prominently in
`playbooks/test.sh` so the next person who tries to "simplify" it gets
warned.

---

### D2. Running PHPUnit from the wrong directory (Moodle 5.x)

**Symptom**
```
PHP Fatal error:  require(): Failed opening required '/var/www/html/public/lib/phpunit/bootstrap.php'
```
or
```
PHPUnit could not find any test files.
```

**Cause**
Moodle 5.x splits the repo: `$MOODLE_REPO_ROOT` contains `composer.json`,
`vendor/`, and `phpunit.xml`; `$MOODLE_CLI_ROOT = $MOODLE_REPO_ROOT/public/`
contains the webroot. PHPUnit must run from `$MOODLE_REPO_ROOT`, not
from `public/`.

**Fix**
```bash
cd /var/www/html                                 # repo root
vendor/bin/phpunit --testsuite local_example_testsuite
```
NOT
```bash
cd /var/www/html/public                          # webroot — WRONG
../vendor/bin/phpunit ...
```

**Prevention**
Define `MOODLE_REPO_ROOT` and `MOODLE_CLI_ROOT` separately in every
test driver env file and always `cd "$MOODLE_REPO_ROOT"` before
invoking phpunit.

---

### D3. Default-filter trap: running all 29k Moodle core tests by accident

**Symptom**
`./playbooks/test.sh --target=hetzner` runs for hours, hits memory
limits, or fills the log with thousands of unrelated tests. CI queue
backs up because one job won't finish.

**Cause**
PHPUnit with no `--testsuite` argument runs every suite in
`phpunit.xml`, which in Moodle is ~29k tests across the entire codebase.
You wanted just your plugin.

**Fix**
`playbooks/test.sh` has a **default filter**: when neither `--component`
nor `--full` is given, `run_phpunit()` enumerates `plugins/*/tests/` and
passes the matching `<dirname>_testsuite` names (comma-separated) via
`--testsuite`. Same pattern for Behat via `@plugin1||@plugin2` tag
expression.

Opt-in to full runs only with `--full`:
```bash
./playbooks/test.sh --target=hetzner --full
```

**Prevention**
**This is a recorded feedback memory:** never claim a specific test
count (e.g. "53 tests, not 29k") without first verifying with
`vendor/bin/phpunit --list-suites | wc -l` or the equivalent. Claiming
a low number can prompt a full-run that crushes the CI.

---

### D4. Behat "Selenium session not found" / Firefox profile errors

**Symptom**
```
{"value":{"error":"session not created","message":"Unable to find a matching set of capabilities"}}
```
when Behat tries to launch the Selenium Firefox session.

**Cause**
Moodle Behat ships defaults for an older Firefox. Selenium on newer
Firefox (115+) requires an explicit Firefox profile config block in
`behat.yml.dist` or environment-level config.

**Fix**
In `behat.yml` (or injected via env), add:
```yaml
default:
  extensions:
    Behat\MinkExtension:
      browser_name: firefox
      selenium2:
        wd_host: http://selenium:4444/wd/hub
        capabilities:
          browserName: firefox
          marionette: true
          moz:firefoxOptions:
            args: ['-headless']
            prefs:
              browser.download.dir: /tmp
              browser.download.folderList: 2
```

**Prevention**
Run `admin/tool/behat/cli/init.php` from inside the same Moodle version
as the test runner — it regenerates `behat.yml` with matching defaults.

---

### D5. Behat `$CFG->behat_wwwroot` mismatch

**Symptom**
```
The given wwwroot is not used for behat testing.
```
or Behat steps navigate to 127.0.0.1 and hit `Connection refused`.

**Cause**
`$CFG->behat_wwwroot` in `config.php` must match the URL Selenium uses
to reach the webserver. In Docker-networked setups it's the container
name, not `localhost`.

**Fix**
```php
$CFG->behat_wwwroot = 'http://webserver';  // docker service name
$CFG->behat_prefix  = 'b_';
$CFG->behat_dataroot = '/var/www/behatdata';
```

**Prevention**
Template `config.php` per-environment. Never copy a working Behat
config from a different environment without rewriting `behat_wwwroot`.

---

### D6. Leftover `phpu_*` / `b_*` tables after crashed tests

**Symptom**
```
ERROR: relation "phpu_user_enrolments" already exists
```
or test init fails with "database not empty" when running `--reinit`.

**Cause**
A previous PHPUnit or Behat run crashed and never cleaned up its
prefix-scoped tables. Moodle's reinit won't drop them — it expects a
clean prefix.

**Fix**
For PostgreSQL:
```sql
DO $$
DECLARE r record;
BEGIN
  FOR r IN SELECT tablename FROM pg_tables
           WHERE schemaname = 'public' AND tablename LIKE 'phpu_%'
  LOOP
    EXECUTE 'DROP TABLE IF EXISTS public.' || quote_ident(r.tablename) || ' CASCADE';
  END LOOP;
END $$;
```
Same for `b_%` prefix. For MySQL, use `information_schema.tables`.

**Prevention**
Add a "leftover cleanup" step to `test.sh --reinit` that runs this DO
block before calling `admin/tool/phpunit/cli/init.php`.

---

## E. Deployment & CI

### E1. `docker cp` leaves files owned by root

**Symptom**
After deploying, Moodle page loads but warnings appear:
```
Warning: file_put_contents(/var/www/moodledata/cache/...): failed to open stream: Permission denied
```

**Cause**
`docker cp` preserves host ownership. `www-data` inside the container
can't write into dirs owned by `root`.

**Fix**
```bash
docker exec <container> chown -R www-data:www-data /var/www/html/public/local/example
```
Add this to every deploy step.

**Prevention**
Your `deploy.sh` must always chown after `docker cp`. This is
non-optional.

---

### E2. `ci/test-results` branch never updates

**Symptom**
You push a fix, wait for the CI workflow, then
```bash
git fetch origin 'refs/heads/ci/test-results:refs/remotes/origin/ci/test-results'
```
returns nothing new — the branch is stale or missing.

**Cause — diagnostic checklist**
1. **Did the workflow actually run?** Check `git log origin/main` for the
   push and compare to the workflow's `on: push: paths:` filter. If your
   change only touched a file outside the filter, the workflow didn't
   trigger.
2. **Is it queued behind an older run?** The workflow uses
   `concurrency: test-hetzner, cancel-in-progress: false`, so a stuck run
   blocks all subsequent ones. Kill the stuck run from the GH web UI if
   you have access, or wait for its timeout.
3. **Did the publish step error out?** If `publish-test-result.sh`
   crashes before pushing, you get no branch update. Since api.github.com
   is egress-blocked, the fallback is SSH to the server and read
   `journalctl` / workflow logs.

**Fix**
Once the stuck run clears, push any harmless commit (even a whitespace
change matching the paths filter) to retrigger the workflow.

**Prevention**
Keep concurrency-group runs short. If a test run ever hangs, kill it
manually instead of waiting for the default 6-hour timeout.

---

### E3. GitHub Actions rsync deploy syncs too much

**Symptom**
The Hetzner deploy workflow suddenly starts uploading `_claude-skills-update/`,
`product/`, or `.github/` to the server's Moodle plugins directory,
breaking the site.

**Cause**
The workflow's `rsync` command lacks `--exclude` entries for non-plugin
workspace directories. Only `plugins/**` should ever land on the server.

**Fix**
In `.github/workflows/deploy-hetzner.yml`:
```yaml
- name: Rsync plugins
  run: |
    rsync -avz --delete \
      --include='plugins/***' \
      --exclude='*' \
      ./ deploy@hetzner:/var/www/html/public/local/
```
Or copy only specific `plugins/*` subtrees into their destination paths
individually.

**Prevention**
Every new top-level dir in the workspace repo must be explicitly
considered for the rsync filter. Review deploy workflow any time you
add a dir.

---

## F. Submission bounces

### F1. AMD build drift

**Symptom**
QA bot posts:
```
AMD build file does not match source: amd/build/foo.min.js
```

**Cause**
You edited `amd/src/foo.js` but didn't re-run `grunt amd`, so
`amd/build/foo.min.js` is stale.

**Fix**
```bash
cd <plugin-dir>
npx grunt amd
git add amd/build/
git commit -m "Rebuild AMD"
```

**Prevention**
Either (a) commit `amd/build/` only in the same commit as `amd/src/`
changes, or (b) add a pre-commit hook that fails if `amd/src/*` is
newer than `amd/build/*.min.js`.

---

### F2. ZIP extracts to the wrong top-level directory name

**Symptom**
QA bot posts:
```
Plugin ZIP top directory is "local_example" but expected "example"
```

**Cause**
You built the ZIP with `zip -r local_example.zip local_example/` or
similar. The top-level dir inside a Moodle plugin ZIP must match the
**leaf name** (without the type prefix), because Moodle extracts it
directly into `local/` (or `mod/`, etc.).

**Fix**
```bash
git archive --format=zip --prefix=example/ v1.0.0 -o local_example_v1.0.0.zip
```
Note `--prefix=example/`, NOT `--prefix=local_example/`.

**Prevention**
Template your release script with `--prefix=<leaf>/` and make the
release tag the trigger. Never `zip -r` manually.

---

### F3. `thirdpartylibs.xml` missing for bundled vendor code

**Symptom**
QA bot posts:
```
Plugin contains third-party library but no thirdpartylibs.xml declaration.
```

**Cause**
You bundled a JS/PHP/CSS library (even a small one) without declaring
it. Moodle requires every vendored library to have a provenance entry.

**Fix**
Create `thirdpartylibs.xml` at the plugin root:
```xml
<?xml version="1.0"?>
<libraries>
  <library>
    <location>amd/src/vendor/chartjs.js</location>
    <name>Chart.js</name>
    <version>4.4.0</version>
    <license>MIT</license>
    <licenseversion></licenseversion>
    <repository>https://github.com/chartjs/Chart.js</repository>
  </library>
</libraries>
```

**Prevention**
Before bundling any third-party code, think twice: is there a core
Moodle helper? If not, create the declaration in the same commit as
the vendor drop.

---

## G. Conversational / agentic pitfalls

### G1. Claude hallucinates test counts

**Symptom**
Claude claims "the plugin has 53 tests" or "only 12 Behat features",
then someone acts on that and runs the full suite, which turns out to
be 29,000 tests, crashing CI.

**Cause**
The model asserted a specific number without verifying it. Numeric
claims about test counts, plugin counts, file counts, etc., are
tempting to emit from memory and are very easy to get wrong.

**Fix — this is a recorded feedback memory**
Before stating any specific test count, plugin count, or file count,
verify with a command:

```bash
vendor/bin/phpunit --list-suites | wc -l
find plugins -maxdepth 2 -name tests -type d | wc -l
find <path> -name '*.feature' | wc -l
```

If you cannot verify in the current environment, say "approximately"
and state the source of the estimate, or decline to name a number at
all.

**Prevention**
Treat "how many X are there" as a tool-requiring question, not a
knowledge question. Never round-trip a number through memory alone.

---

### G2. Claude rewrites `version.php` from memory and regresses

**Symptom**
After a code change, the new `version.php` has `$plugin->version =
2026041100` while `main` has `2026041200`. Moodle refuses to upgrade.

**Cause**
The model regenerated `version.php` without first reading the version
number from `origin/main` or the local file. The "plausible" version
string was lower than the actual one.

**Fix**
```bash
# Before writing a new version.php, ALWAYS:
git show origin/main:plugins/<name>/version.php | grep -E '(version|release)'
# Then increment the read value, never type from memory.
```

**Prevention**
**Recorded feedback memory.** Version bumps never regress. Always read
origin first, increment, write.

---

### G3. Claude confuses `$MOODLE_REPO_ROOT` and `$MOODLE_CLI_ROOT`

**Symptom**
Claude invokes `php admin/cli/upgrade.php` from the repo root instead
of `public/`, or invokes `vendor/bin/phpunit` from `public/` instead of
the repo root. Both fail with "file not found" or "autoload error".

**Cause**
Moodle 5.x introduces the `public/` split and older muscle memory
assumes everything lives at one root.

**Fix**
Always be explicit:
- `cd "$MOODLE_CLI_ROOT" && php admin/cli/upgrade.php --non-interactive`
- `cd "$MOODLE_REPO_ROOT" && vendor/bin/phpunit --testsuite <name>`

**Prevention**
Use the two variables in every shell invocation. Never refer to "the
Moodle root" ambiguously.

---

## H. Quick reference table: top 10 most common bounces

| # | Bounce | Preempt with |
|---|---|---|
| 1 | Missing privacy provider | Scaffold `classes/privacy/provider.php` from day 1 |
| 2 | Missing PHPDoc on public API | `moodle-plugin-ci phpcs` + phpmd locally |
| 3 | Savepoint/version mismatch | `moodle-plugin-ci savepoints` in pre-commit |
| 4 | AMD build drift | Re-run `grunt amd` before every commit that touches `amd/src/` |
| 5 | Wrong ZIP top-level dir | `git archive --prefix=<leaf>/` |
| 6 | Capability string missing | Lang string in same commit as `db/access.php` |
| 7 | Direct DB access without `$DB` | grep for `mysql_`, `pg_`, `PDO` in CI |
| 8 | Raw `<script>` in templates | Mustache lint + AMD migration |
| 9 | Stale `amd/build/` not matching `amd/src/` | Pre-commit hook on mtime |
| 10 | `require_login()` missing on view page | Every `view.php` starts with it, no exceptions |

---

## I. How to extend this file

When a new failure mode shows up:

1. Capture the exact symptom (error message, log line, reviewer quote).
2. Write down the root cause in one sentence.
3. Write down the minimum fix as a code block.
4. Add a one-line prevention rule.
5. File it under the appropriate section (A–H) and cross-reference
   from any related entry.

If the same failure mode shows up **twice** in conversations, consider
promoting the prevention rule to a **feedback memory** in
`.auto-memory/` so Claude internalizes it across sessions, not just in
this file. Feedback memories have higher recall than reference files
because they're always loaded at session start.
