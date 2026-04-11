<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests for the sandbox_course provisioning helper.
 *
 * @package    local_lernhive_onboarding
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_onboarding;

use advanced_testcase;
use PHPUnit\Framework\Attributes\CoversClass;

defined('MOODLE_INTERNAL') || die();

#[CoversClass(sandbox_course::class)]
final class sandbox_course_test extends advanced_testcase {

    /**
     * A fresh `ensure()` call must create the course, persist the
     * config key, and hand back a positive ID. The course must be
     * hidden and carry the canonical shortname — tours depend on both.
     */
    public function test_ensure_creates_course_on_fresh_install(): void {
        $this->resetAfterTest();
        global $DB;

        // Clean slate — install.php has already run during bootstrap,
        // so we reverse that before exercising the fresh-creation path.
        unset_config(sandbox_course::CONFIG_KEY, 'local_lernhive_onboarding');
        $DB->delete_records('course', ['shortname' => sandbox_course::SHORTNAME]);

        $id = sandbox_course::ensure();

        $this->assertGreaterThan(0, $id);

        $course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
        $this->assertSame(sandbox_course::SHORTNAME, $course->shortname);
        $this->assertSame(0, (int) $course->visible,
            'Sandbox course must be hidden so novice trainers do not mistake it for a live course.');

        $stored = (int) get_config('local_lernhive_onboarding', sandbox_course::CONFIG_KEY);
        $this->assertSame($id, $stored, 'Config key must track the created course ID.');
    }

    /**
     * Second call to `ensure()` must short-circuit on the stored ID
     * and return the same course — no duplicate course, no churn.
     * The fast path is the hot path, so this is worth pinning.
     */
    public function test_ensure_is_idempotent(): void {
        $this->resetAfterTest();
        global $DB;

        $first = sandbox_course::ensure();
        $second = sandbox_course::ensure();

        $this->assertSame($first, $second);

        $count = $DB->count_records('course', ['shortname' => sandbox_course::SHORTNAME]);
        $this->assertSame(1, $count, 'ensure() must never duplicate the sandbox course.');
    }

    /**
     * If the config key was wiped (e.g. uninstall → reinstall cycle)
     * but the course survived, `ensure()` must rewire the config to
     * the existing row rather than creating a duplicate.
     */
    public function test_ensure_recovers_from_missing_config_via_shortname(): void {
        $this->resetAfterTest();
        global $DB;

        $original = sandbox_course::ensure();

        // Simulate a config-only wipe — the course row stays.
        unset_config(sandbox_course::CONFIG_KEY, 'local_lernhive_onboarding');

        $recovered = sandbox_course::ensure();

        $this->assertSame($original, $recovered, 'Shortname lookup must recover the same course.');

        $count = $DB->count_records('course', ['shortname' => sandbox_course::SHORTNAME]);
        $this->assertSame(1, $count, 'Shortname recovery must not duplicate the sandbox course.');

        $stored = (int) get_config('local_lernhive_onboarding', sandbox_course::CONFIG_KEY);
        $this->assertSame($original, $stored, 'Config key must be rewired after recovery.');
    }

    /**
     * If both the config key and the course are gone (admin deleted
     * the course manually, triggered a reinstall, …) `ensure()` must
     * create a fresh course and persist a new ID.
     */
    public function test_ensure_rebuilds_when_stored_id_points_at_nothing(): void {
        $this->resetAfterTest();
        global $DB;

        $original = sandbox_course::ensure();

        // Blow away the course. Use delete_course() so related bits
        // (context, sections, …) disappear cleanly — the config key
        // still points at the now-deleted row.
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');
        delete_course($original, false);

        $this->assertFalse($DB->record_exists('course', ['id' => $original]),
            'Precondition: course should be gone.');

        $rebuilt = sandbox_course::ensure();

        $this->assertGreaterThan(0, $rebuilt);
        $this->assertNotSame($original, $rebuilt, 'A new course must be provisioned.');

        $stored = (int) get_config('local_lernhive_onboarding', sandbox_course::CONFIG_KEY);
        $this->assertSame($rebuilt, $stored, 'Config key must point at the rebuilt course.');
    }

    /**
     * `forget()` drops only the plugin config key. The course itself
     * must survive — destroying potentially populated courses on
     * uninstall would be a data-loss bug.
     */
    public function test_forget_drops_config_but_preserves_course(): void {
        $this->resetAfterTest();
        global $DB;

        $id = sandbox_course::ensure();

        sandbox_course::forget();

        $this->assertFalse(
            (bool) get_config('local_lernhive_onboarding', sandbox_course::CONFIG_KEY),
            'Config key must be gone after forget().'
        );
        $this->assertTrue(
            $DB->record_exists('course', ['id' => $id]),
            'Course row must survive uninstall — admins may have added real content.'
        );
    }

    /**
     * End-to-end wiring: once the sandbox exists, `{DEMOCOURSEID}` in
     * a tour start_url must resolve to its real course ID via the
     * plugin config — this is the whole reason the helper exists.
     */
    public function test_democourseid_placeholder_resolves_to_sandbox(): void {
        $this->resetAfterTest();

        $id = sandbox_course::ensure();

        $url = start_url_resolver::resolve('/course/view.php?id={DEMOCOURSEID}', 1);

        $this->assertSame((string) $id, $url->get_param('id'));
    }
}
