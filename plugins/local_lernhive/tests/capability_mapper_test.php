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

namespace local_lernhive;

use advanced_testcase;
use local_lernhive\feature\override_store;
use local_lernhive\feature\registry;
use PHPUnit\Framework\Attributes\CoversClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Registry-driven capability mapper tests.
 *
 * @package    local_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(capability_mapper::class)]
final class capability_mapper_test extends advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        registry::reset_cache();
    }

    protected function tearDown(): void {
        registry::reset_cache();
        parent::tearDown();
    }

    public function test_get_level_modules_includes_assign_on_level_2_by_default(): void {
        $modules = capability_mapper::get_level_modules();
        $this->assertContains('mod_assign', $modules[2] ?? []);
        $this->assertNotContains('mod_assign', $modules[1] ?? []);
    }

    public function test_get_level_modules_reflects_admin_override(): void {
        $this->resetAfterTest(true);

        override_store::set_admin_override('mod_assign.create', 1);
        $modules = capability_mapper::get_level_modules();

        $this->assertContains('mod_assign', $modules[1] ?? []);
        $this->assertNotContains('mod_assign', $modules[2] ?? []);
    }

    public function test_disabled_module_is_removed_from_module_lists(): void {
        $this->resetAfterTest(true);

        override_store::set_admin_override('mod_assign.create', null);
        $allmodules = capability_mapper::get_all_modules();
        $allowedat5 = capability_mapper::get_allowed_modules(5);

        $this->assertNotContains('mod_assign', $allmodules);
        $this->assertNotContains('mod_assign', $allowedat5);
    }
}
