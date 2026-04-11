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
 * Visibility gate for the LernHive dashboard banner.
 *
 * @package    local_lernhive_onboarding
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_onboarding;

defined('MOODLE_INTERNAL') || die();

/**
 * Decides whether the trainer learning-path banner should appear for a user.
 *
 * Separated from the hook callback so the logic can be unit-tested without
 * spinning up a full request/page context.
 */
class banner_gate {

    /**
     * Whether to show the banner on the Moodle dashboard for the given user.
     *
     * The gate has three independent requirements, all of which must be true:
     *
     * 1. The user must be a real, logged-in, non-guest account.
     * 2. The user must hold the
     *    `local/lernhive_onboarding:receivelearningpath` capability at
     *    system context — granted via the dedicated `lernhive_trainer` role.
     * 3. The user must have at least one remaining tour on Level 1.
     *    Once Level 1 is fully complete the banner auto-hides; there is no
     *    separate dismiss mechanism in R1.
     *
     * The first two gates are cheap (cap check against already-loaded
     * accessdata). The Level 1 completeness check hits the database once
     * per request and is only evaluated when the previous gates pass.
     *
     * @param int $userid The user id to check. Pass 0 to get a definite no.
     * @return bool
     */
    public static function should_show(int $userid): bool {
        if ($userid <= 0) {
            return false;
        }

        if (isguestuser($userid)) {
            return false;
        }

        $systemcontext = \context_system::instance();
        if (!has_capability(trainer_role::CAPABILITY, $systemcontext, $userid)) {
            return false;
        }

        // Hide the banner once the user has completed Level 1 — the
        // Learning Path page is still reachable from the navigation but we
        // no longer push it.
        if (tour_manager::is_level_complete(1, $userid)) {
            return false;
        }

        return true;
    }
}
