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
 * Unit tests for the trainer_role provisioning helper.
 *
 * @package    local_lernhive_onboarding
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_onboarding;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

/**
 * @covers \local_lernhive_onboarding\trainer_role
 */
final class trainer_role_test extends advanced_testcase {

    /**
     * The install hook provisions the role, so it must already exist
     * with the canonical shortname and the learning-path capability.
     */
    public function test_role_exists_after_install(): void {
        $this->resetAfterTest();
        global $DB;

        $role = $DB->get_record('role', ['shortname' => trainer_role::SHORTNAME]);
        $this->assertNotFalse($role, 'lernhive_trainer role should be created by db/install.php');

        $systemcontext = \context_system::instance();
        $caps = get_capabilities_from_role_on_context((object) ['id' => $role->id], $systemcontext);
        $found = false;
        foreach ($caps as $cap) {
            if ($cap->capability === trainer_role::CAPABILITY && (int) $cap->permission === CAP_ALLOW) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Trainer role must have the receivelearningpath capability granted');
    }

    /**
     * ensure() is idempotent: calling it twice yields the same role id and
     * does not duplicate the role record.
     */
    public function test_ensure_is_idempotent(): void {
        $this->resetAfterTest();
        global $DB;

        $firstid = trainer_role::ensure();
        $secondid = trainer_role::ensure();

        $this->assertSame($firstid, $secondid);
        $this->assertSame(
            1,
            $DB->count_records('role', ['shortname' => trainer_role::SHORTNAME])
        );
    }

    /**
     * A user holding the trainer role gains the learning-path capability.
     */
    public function test_assigned_user_has_capability(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $roleid = trainer_role::get_id();
        $this->assertNotNull($roleid, 'trainer role must already exist post-install');

        $systemcontext = \context_system::instance();
        role_assign($roleid, $user->id, $systemcontext->id);

        $this->assertTrue(
            has_capability(trainer_role::CAPABILITY, $systemcontext, $user->id),
            'User assigned to lernhive_trainer at system context must hold the learning-path capability'
        );
    }

    /**
     * A plain user (no assignment) must NOT hold the learning-path capability.
     */
    public function test_unassigned_user_lacks_capability(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $systemcontext = \context_system::instance();

        $this->assertFalse(
            has_capability(trainer_role::CAPABILITY, $systemcontext, $user->id),
            'Users without the trainer role must not see the learning path banner'
        );
    }
}
