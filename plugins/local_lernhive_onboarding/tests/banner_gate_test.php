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
 * Unit tests for the dashboard banner visibility gate.
 *
 * @package    local_lernhive_onboarding
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_onboarding;

use advanced_testcase;
use PHPUnit\Framework\Attributes\CoversClass;

defined('MOODLE_INTERNAL') || die();

#[CoversClass(banner_gate::class)]
final class banner_gate_test extends advanced_testcase {

    /**
     * Passing userid 0 always returns false — guards against accidental
     * calls from request paths where $USER has not yet been hydrated.
     */
    public function test_userid_zero_returns_false(): void {
        $this->resetAfterTest();
        $this->assertFalse(banner_gate::should_show(0));
    }

    /**
     * Guest users never see the banner, even if somehow assigned the role.
     */
    public function test_guest_user_returns_false(): void {
        $this->resetAfterTest();
        global $CFG;
        require_once($CFG->libdir . '/moodlelib.php');

        $guestid = (int) guest_user()->id;
        $this->assertFalse(banner_gate::should_show($guestid));
    }

    /**
     * A logged-in user without the trainer role cannot see the banner.
     */
    public function test_user_without_trainer_role_returns_false(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->assertFalse(banner_gate::should_show((int) $user->id));
    }

    /**
     * A user with the trainer role AND incomplete Level 1 sees the banner.
     */
    public function test_trainer_with_incomplete_level_returns_true(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->assign_trainer_role((int) $user->id);

        $this->assertTrue(
            banner_gate::should_show((int) $user->id),
            'Trainer with incomplete Level 1 should see the banner'
        );
    }

    /**
     * A trainer who has already completed every Level 1 tour no longer
     * sees the banner — the banner auto-hides on level completion.
     */
    public function test_trainer_with_complete_level_returns_false(): void {
        $this->resetAfterTest();
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $this->assign_trainer_role((int) $user->id);

        // Mark every Level 1 tour as completed by the user.
        $categories = tour_manager::get_categories(1);
        $tourids = [];
        foreach ($categories as $cat) {
            foreach (tour_manager::get_category_tours($cat->id) as $mapping) {
                $tourids[(int) $mapping->tourid] = true;
            }
        }
        foreach (array_keys($tourids) as $tourid) {
            set_user_preference(
                'tool_usertours_' . $tourid . '_completed',
                time(),
                $user->id
            );
        }

        $this->assertTrue(
            tour_manager::is_level_complete(1, (int) $user->id),
            'Sanity check: the test has just marked every Level 1 tour as completed'
        );
        $this->assertFalse(
            banner_gate::should_show((int) $user->id),
            'Trainer who completed Level 1 must no longer see the banner'
        );
    }

    /**
     * Helper — assign the lernhive_trainer role at system context.
     */
    private function assign_trainer_role(int $userid): void {
        $roleid = trainer_role::get_id();
        $this->assertNotNull($roleid, 'Trainer role must be provisioned by install.php');
        role_assign($roleid, $userid, \context_system::instance()->id);
    }
}
