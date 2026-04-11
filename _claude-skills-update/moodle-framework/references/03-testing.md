# Testing — PHPUnit, Behat, Generators, CI Feedback Loop

This file is the authoritative reference for **writing, running, and
debugging Moodle plugin tests**. Target version: Moodle 5.x with
PHPUnit 10 and Behat 3.x. Many patterns below were learned the hard way
from the LernHive test-infrastructure bootstrap — the pitfalls are
baked into `playbooks/test.sh` and into `05-errors.md`.

---

## 1. When to write which kind of test

| Test type | Use when |
|---|---|
| **PHPUnit** | Isolated unit-ish tests: data-layer logic, value objects, pure functions, service classes with `$DB` access, event payloads, privacy provider, capability callbacks. Run against a dedicated test DB, fast, reliable. |
| **Behat** | End-to-end user flows: login, forms, clicks, JavaScript-driven components, visual feedback. Slow, requires Selenium, brittle — reserve for critical happy paths. |
| **PHPUnit with generator** | When a test needs "realistic" Moodle data (a course, users, enrolments) but doesn't need a browser. Use the `$this->getDataGenerator()` pattern. |

**Default ratio for a LernHive plugin:** ~5–15 PHPUnit tests covering
the domain logic, plus 1–3 Behat features covering the core user
journeys. Don't chase 100% line coverage; chase coverage of the
contracts that matter.

---

## 2. PHPUnit basics

### File placement

```
plugins/local_lernhive_example/
└── tests/
    ├── item_test.php              # tests for \local_lernhive_example\item
    ├── card_registry_test.php
    └── behat/
        └── example.feature
```

Each test file must:
- Be named `<thing>_test.php`.
- Declare a class named `<something>_test` (any name — Moodle's test
  runner uses the filename, not the class name, for discovery).
- Extend `\advanced_testcase` (the Moodle base, NOT `\PHPUnit\Framework\TestCase`).

### Minimal test class

```php
<?php
namespace local_lernhive_example;

defined('MOODLE_INTERNAL') || die();

/**
 * @covers \local_lernhive_example\item
 */
class item_test extends \advanced_testcase {

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();     // <-- IMPORTANT: see below
    }

    public function test_item_can_be_created(): void {
        $user  = $this->getDataGenerator()->create_user();
        $item  = item::create_for($user->id, 'Hello');

        $this->assertNotEmpty($item->id);
        $this->assertSame($user->id, $item->userid);
        $this->assertSame('Hello', $item->title);
    }
}
```

### `resetAfterTest()` — always

`\advanced_testcase::resetAfterTest()` tells Moodle to roll back the DB
state (and reset `$CFG`, `$USER`, etc.) after the test finishes. If you
forget it, **any** DB write leaks into the next test. It's the single
most important habit in Moodle testing.

Call it in every test that writes to the DB, or in `setUp()` for
consistency.

### The data generators

Moodle ships a rich fixture system reachable from
`$this->getDataGenerator()`:

```php
$gen = $this->getDataGenerator();

$user = $gen->create_user(['email' => 'alice@example.com']);
$course = $gen->create_course(['fullname' => 'Test course']);
$gen->enrol_user($user->id, $course->id, 'student');

// Plugin-specific generators (opt-in)
$modgen = $gen->get_plugin_generator('mod_forum');
$forum = $modgen->create_instance(['course' => $course->id, 'type' => 'general']);
$discussion = $modgen->create_discussion([
    'forum'  => $forum->id,
    'userid' => $user->id,
    'course' => $course->id,
]);
```

Plugins define their own generator in `tests/generator/lib.php`:

```php
class local_lernhive_example_generator extends \component_generator_base {
    public function create_item(array $overrides = []): \stdClass {
        global $DB;
        $record = (object)array_merge([
            'userid'      => 0,
            'title'       => 'Generated ' . mt_rand(),
            'timecreated' => time(),
        ], $overrides);
        $record->id = $DB->insert_record('local_example_items', $record);
        return $record;
    }
}
```

### Common assertions

```php
$this->assertTrue($x);
$this->assertFalse($x);
$this->assertNull($x);
$this->assertSame(42, $x);              // strict ===
$this->assertEquals(42, $x);            // loose ==
$this->assertCount(3, $array);
$this->assertArrayHasKey('title', $a);
$this->assertStringContainsString('hello', $text);
$this->assertInstanceOf(item::class, $x);
$this->assertMatchesRegularExpression('/^abc/', $s);

// Exception testing
$this->expectException(\moodle_exception::class);
$this->expectExceptionMessage('invalid_item');
item::create_for(0, '');     // <-- this should throw
```

### `setAdminUser()` and user context

By default, tests run as the CLI user (no `$USER`). When code under
test calls `require_login()` or depends on capabilities, set a user:

```php
$this->setAdminUser();           // run as admin
$this->setUser($alice);          // run as Alice
$this->setGuestUser();
```

Always call `resetAfterTest()` before these, so the user state is
rolled back.

---

## 3. Running PHPUnit against Moodle

### The repo root vs CLI root trap

Moodle's `phpunit.xml` references paths **relative to the repo root**
(where `composer.json` and `vendor/` live). On Moodle 5.x with the
`public/` split that's **one directory above** `public/`.

Running `vendor/bin/phpunit` from the wrong cwd causes either
`Autoload not available` or cryptic "no tests discovered" failures.
Always cwd = the repo root.

### Initializing the test environment

Before the first run on a fresh install (or after fixtures / schema
changes), run:

```bash
# From the Moodle CLI root (public/ on 5.x)
php admin/tool/phpunit/cli/init.php
```

This regenerates `phpunit.xml` from the currently installed plugins,
creates the test DB (separate schema, suffixed with `_phpu`), and
writes `tests/behat/behat.yml` boilerplate.

Re-run `init.php` after:
- Installing or removing a plugin
- Editing `db/install.xml` or `db/upgrade.php`
- Editing `db/services.php`, `db/events.php`, or `db/caches.php`
- Bumping `$plugin->version`

### The right command line

```bash
# Cwd = repo root (where composer.json lives)
cd /var/www/html           # NOT /var/www/html/public

# Run tests for one plugin (preferred)
vendor/bin/phpunit -c phpunit.xml --testsuite local_lernhive_example_testsuite

# Run a single test file
vendor/bin/phpunit -c phpunit.xml plugins/local_lernhive_example/tests/item_test.php

# Run a single test method
vendor/bin/phpunit -c phpunit.xml --filter test_item_can_be_created

# Run everything (~29k tests in Moodle core — don't do this by accident)
vendor/bin/phpunit -c phpunit.xml
```

`playbooks/test.sh` wraps all of this and enforces the default filter.

### Memory limits

Moodle's test helpers can run out of the default 256M — especially with
many fixtures. Raise it:

```bash
php -d memory_limit=512M vendor/bin/phpunit ...
```

`playbooks/test.sh` exposes this via `PHPUNIT_MEM` in `test.<target>.env`.

---

## 4. The warnings-vs-failure output trap

PHPUnit 10 with `failOnWarning` treats **every** E_WARNING during the
test suite as a test failure — including harmless ones that Moodle
core itself emits during `reset_dataroot()`:

```
151 warnings about remove_dir() unlink/rmdir failing on already-removed
MUC cache files. Exit code 2. But the summary line says:

OK, but there were issues!
Tests: 10, Assertions: 51, Warnings: 151.
```

These warnings do **not** indicate a real bug in the code under test
— they're cleanup noise from Moodle's own teardown path. Treating them
as failures would block every CI run.

**The pattern in `playbooks/test.sh`:**

```bash
tmpout="$(mktemp)"
set +e
in_container_repo_root \
    env PHPUNIT_MEMORY_LIMIT="$PHPUNIT_MEM" \
    php -d memory_limit="$PHPUNIT_MEM" "$PHPUNIT_BIN" \
    -c "$MOODLE_REPO_ROOT/phpunit.xml" "${args[@]}" \
    2>&1 | tee "$tmpout"
rc="${PIPESTATUS[0]}"
set -e

if [[ "$rc" -eq 0 ]]; then
    ok "PHPUnit: passed"
    return 0
fi

if grep -qE '^OK(\s|,|$)' "$tmpout"; then
    warn "PHPUnit: passed, but phpunit reported warnings/deprecations (exit $rc — treated as pass)"
    return 0
fi

err "PHPUnit: failed (exit $rc)"
return 3
```

**Rules for this parser:**
- Only treat non-zero + `^OK` as "passed with warnings".
- `FAILURES!` / `ERRORS!` headings + non-zero = real failure.
- Never ignore exit codes without also inspecting output.

If at some point Moodle fixes its teardown noise upstream, remove the
parser and fall back to strict exit-code checking.

---

## 5. Default testsuite filter (plugin-only)

Moodle's generated `phpunit.xml` registers **every** testsuite — core
Moodle (~29,000 tests) plus all installed plugins. Running `phpunit`
without `--testsuite` runs them all, which on a small Hetzner VM takes
multiple hours and drowns plugin signal in Moodle-core noise.

`playbooks/test.sh` therefore enforces a **default filter** to only
the LernHive plugin testsuites whenever neither `--component` nor
`--full` is specified:

```bash
lernhive_plugin_components() {
    for d in "$ROOT"/plugins/*/; do
        [[ -d "$d" ]] || continue
        name="$(basename "$d")"
        [[ -d "$d/tests" ]] || continue
        echo "$name"
    done
}

lernhive_testsuite_list() {
    local names=() c
    while IFS= read -r c; do
        [[ -n "$c" ]] && names+=("${c}_testsuite")
    done < <(lernhive_plugin_components)
    local IFS=','
    echo "${names[*]}"
}
```

Then in `run_phpunit()`:

```bash
if [[ -n "$COMPONENT_FILTER" ]]; then
    args+=(--testsuite "$(component_testsuite "$COMPONENT_FILTER")")
elif [[ "$FULL" -eq 1 ]]; then
    log "PHPUnit: --full → running ALL testsuites"
else
    args+=(--testsuite "$(lernhive_testsuite_list)")
fi
```

**Rule:** the plugin dirname equals the frankenstyle component name,
and the Moodle-registered testsuite is `<component>_testsuite`. This
1:1 mapping is what makes the filter work — never break it by renaming
plugin directories.

**Numerics-check rule:** if you're about to claim a test-count
("we have N tests", "running the full suite means M"), **verify it
with `vendor/bin/phpunit --list-suites` first**. Do not guess from
memory. An earlier version of this framework shipped an off-by-three-
orders-of-magnitude claim about test count ("53 vs 29000"), which
caused a 19%-complete 29k run before anyone noticed.

---

## 6. Behat basics

### File placement

```
plugins/local_lernhive_example/tests/behat/
├── example.feature
└── behat_local_lernhive_example.php      # custom step definitions
```

### Minimal feature

```gherkin
@local_lernhive_example
Feature: Example plugin entry page
  In order to use the example plugin
  As a user
  I need to see the entry page with the right actions

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | alice    | Alice     | A        |
    And the following "courses" exist:
      | fullname   | shortname |
      | Course 1   | c1        |
    And the following "course enrolments" exist:
      | user  | course | role    |
      | alice | c1     | student |

  Scenario: Student sees the main action
    Given I log in as "alice"
    When I am on the "Course 1" course page
    And I follow "Example"
    Then I should see "Start learning"
```

**Key rules:**
- Every feature must be tagged with `@<frankenstyle>` as the first line.
  This makes the default tag filter in `playbooks/test.sh` work.
- Use Moodle's **data tables** (`Given the following "users" exist:`)
  — they map to the generators from PHPUnit and give you fast,
  realistic fixtures without custom steps.
- Prefer high-level steps ("I am on the course page") over low-level
  CSS selectors ("I click on `.mod-entry`") — high-level steps survive
  theme changes.

### Initializing Behat

```bash
# First run or after plugin changes
php admin/tool/behat/cli/init.php
```

Then run:

```bash
# Everything tagged for one plugin
vendor/bin/behat --config=<behatyaml> --tags=@local_lernhive_example

# Single feature
vendor/bin/behat --config=<behatyaml> \
    plugins/local_lernhive_example/tests/behat/example.feature

# With JavaScript (needs Selenium running)
vendor/bin/behat --tags=@javascript
```

### `behat.yml` paths and `behat_wwwroot`

Moodle generates `behat.yml` inside the repo root. Two gotchas:

1. In moodle-docker, the web container is named `webserver`, and Behat
   runs inside a separate PHP container that accesses Moodle via the
   internal Docker network. The correct `behat_wwwroot` is
   `http://webserver` (NOT `http://localhost` or the public URL).
2. The `behat_faildump_path` needs a directory that's writable from
   inside the container (typically `/tmp/behat-dumps`).

These are set in `config.php`:

```php
$CFG->behat_wwwroot = 'http://webserver';
$CFG->behat_prefix  = 'b_';
$CFG->behat_dataroot = '/var/www/data/behatdata';
$CFG->behat_faildump_path = '/tmp/behat-dumps';
```

### Selenium + Firefox profile

For `@javascript` scenarios, Behat drives a real browser. The
moodle-docker stack ships a `selenium` service with Firefox; you need
to configure a Firefox profile that disables first-run dialogs:

```yaml
# behat.yml excerpt (Moodle's init.php generates this, but tweak here)
default:
  extensions:
    Behat\MinkExtension:
      browser_name: firefox
      selenium2:
        wd_host: http://selenium:4444/wd/hub
        capabilities:
          browserName: firefox
          moz:firefoxOptions:
            prefs:
              browser.startup.homepage_override.mstone: "ignore"
              browser.tabs.warnOnClose: false
```

If `@javascript` scenarios hang on "waiting for Firefox", 80% of the
time it's either the selenium container not running or the profile
not disabling the first-run dialog.

---

## 7. Common Behat steps

| Intent | Step |
|---|---|
| Login | `Given I log in as "alice"` |
| Navigate | `When I am on the "Course 1" course page`<br>`When I am on the "Alice" "user profile" page` |
| Click | `When I click on "Save" "button"`<br>`When I follow "Example"` |
| Form input | `And I set the field "Name" to "Hello"`<br>`And I set the field "Category" to "General"` |
| Assertions | `Then I should see "Success"`<br>`Then I should not see "Error"`<br>`Then "Save" "button" should exist` |
| Wait | `And I wait "2" seconds`<br>`And I wait until "Save" "button" is visible` |

**Anti-pattern:** `I wait "N" seconds`. Use
`I wait until "Save" "button" is visible` (polled, bounded) instead of
arbitrary sleeps. Polled waits are less flaky and faster when the UI
is fast.

---

## 8. Custom Behat steps

When a feature needs vocabulary that doesn't exist in core, add a
custom context:

```php
// tests/behat/behat_local_lernhive_example.php
require_once(__DIR__ . '/../../../lib/behat/behat_base.php');

class behat_local_lernhive_example extends behat_base {
    /**
     * @Given /^the example plugin has (\d+) items? for "(?P<username>[^"]+)"$/
     */
    public function the_plugin_has_n_items_for(int $count, string $username): void {
        global $DB;
        $user = $DB->get_record('user', ['username' => $username], '*', MUST_EXIST);
        for ($i = 0; $i < $count; $i++) {
            $DB->insert_record('local_example_items', (object)[
                'userid'      => $user->id,
                'title'       => "Item $i",
                'timecreated' => time(),
            ]);
        }
    }
}
```

Class name **must** match filename and must start with `behat_`.
Methods are routed by PHPDoc annotations (`@Given`, `@When`, `@Then`).

---

## 9. Leftover data cleanup

If Behat or PHPUnit crashes mid-run, stray DB tables or dataroot
directories may remain and prevent the next run from starting.

For Moodle's test DB (Postgres) on the LernHive stack:

```sql
DO $$
DECLARE r record;
BEGIN
  FOR r IN SELECT tablename FROM pg_tables
           WHERE schemaname = 'public' AND tablename LIKE 'phpu\_%' LOOP
    EXECUTE 'DROP TABLE IF EXISTS public.' || quote_ident(r.tablename) || ' CASCADE';
  END LOOP;
END $$;
```

And delete the dataroot:

```bash
docker exec lernhive-webserver-1 rm -rf /var/www/data/phpudata /var/www/data/behatdata
```

Then re-run `admin/tool/phpunit/cli/init.php` and
`admin/tool/behat/cli/init.php`.

---

## 10. The `ci/test-results` feedback loop

Cowork's sandbox egress allowlist blocks `api.github.com`, so Claude
can't read GitHub Actions logs via the API. Workaround: the workflow
writes results to a dedicated orphan branch Claude can reach via the
plain `github.com` git endpoint.

### Publishing (from workflow)

```yaml
permissions:
  contents: write

jobs:
  phpunit:
    steps:
      - uses: actions/checkout@v4    # for access to scripts/ directory
      - name: Run PHPUnit over SSH
        run: |
          set +e
          ssh "$HETZNER_USER@$HETZNER_HOST" "$CMD" 2>&1 | tee /tmp/test-output-phpunit.log
          SSH_RC=${PIPESTATUS[0]}
          set -e
          echo "SSH_RC=$SSH_RC" >> "$GITHUB_ENV"
          exit "$SSH_RC"
      - name: Publish PHPUnit result
        if: always()
        env:
          GH_TOKEN: ${{ secrets.GITHUB_TOKEN }}
          JOB: phpunit
          SSH_RC: ${{ env.SSH_RC || '255' }}
        run: bash .github/scripts/publish-test-result.sh
```

### The publish script writes:

```
runs/
├── latest-phpunit.log           # full captured output
├── latest-phpunit.json          # structured status
├── latest-behat.log
├── latest-behat.json
└── archive/
    ├── <ts>-<sha>-phpunit.log
    └── <ts>-<sha>-behat.log
```

JSON structure:

```json
{
  "job": "phpunit",
  "status": "passed",
  "exit_code": 0,
  "has_warnings": true,
  "run_id": 123456789,
  "run_number": 42,
  "sha": "9857389...",
  "short_sha": "9857389",
  "ref": "refs/heads/main",
  "actor": "jmoskaliuk",
  "event": "push",
  "timestamp": "2026-04-11T15:30:00Z",
  "log_path": "runs/latest-phpunit.log",
  "archive_path": "runs/archive/20260411T153000Z-9857389-phpunit.log",
  "tests": 53,
  "assertions": 284
}
```

### Reading from Claude

```bash
SHADOW=/sessions/<session>/shadow/lernhive
git -C "$SHADOW" fetch origin 'refs/heads/ci/test-results:refs/remotes/origin/ci/test-results'

# Read the status
git -C "$SHADOW" show origin/ci/test-results:runs/latest-phpunit.json | jq .

# Read the full log
git -C "$SHADOW" show origin/ci/test-results:runs/latest-phpunit.log | tail -80

# List archived runs for history
git -C "$SHADOW" ls-tree origin/ci/test-results:runs/archive/
```

### Reasoning about "nothing there yet"

If the branch doesn't appear after a push:
1. Did the push match the workflow's `paths:` filter? Check with
   `git log --name-only <commit>`.
2. Is an older run still occupying the `concurrency: test-hetzner` slot?
   Runs queue (not cancel), so one stuck predecessor blocks the new one.
3. Did `publish-test-result.sh` error out? The workflow summary would
   show it, but the API gate means Claude can't see it. Fall back to
   the user checking the Actions tab manually.
4. Is there a stale server-side `lernhive-test` process still running
   the aborted 29k test suite from before? `ssh` in and `pkill phpunit`.

---

## 11. Testing checklist before opening a PR

- [ ] New code path has at least one PHPUnit test (covers the happy
      case + one error case).
- [ ] Test calls `$this->resetAfterTest()` if it writes to DB.
- [ ] Test file is named `<thing>_test.php`.
- [ ] Test class has a `@covers \namespace\class` PHPDoc annotation.
- [ ] Feature file (if any) is tagged with `@<frankenstyle>` on line 1.
- [ ] `playbooks/test.sh --target=local --component=<frankenstyle>`
      passes on the dev machine.
- [ ] If the test hit a core teardown warning, the warning is harmless
      (it reads like "remove_dir failed on <MUC cache file>") and the
      parser-pattern in `test.sh` still maps to pass.
- [ ] No leftover dataroot or `phpu_*` DB tables after the run.
- [ ] `origin/main`'s next push will trigger `test-hetzner.yml` (the
      file is inside the workflow's `paths:` filter).

If all green locally, push to main, wait ~3–5 min, fetch
`ci/test-results`, close the feedback loop.
