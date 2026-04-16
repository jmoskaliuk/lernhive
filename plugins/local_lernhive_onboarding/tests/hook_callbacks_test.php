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
 * Unit tests for onboarding hook callbacks.
 *
 * @package    local_lernhive_onboarding
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_onboarding;

use advanced_testcase;
use PHPUnit\Framework\Attributes\CoversClass;
use tool_usertours\hook\before_serverside_filter_fetch;

defined('MOODLE_INTERNAL') || die();

#[CoversClass(hook_callbacks::class)]
final class hook_callbacks_test extends advanced_testcase {
    /**
     * Forced launch state should consume session data once and tailor filters.
     */
    public function test_configure_forced_tour_filter_consumes_state_and_adjusts_filters(): void {
        $this->resetAfterTest();
        global $SESSION;

        forced_tour_state::set_forced_tour_id(0);
        $SESSION->local_lhonb_forced_tour_launch = [
            'tourid' => 42,
            'expires' => time() + 60,
        ];

        $hook = new before_serverside_filter_fetch([
            \tool_usertours\local\filter\role::class,
        ]);

        hook_callbacks::configure_forced_tour_filter($hook);

        $this->assertSame(42, forced_tour_state::get_forced_tour_id());
        $this->assertObjectNotHasProperty('local_lhonb_forced_tour_launch', $SESSION);

        $filters = $hook->get_filter_list();
        $this->assertNotContains(\tool_usertours\local\filter\role::class, $filters);
        $this->assertContains(\local_lernhive_onboarding\local\filter\forced_tour::class, $filters);
    }

    /**
     * Expired state should be discarded and not mutate the filter list.
     */
    public function test_configure_forced_tour_filter_ignores_expired_state(): void {
        $this->resetAfterTest();
        global $SESSION;

        forced_tour_state::set_forced_tour_id(0);
        $SESSION->local_lhonb_forced_tour_launch = [
            'tourid' => 42,
            'expires' => time() - 1,
        ];

        $hook = new before_serverside_filter_fetch([
            \tool_usertours\local\filter\role::class,
        ]);

        hook_callbacks::configure_forced_tour_filter($hook);

        $this->assertNull(forced_tour_state::get_forced_tour_id());
        $this->assertObjectNotHasProperty('local_lhonb_forced_tour_launch', $SESSION);

        $filters = $hook->get_filter_list();
        $this->assertContains(\tool_usertours\local\filter\role::class, $filters);
        $this->assertNotContains(\local_lernhive_onboarding\local\filter\forced_tour::class, $filters);
    }
}

