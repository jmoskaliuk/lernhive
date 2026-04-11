# Architecture — Plugin Types, APIs, Hooks, XMLDB, Files

This file is the "what kind of thing am I building, and which Moodle
extension point fits?" reference. Target version: **Moodle 5.x** with
patterns that remain valid from 4.3+.

---

## 1. Plugin types cheat sheet

| Type | Dir prefix | Frankenstyle | When to use |
|---|---|---|---|
| **Activity module** | `mod/<name>` | `mod_<name>` | A new kind of graded activity or interaction inside a course (like quiz, assign). Heaviest machinery: courseware lifecycle, gradebook, backup, completion. |
| **Block** | `blocks/<name>` | `block_<name>` | A sidebar/dashboard widget. Lightweight, displays data drawn from other plugins. |
| **Local plugin** | `local/<name>` | `local_<name>` | General-purpose extension — custom pages, services, libraries, cross-cutting features that don't fit any other type. The LernHive plugins are almost all `local_*`. |
| **Theme** | `theme/<name>` | `theme_<name>` | Visual chrome: navigation frame, layouts, SCSS, block regions. Never business logic. |
| **Course format** | `course/format/<name>` | `format_<name>` | The look & feel of a course *from inside* (sections, nav, course-internal layout). Swappable per course. |
| **Auth** | `auth/<name>` | `auth_<name>` | Authentication provider (SSO, LDAP, custom). |
| **Enrolment** | `enrol/<name>` | `enrol_<name>` | How users get into a course. |
| **Report** | `report/<name>` or `course/report/<name>` | `report_<name>` | Admin- or course-level reporting pages. |
| **Question type** | `question/type/<name>` | `qtype_<name>` | A new question behavior for the question bank / quizzes. |
| **Filter** | `filter/<name>` | `filter_<name>` | Transforms displayed text (e.g., multilang, syntax highlighting). |
| **Admin tool** | `admin/tool/<name>` | `tool_<name>` | Admin-facing utility (e.g., bulk operations, diagnostics). |
| **Repository** | `repository/<name>` | `repository_<name>` | External file source for the file picker. |

**Decision rule:** if the question is "*where* does the logic live?", the
answer is almost always a local plugin — unless you're specifically
replacing a built-in Moodle responsibility (auth, enrolment, course
format, activity type).

**Anti-pattern:** cramming course-content logic into a theme. Themes
don't render course content in Moodle 5.x after the layout cleanup;
that's course format territory. See `eledia-moodle-ux` → Theme vs Course
Format vs Plugin decision frame.

---

## 2. Required files per plugin

Every plugin MUST contain:

- `version.php` — component name, version, requires, maturity
- `lang/en/<frankenstyle>.php` — English language strings (canonical)
- `db/access.php` — if it defines capabilities
- `db/install.xml` + `db/upgrade.php` — if it has DB tables
- `classes/privacy/provider.php` — privacy API implementation (required
  for plugin approval in the Directory)

Plugin-type-specific essentials:

| Type | Extra must-haves |
|---|---|
| `mod_*` | `lib.php` (module callbacks), `mod_form.php`, `view.php`, `backup/` directory |
| `block_*` | `block_<name>.php`, `edit_form.php` (if configurable) |
| `local_*` | `lib.php` for hooks/callbacks, `settings.php` (if admin-configurable) |
| `theme_*` | `config.php`, `lib.php`, `layout/` dir, `scss/` or `style/` dir, `templates/` |
| `format_*` | `format.php`, `lib.php`, `renderer.php` |
| `qtype_*` | `question.php`, `renderer.php`, `edit_<name>_form.php` |

### `version.php` template (Moodle 5.x)

```php
<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_example';
$plugin->version   = 2026040800;   // YYYYMMDDXX
$plugin->requires  = 2024100700;   // Moodle 4.5 (minimum for submission)
$plugin->release   = '1.0.0';
$plugin->maturity  = MATURITY_STABLE;
// Optional: dependencies on other plugins
// $plugin->dependencies = ['local_other' => 2024010100];
```

**Versioning rule:** `$plugin->version` must strictly monotonically
increase. Never rewrite it to something smaller — Moodle will refuse to
upgrade. When bumping, **read the current value from `origin/main`
first**, never guess from memory.

---

## 3. Capabilities & access control

Define them in `db/access.php`:

```php
$capabilities = [
    'local/example:view' => [
        'captype'      => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [
            'user'           => CAP_ALLOW,
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW,
        ],
    ],
    'local/example:configure' => [
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'riskbitmask'  => RISK_CONFIG,
        'archetypes'   => [
            'manager' => CAP_ALLOW,
        ],
    ],
];
```

### Checking capabilities in Moodle 5.x

Use the modern helpers — the old `has_capability()` is still valid but
context construction changed:

```php
// MODERN (5.x)
$context = \core\context\system::instance();
$context = \core\context\course::instance($courseid);
$context = \core\context\module::instance($cmid);
require_capability('local/example:view', $context);

// ANTI-PATTERN: do not use context_system::instance() — use the new
// \core\context\system::instance() namespace.
```

**Important:** don't try to override `has_capability` via plugin callbacks.
Moodle removed the `get_extra_capabilities` plugin-level override path.
Use roles + capabilities as Moodle intends, and provide a sensible
`archetypes` default.

### When to reuse core capabilities

Before inventing a new capability, check whether Moodle core already has
one that means the same thing:

- `moodle/site:config` — admin-only config
- `moodle/course:manageactivities` — course editing
- `moodle/course:view` — enrolled in course
- `moodle/course:viewhiddencourses` — see hidden content
- `moodle/user:viewdetails` — read user profile

Reusing core caps keeps the capability UI manageable.

---

## 4. Output API — rendering pages

Every Moodle page follows the same skeleton:

```php
require(__DIR__ . '/../../config.php');
require_login();

$context = \core\context\system::instance();
require_capability('local/example:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/example/view.php'));
$PAGE->set_title(get_string('pluginname', 'local_example'));
$PAGE->set_heading(get_string('pluginname', 'local_example'));

echo $OUTPUT->header();

// ... content ...

echo $OUTPUT->footer();
```

**Renderer pattern (preferred for anything non-trivial):**

```php
// classes/output/main_renderer.php
namespace local_example\output;

class main_renderer extends \plugin_renderer_base {
    public function dashboard(dashboard_data $data): string {
        return $this->render_from_template(
            'local_example/dashboard',
            $data->export_for_template($this)
        );
    }
}
```

Templates live in `templates/*.mustache` and use the **Moodle Component
Library** naming (card, alert, badge, etc.) — see the `eledia-moodle-ux`
skill for the full catalog.

**Anti-pattern:** writing raw HTML with inline styles in `view.php`.
Always go through templates; always use Bootstrap utility classes
instead of `style="…"`.

---

## 5. Forms API (`moodleform`)

```php
// classes/form/settings_form.php
namespace local_example\form;

class settings_form extends \moodleform {
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'name', get_string('name'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required');

        $mform->addElement('selectyesno', 'enabled', get_string('enabled'));
        $mform->setDefault('enabled', 1);

        $mform->addElement('editor', 'description_editor',
            get_string('description'), null,
            ['maxfiles' => 0, 'noclean' => true]);
        $mform->setType('description_editor', PARAM_RAW);

        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (strlen($data['name']) < 3) {
            $errors['name'] = get_string('nametooshort', 'local_example');
        }
        return $errors;
    }
}
```

Use `$form->get_data()` — not `optional_param` — to read submitted
values. Moodle does the sesskey + nosubmit dance for you.

---

## 6. Database API (`$DB`)

Always use `$DB`, never raw PDO/mysqli:

```php
global $DB;

// Single record
$record = $DB->get_record('local_example_items', ['id' => $id], '*', MUST_EXIST);

// Multiple records
$items = $DB->get_records('local_example_items',
    ['userid' => $USER->id],
    'timecreated DESC',
    'id, title, timecreated',
    0, 50);

// Typed placeholders — use them for LIKE queries
$sql = "SELECT * FROM {local_example_items}
         WHERE " . $DB->sql_like('title', ':term', false) . "
           AND userid = :userid";
$items = $DB->get_records_sql($sql, [
    'term'   => '%' . $DB->sql_like_escape($search) . '%',
    'userid' => $USER->id,
]);

// Insert
$id = $DB->insert_record('local_example_items', (object)[
    'userid'      => $USER->id,
    'title'       => $title,
    'timecreated' => time(),
]);

// Update
$DB->update_record('local_example_items', (object)[
    'id'    => $id,
    'title' => $newtitle,
]);

// Delete
$DB->delete_records('local_example_items', ['userid' => $USER->id]);

// Transactions
$tx = $DB->start_delegated_transaction();
try {
    // ... multiple writes ...
    $tx->allow_commit();
} catch (\Throwable $e) {
    $tx->rollback($e);
}
```

**Rules:**
- **Never** concatenate user input into SQL. Always use named placeholders.
- **Always** brace table names with `{}` — Moodle expands them to the
  prefixed real name.
- For `IN` clauses, use `$DB->get_in_or_equal($array)`.
- For cross-DB LIKE, use `$DB->sql_like()` + `$DB->sql_like_escape()`.
- `MUST_EXIST` on reads throws if missing — use it when you need the row.

---

## 7. XMLDB — database schema

### `db/install.xml`

Edit ONLY via the XMLDB editor (Site admin → Development → XMLDB editor)
or by hand if you're very careful about schema versioning. Never edit
XMLDB for an existing plugin without also bumping `$plugin->version` and
adding a matching `upgrade.php` step.

Minimal table:

```xml
<?xml version="1.0" encoding="UTF-8" ?>
<XMLDB PATH="local/example/db" VERSION="2026040800">
  <TABLES>
    <TABLE NAME="local_example_items">
      <FIELDS>
        <FIELD NAME="id" TYPE="int" LENGTH="10" NOTNULL="true" SEQUENCE="true"/>
        <FIELD NAME="userid" TYPE="int" LENGTH="10" NOTNULL="true" SEQUENCE="false"/>
        <FIELD NAME="title" TYPE="char" LENGTH="255" NOTNULL="true" SEQUENCE="false"/>
        <FIELD NAME="timecreated" TYPE="int" LENGTH="10" NOTNULL="true" SEQUENCE="false"/>
      </FIELDS>
      <KEYS>
        <KEY NAME="primary" TYPE="primary" FIELDS="id"/>
        <KEY NAME="userid-fk" TYPE="foreign" FIELDS="userid" REFTABLE="user" REFFIELDS="id"/>
      </KEYS>
      <INDEXES>
        <INDEX NAME="userid-time" UNIQUE="false" FIELDS="userid, timecreated"/>
      </INDEXES>
    </TABLE>
  </TABLES>
</XMLDB>
```

### `db/upgrade.php` — savepoints

Every schema change needs a numbered savepoint matching the next version:

```php
function xmldb_local_example_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026040800) {
        $table = new xmldb_table('local_example_items');
        $field = new xmldb_field('priority', XMLDB_TYPE_INTEGER, '10',
            null, XMLDB_NOTNULL, null, '0', 'title');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2026040800, 'local', 'example');
    }

    return true;
}
```

**Rules:**
- Each upgrade block must be guarded by `if ($oldversion < ...)` AND
  ended with `upgrade_plugin_savepoint(...)` with the exact same version.
- Check `field_exists` / `index_exists` / `table_exists` before
  destructive operations — upgrades must be idempotent across reruns.
- Never delete or rename a field without a 2-step deprecation: add the
  new, copy data, keep the old nullable for one release, then remove.

---

## 8. Events API

```php
// Triggering
\local_example\event\item_created::create([
    'context'  => \core\context\system::instance(),
    'objectid' => $itemid,
    'other'    => ['title' => $title],
])->trigger();
```

Event class:

```php
namespace local_example\event;

class item_created extends \core\event\base {
    protected function init() {
        $this->data['crud']        = 'c';
        $this->data['edulevel']    = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'local_example_items';
    }

    public static function get_name(): string {
        return get_string('event_item_created', 'local_example');
    }

    public function get_description(): string {
        return "User {$this->userid} created item {$this->objectid}.";
    }
}
```

Events feed the Moodle logs automatically. They also drive the
observers system in `db/events.php` (Moodle 3.x legacy path) or the
Hooks API (modern path — see below).

---

## 9. Hooks API (Moodle 4.3+)

The Hooks API is the modern replacement for event observers and for
many lib.php callback patterns.

```php
// db/hooks.php
$callbacks = [
    [
        'hook'     => \core\hook\output\before_http_headers::class,
        'callback' => \local_example\hook_listeners\inject_meta::class . '::on',
    ],
];
```

```php
// classes/hook_listeners/inject_meta.php
namespace local_example\hook_listeners;

class inject_meta {
    public static function on(\core\hook\output\before_http_headers $hook): void {
        global $PAGE;
        $PAGE->requires->css_theme(new \moodle_url('/local/example/styles.css'));
    }
}
```

**When to prefer Hooks over legacy lib.php callbacks:**
- New code: always prefer Hooks when a hook exists for the thing you
  need to intercept.
- Legacy: `*_before_http_headers`, `*_extend_navigation`, etc. in lib.php
  still work but are being slowly deprecated. When you touch code that
  uses one, check if a Hook equivalent exists and migrate.

**Available core hooks (partial list):** `output\before_http_headers`,
`output\before_standard_head_html_generation`, `navigation\primary_nav`,
`user\*`, `mod\*`, `message\*`. Full list in Moodle core under
`lib/classes/hook/`.

---

## 10. Tasks API — cron and adhoc jobs

Scheduled task (runs on Moodle cron):

```php
// db/tasks.php
$tasks = [
    [
        'classname' => 'local_example\\task\\cleanup_old_items',
        'blocking'  => 0,
        'minute'    => '*/15',
        'hour'      => '*',
        'day'       => '*',
        'month'     => '*',
        'dayofweek' => '*',
    ],
];
```

```php
// classes/task/cleanup_old_items.php
namespace local_example\task;

class cleanup_old_items extends \core\task\scheduled_task {
    public function get_name(): string {
        return get_string('task_cleanup_old_items', 'local_example');
    }
    public function execute() {
        global $DB;
        $cutoff = time() - (30 * 86400);
        $DB->delete_records_select('local_example_items', 'timecreated < ?', [$cutoff]);
    }
}
```

Ad-hoc task (queued programmatically):

```php
$task = new \local_example\task\process_item();
$task->set_custom_data(['itemid' => $itemid]);
\core\task\manager::queue_adhoc_task($task);
```

Ad-hoc tasks run on the next cron. They're the right pattern for
"deferred side-effects" like sending mail, rebuilding indexes, or
calling external APIs.

---

## 11. File API (`mod_resource` pattern)

```php
// Storing
$fs = get_file_storage();
$record = (object)[
    'contextid' => $context->id,
    'component' => 'local_example',
    'filearea'  => 'attachment',
    'itemid'    => $itemid,
    'filepath'  => '/',
    'filename'  => $filename,
];
$fs->create_file_from_pathname($record, $tmppath);

// Retrieving
$files = $fs->get_area_files(
    $context->id,
    'local_example',
    'attachment',
    $itemid,
    'filename',
    false              // include dirs
);

// Serving — pluginfile.php callback
function local_example_pluginfile($course, $cm, $context, $filearea,
        $args, $forcedownload, array $options = []) {
    if ($filearea !== 'attachment') {
        return false;
    }
    require_login();
    $itemid    = array_shift($args);
    $filename  = array_pop($args);
    $filepath  = $args ? '/' . implode('/', $args) . '/' : '/';
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_example', 'attachment',
        $itemid, $filepath, $filename);
    if (!$file) {
        send_file_not_found();
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}
```

The `pluginfile.php` callback must live in the plugin's `lib.php` with
that exact name pattern: `<frankenstyle>_pluginfile`.

---

## 12. Privacy API

**Mandatory for plugin submission.** Every plugin that stores any
user-identifiable data must implement `\core_privacy\local\metadata\provider`
and one of the subject/data providers.

Minimal template for a plugin that stores user data:

```php
namespace local_example\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;

class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {

    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_example_items', [
            'userid'      => 'privacy:metadata:local_example_items:userid',
            'title'       => 'privacy:metadata:local_example_items:title',
            'timecreated' => 'privacy:metadata:local_example_items:timecreated',
        ], 'privacy:metadata:local_example_items');
        return $collection;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        $list = new contextlist();
        $list->add_from_sql(
            "SELECT id FROM {context} WHERE contextlevel = :level",
            ['level' => CONTEXT_SYSTEM]
        );
        return $list;
    }

    public static function export_user_data(approved_contextlist $contextlist) {
        // ... export logic ...
    }

    public static function delete_data_for_all_users_in_context(\context $context) {
        // ...
    }

    public static function delete_data_for_user(approved_contextlist $contextlist) {
        // ...
    }

    public static function get_users_in_context(\core_privacy\local\request\userlist $userlist): void {
        // ...
    }

    public static function delete_data_for_users(\core_privacy\local\request\approved_userlist $userlist): void {
        // ...
    }
}
```

Don't skip this. The Moodle Plugin Directory reviewers reject
submissions with a null privacy provider. If your plugin truly stores no
user-identifiable data, declare that explicitly:

```php
class provider implements \core_privacy\local\metadata\null_provider {
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
```

---

## 13. Backup / Restore API

Required for `mod_*` plugins and for any plugin whose data should survive
a course backup/restore cycle.

Files:
- `backup/moodle2/backup_<frankenstyle>_activity_task.class.php`
- `backup/moodle2/backup_<frankenstyle>_stepslib.php`
- `backup/moodle2/restore_<frankenstyle>_activity_task.class.php`
- `backup/moodle2/restore_<frankenstyle>_stepslib.php`

For local plugins with cross-course data, implement the
`core\backup\*` hooks instead — there's no per-activity backup task.

---

## 14. Web services

Define functions in `db/services.php`:

```php
$functions = [
    'local_example_get_items' => [
        'classname'   => 'local_example\\external\\get_items',
        'methodname'  => 'execute',
        'description' => 'Return items for the current user',
        'type'        => 'read',
        'capabilities'=> 'local/example:view',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
];
```

External class:

```php
namespace local_example\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_multiple_structure;
use core_external\external_single_structure;

class get_items extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'limit' => new external_value(PARAM_INT, 'Max items', VALUE_DEFAULT, 20),
        ]);
    }
    public static function execute(int $limit = 20): array {
        global $DB, $USER;
        $params = self::validate_parameters(self::execute_parameters(), ['limit' => $limit]);
        self::validate_context(\core\context\system::instance());
        require_capability('local/example:view', \core\context\system::instance());
        return $DB->get_records('local_example_items',
            ['userid' => $USER->id], 'timecreated DESC', 'id, title', 0, $params['limit']);
    }
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'id'    => new external_value(PARAM_INT, 'Item ID'),
                'title' => new external_value(PARAM_TEXT, 'Title'),
            ])
        );
    }
}
```

**Use the new `core_external\*` namespaces (5.x).** The old
`external_api` in the global namespace still works but is deprecated.

---

## 15. AMD / ES6 JavaScript

Source files go in `amd/src/*.js` and are compiled to `amd/build/*.min.js`
via `grunt amd`:

```js
// amd/src/dashboard.js
import {get_string} from 'core/str';
import Notification from 'core/notification';
import Ajax from 'core/ajax';

export const init = async () => {
    const label = await get_string('refresh', 'local_example');
    document.querySelector('[data-action="refresh"]').textContent = label;

    document.addEventListener('click', async (e) => {
        if (!e.target.matches('[data-action="refresh"]')) return;
        try {
            const items = await Ajax.call([{
                methodname: 'local_example_get_items',
                args: {limit: 20},
            }])[0];
            renderItems(items);
        } catch (err) {
            Notification.exception(err);
        }
    });
};
```

Call from PHP:

```php
$PAGE->requires->js_call_amd('local_example/dashboard', 'init');
```

**Rules:**
- ES6 module syntax (`import` / `export`).
- Never write raw XHR — always `core/ajax`.
- Never write raw `alert()` / `confirm()` — always `core/notification` or
  `core/modal`.
- Run `grunt amd` before committing; never commit only `src/` without
  `build/`.

---

## 16. Language strings

One file per language, named after the frankenstyle:

```php
// lang/en/local_example.php
$string['pluginname'] = 'Example';
$string['example:view'] = 'View example items';
$string['example:configure'] = 'Configure example';
$string['privacy:metadata'] = 'The Example plugin does not store personal data.';
```

**Rules:**
- **English is canonical.** Every string must exist in `lang/en/`.
- **Reuse core strings where semantically correct.** Use
  `get_string('name')` (no component arg) instead of defining your own
  `name` string.
- Use Moodle's multilang filter for content strings that need inline
  translations: `<span class="multilang" lang="en">…</span>`.
- Never concatenate translated fragments — always use
  `{$a->field}` placeholders so translators can reorder.

---

## 17. Caching (MUC)

Define definitions in `db/caches.php`:

```php
$definitions = [
    'item_metadata' => [
        'mode'      => cache_store::MODE_APPLICATION,
        'simplekeys'=> true,
        'simpledata'=> false,
        'staticacceleration' => true,
        'staticaccelerationsize' => 100,
    ],
];
```

Use:

```php
$cache = \cache::make('local_example', 'item_metadata');
if (($meta = $cache->get($itemid)) === false) {
    $meta = compute_metadata($itemid);
    $cache->set($itemid, $meta);
}
```

**Modes:**
- `MODE_APPLICATION` — shared across requests (use for expensive reads)
- `MODE_SESSION` — per-user session
- `MODE_REQUEST` — in-memory, per-request only (cheapest)

After deploy, always run `purge_caches.php` — stale MUC entries are
the #1 cause of "my code change didn't take effect" confusion.

---

## 18. Settings pages

```php
// settings.php (for admin/cli/upgrade.php to register)
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_example',
        get_string('pluginname', 'local_example'));

    $settings->add(new admin_setting_configtext(
        'local_example/apiurl',
        get_string('apiurl', 'local_example'),
        get_string('apiurl_desc', 'local_example'),
        'https://api.example.com',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_example/apitoken',
        get_string('apitoken', 'local_example'),
        get_string('apitoken_desc', 'local_example'),
        ''
    ));

    $ADMIN->add('localplugins', $settings);
}
```

Read values with `get_config('local_example', 'apiurl')`. Write with
`set_config('apiurl', $value, 'local_example')`.

---

## 19. Plugin anatomy at a glance

```
local/example/
├── version.php               # REQUIRED
├── lib.php                   # callbacks, hooks (legacy), pluginfile
├── settings.php              # admin settings page
├── lang/en/local_example.php # REQUIRED: English strings
├── pix/icon.svg              # plugin icon for navigation
├── db/
│   ├── access.php            # capabilities
│   ├── install.xml           # schema
│   ├── upgrade.php           # migrations
│   ├── tasks.php             # cron
│   ├── hooks.php             # hook listeners (4.3+)
│   ├── caches.php            # MUC definitions
│   └── services.php          # web service definitions
├── classes/
│   ├── privacy/provider.php  # REQUIRED for submission
│   ├── external/             # web service implementations
│   ├── output/               # renderers + export_for_template()
│   ├── form/                 # moodleform subclasses
│   ├── task/                 # scheduled / adhoc tasks
│   ├── event/                # event classes
│   ├── hook_listeners/       # hook callbacks
│   └── ... (your domain classes)
├── templates/                # Mustache templates
├── amd/
│   ├── src/                  # ES6 sources
│   └── build/                # grunt output (COMMIT THIS)
├── tests/
│   ├── <component>_test.php  # PHPUnit
│   └── behat/                # Behat features
├── backup/moodle2/           # backup/restore (if applicable)
├── README.md
└── docs/                     # DevFlow docs (LernHive convention)
    ├── 00-master.md
    ├── 01-features.md
    ├── 02-user-doc.md
    ├── 03-dev-doc.md
    ├── 04-tasks.md
    └── 05-quality.md
```

This is the skeleton to check against when scaffolding or reviewing a
plugin. Missing files are fine **only if** the functionality genuinely
doesn't apply — but privacy/provider is never optional.
