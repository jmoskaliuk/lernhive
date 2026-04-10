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
 * Unit tests for the flavour manager.
 *
 * @package    local_lernhive_flavour
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_lernhive_flavour\flavour_manager
 */

namespace local_lernhive_flavour;

defined('MOODLE_INTERNAL') || die();

/**
 * @runTestsInSeparateProcesses
 */
final class flavour_manager_test extends \advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        flavour_registry::reset_cache();
    }

    public function test_get_active_falls_back_to_default_when_unset(): void {
        // Fresh install: nothing stored yet → school.
        set_config('active_flavour', null, 'local_lernhive_flavour');
        $this->assertSame('school', flavour_manager::get_active());
    }

    public function test_get_active_returns_stored_value(): void {
        set_config('active_flavour', 'lxp', 'local_lernhive_flavour');
        $this->assertSame('lxp', flavour_manager::get_active());
    }

    public function test_get_active_rejects_stored_garbage(): void {
        set_config('active_flavour', 'atlantis', 'local_lernhive_flavour');
        $this->assertSame('school', flavour_manager::get_active());
    }

    public function test_apply_writes_all_managed_keys(): void {
        $admin = get_admin();
        flavour_manager::apply('school', $admin->id);

        // Every key declared by the school profile should now be set.
        $this->assertSame('1', get_config('local_lernhive', 'default_level'));
        $this->assertSame('1', get_config('local_lernhive', 'show_levelbar'));
        $this->assertSame('1', get_config('local_lernhive', 'allow_teacher_course_creation'));
        $this->assertSame('1', get_config('local_lernhive', 'allow_teacher_user_creation'));
        $this->assertSame('1', get_config('local_lernhive', 'allow_teacher_user_browse'));
        $this->assertSame('0', get_config('local_lernhive', 'teacher_category_parent'));
    }

    public function test_apply_updates_active_flavour_config(): void {
        $admin = get_admin();
        flavour_manager::apply('lxp', $admin->id);
        $this->assertSame('lxp', get_config('local_lernhive_flavour', 'active_flavour'));
    }

    public function test_apply_lxp_flips_teacher_powers_off(): void {
        global $DB;
        $admin = get_admin();

        // Start from school.
        flavour_manager::apply('school', $admin->id);
        $this->assertSame('1', get_config('local_lernhive', 'allow_teacher_course_creation'));

        // Switch to LXP — teacher powers should flip off.
        flavour_manager::apply('lxp', $admin->id);
        $this->assertSame('0', get_config('local_lernhive', 'allow_teacher_course_creation'));
        $this->assertSame('0', get_config('local_lernhive', 'allow_teacher_user_creation'));
        $this->assertSame('0', get_config('local_lernhive', 'allow_teacher_user_browse'));
        $this->assertSame('0', get_config('local_lernhive', 'show_levelbar'));

        // Two applications, two audit rows.
        $this->assertSame(2, $DB->count_records('local_lernhive_flavour_apps'));
    }

    public function test_apply_on_fresh_site_does_not_flag_overrides(): void {
        global $DB;
        $admin = get_admin();

        $result = flavour_manager::apply('school', $admin->id);
        $this->assertFalse($result->overrides_detected);

        $record = $DB->get_record('local_lernhive_flavour_apps', ['id' => $result->audit_id]);
        $this->assertNotFalse($record);
        $this->assertSame(0, (int) $record->overrides_detected);
    }

    public function test_apply_after_existing_config_flags_overrides(): void {
        $admin = get_admin();

        // Admin manually configured something differently from any flavour default.
        set_config('allow_teacher_course_creation', 1, 'local_lernhive');
        set_config('show_levelbar', 1, 'local_lernhive');

        // Now apply LXP, which sets both to 0 → override detected.
        $result = flavour_manager::apply('lxp', $admin->id);
        $this->assertTrue($result->overrides_detected);
    }

    public function test_apply_rejects_unknown_flavour(): void {
        $this->expectException(\invalid_parameter_exception::class);
        flavour_manager::apply('atlantis', 2);
    }

    public function test_diff_reports_changes_against_current_state(): void {
        set_config('show_levelbar', 1, 'local_lernhive');

        $diff = flavour_manager::diff('lxp');

        // Find the show_levelbar entry.
        $entry = null;
        foreach ($diff as $row) {
            if ($row['component'] === 'local_lernhive' && $row['name'] === 'show_levelbar') {
                $entry = $row;
                break;
            }
        }
        $this->assertNotNull($entry);
        $this->assertSame('1', $entry['current']);
        $this->assertSame('0', $entry['target']);
        $this->assertTrue($entry['changes']);
    }

    public function test_has_pending_overrides_true_when_diff_has_changes(): void {
        set_config('allow_teacher_course_creation', 1, 'local_lernhive');
        // LXP sets this to 0 → has pending overrides.
        $this->assertTrue(flavour_manager::has_pending_overrides('lxp'));
    }

    public function test_has_pending_overrides_false_when_diff_is_clean(): void {
        // Fresh state — everything unset → no "changes" versus target.
        // Apply school, then ask whether applying school again would change anything.
        $admin = get_admin();
        flavour_manager::apply('school', $admin->id);
        $this->assertFalse(flavour_manager::has_pending_overrides('school'));
    }

    public function test_apply_triggers_flavour_applied_event(): void {
        $admin = get_admin();

        $sink = $this->redirectEvents();
        flavour_manager::apply('lxp', $admin->id);
        $events = $sink->get_events();
        $sink->close();

        $found = false;
        foreach ($events as $event) {
            if ($event instanceof \local_lernhive_flavour\event\flavour_applied) {
                $found = true;
                $this->assertSame('lxp', $event->other['flavour']);
                $this->assertSame('school', $event->other['previous']);
                break;
            }
        }
        $this->assertTrue($found, 'flavour_applied event was not triggered');
    }
}
