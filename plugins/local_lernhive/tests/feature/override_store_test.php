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

namespace local_lernhive\feature;

use advanced_testcase;
use PHPUnit\Framework\Attributes\CoversClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Integration tests for feature override storage.
 *
 * @package    local_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(override_store::class)]
final class override_store_test extends advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        registry::reset_cache();
    }

    protected function tearDown(): void {
        registry::reset_cache();
        parent::tearDown();
    }

    public function test_apply_flavor_preset_is_idempotent_for_same_payload(): void {
        global $DB;
        $this->resetAfterTest(true);

        $first = override_store::apply_flavor_preset('mod_assign.create', 3, 'lxp');
        $second = override_store::apply_flavor_preset('mod_assign.create', 3, 'lxp');

        $this->assertTrue($first);
        $this->assertFalse($second);
        $this->assertSame(
            1,
            $DB->count_records('local_lernhive_feature_overrides', ['feature_id' => 'mod_assign.create'])
        );

        $levels = override_store::get_effective_levels();
        $this->assertSame(3, $levels['mod_assign.create']);
    }

    public function test_flavor_preset_does_not_overwrite_admin_override(): void {
        $this->resetAfterTest(true);

        override_store::set_admin_override('mod_assign.create', 2);
        $written = override_store::apply_flavor_preset('mod_assign.create', 5, 'lxp');

        $this->assertFalse($written);
        $row = override_store::get_effective_override('mod_assign.create');
        $this->assertNotNull($row);
        $this->assertSame(override_store::SOURCE_ADMIN, $row->source);
        $this->assertSame(2, (int) $row->override_level);
    }

    public function test_set_admin_override_replaces_existing_flavor_row(): void {
        $this->resetAfterTest(true);

        override_store::apply_flavor_preset('mod_assign.create', 4, 'lxp');
        override_store::set_admin_override('mod_assign.create', 2, 1234);

        $row = override_store::get_effective_override('mod_assign.create');
        $this->assertNotNull($row);
        $this->assertSame(override_store::SOURCE_ADMIN, $row->source);
        $this->assertNull($row->flavor_id);
        $this->assertSame(2, (int) $row->override_level);
        $this->assertSame(1234, (int) $row->updated_by);
    }

    public function test_clear_admin_override_deletes_row(): void {
        $this->resetAfterTest(true);

        override_store::set_admin_override('mod_assign.create', null);
        override_store::clear_admin_override('mod_assign.create');

        $this->assertNull(override_store::get_effective_override('mod_assign.create'));
    }

    public function test_set_admin_override_triggers_feature_override_changed_event(): void {
        $this->resetAfterTest(true);

        $sink = $this->redirectEvents();
        override_store::set_admin_override('mod_assign.create', 3);
        $events = $sink->get_events();
        $sink->close();

        $found = false;
        foreach ($events as $event) {
            if (!($event instanceof \local_lernhive\event\feature_override_changed)) {
                continue;
            }
            $found = true;
            $this->assertSame('mod_assign.create', $event->other['feature_id']);
            $this->assertSame(3, $event->other['new_level']);
            $this->assertSame('set', $event->other['action']);
        }
        $this->assertTrue($found, 'feature_override_changed event was not triggered');
    }

    public function test_replace_flavor_preset_map_removes_stale_rows(): void {
        $this->resetAfterTest(true);

        override_store::replace_flavor_preset_map('school', [
            'mod_assign.create' => 4,
            'core.user.create' => 2,
        ]);
        $this->assertNotNull(override_store::get_effective_override('mod_assign.create'));

        override_store::replace_flavor_preset_map('lxp', [
            'core.user.create' => 1,
        ]);

        $this->assertNull(override_store::get_effective_override('mod_assign.create'));
        $row = override_store::get_effective_override('core.user.create');
        $this->assertNotNull($row);
        $this->assertSame(1, (int) $row->override_level);
        $this->assertSame('lxp', (string) $row->flavor_id);
    }
}
