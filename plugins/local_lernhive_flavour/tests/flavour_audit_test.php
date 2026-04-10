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
 * Unit tests for the flavour audit logger.
 *
 * @package    local_lernhive_flavour
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_lernhive_flavour\flavour_audit
 */

namespace local_lernhive_flavour;

defined('MOODLE_INTERNAL') || die();

/**
 * @runTestsInSeparateProcesses
 */
final class flavour_audit_test extends \advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_record_writes_row_and_returns_id(): void {
        global $DB;

        $id = flavour_audit::record(
            'lxp',
            'school',
            42,
            ['local_lernhive' => ['show_levelbar' => '1']],
            ['local_lernhive' => ['show_levelbar' => '0']],
            true
        );

        $this->assertGreaterThan(0, $id);

        $record = $DB->get_record(flavour_audit::TABLE, ['id' => $id]);
        $this->assertNotFalse($record);
        $this->assertSame('lxp', $record->flavour);
        $this->assertSame('school', $record->previous_flavour);
        $this->assertEquals(42, $record->applied_by);
        $this->assertSame(1, (int) $record->overrides_detected);
    }

    public function test_record_persists_json_snapshots(): void {
        $before = ['local_lernhive' => ['show_levelbar' => '1', 'default_level' => '2']];
        $after = ['local_lernhive' => ['show_levelbar' => '0', 'default_level' => '1']];

        flavour_audit::record('lxp', 'school', 1, $before, $after, true);

        $recent = flavour_audit::get_recent(1);
        $this->assertCount(1, $recent);
        $this->assertSame($before, $recent[0]->settings_before);
        $this->assertSame($after, $recent[0]->settings_after);
    }

    public function test_get_recent_returns_rows_newest_first(): void {
        flavour_audit::record('school', null, 1, [], [], false);
        // Ensure ordering is by timeapplied then id, even within the same second.
        flavour_audit::record('lxp', 'school', 1, [], [], false);
        flavour_audit::record('highered', 'lxp', 1, [], [], false);

        $recent = flavour_audit::get_recent(10);
        $this->assertCount(3, $recent);
        $this->assertSame('highered', $recent[0]->flavour);
        $this->assertSame('school', $recent[2]->flavour);
    }

    public function test_get_recent_respects_limit(): void {
        for ($i = 0; $i < 5; $i++) {
            flavour_audit::record('school', null, 1, [], [], false);
        }
        $this->assertCount(2, flavour_audit::get_recent(2));
    }

    public function test_get_last_for_flavour_returns_most_recent_match(): void {
        flavour_audit::record('lxp', 'school', 1, [], ['marker' => 'old'], false);
        flavour_audit::record('school', 'lxp', 1, [], [], false);
        flavour_audit::record('lxp', 'school', 2, [], ['marker' => 'new'], true);

        $last = flavour_audit::get_last_for_flavour('lxp');
        $this->assertNotNull($last);
        $this->assertSame(['marker' => 'new'], $last->settings_after);
        $this->assertEquals(2, $last->applied_by);
    }

    public function test_get_last_for_flavour_returns_null_when_no_match(): void {
        $this->assertNull(flavour_audit::get_last_for_flavour('corporate'));
    }

    public function test_count_all_reflects_row_total(): void {
        $this->assertSame(0, flavour_audit::count_all());
        flavour_audit::record('school', null, 1, [], [], false);
        flavour_audit::record('lxp', 'school', 1, [], [], false);
        $this->assertSame(2, flavour_audit::count_all());
    }
}
