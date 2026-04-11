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
 * Provisioning helper for the LernHive trainer role.
 *
 * @package    local_lernhive_onboarding
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_onboarding;

defined('MOODLE_INTERNAL') || die();

/**
 * Creates and exposes the dedicated `lernhive_trainer` role.
 *
 * The role is installable, idempotent and context-system assignable. It grants
 * the `local/lernhive_onboarding:receivelearningpath` capability which is the
 * canonical visibility gate for the Trainer learning-path dashboard banner.
 *
 * Admins assign the role to users via the standard role assignment UI
 * (Site administration → Users → Permissions → Assign system roles). The
 * banner then appears automatically on the next request.
 */
class trainer_role {

    /**
     * Canonical role shortname. Stable API — do not rename.
     */
    public const SHORTNAME = 'lernhive_trainer';

    /**
     * Capability granted to the role. Must match the entry in db/access.php.
     */
    public const CAPABILITY = 'local/lernhive_onboarding:receivelearningpath';

    /**
     * Ensure the role exists with the correct capability and context level.
     *
     * Safe to call from install.php, upgrade.php, tests, or CLI. The method
     * is idempotent: it creates the role if missing, re-attaches the
     * capability if the admin detached it, and re-asserts the system context
     * level. It never removes a role, so admin customisations survive.
     *
     * @return int The role id.
     */
    public static function ensure(): int {
        global $DB;

        $role = $DB->get_record('role', ['shortname' => self::SHORTNAME]);
        if ($role) {
            $roleid = (int) $role->id;
        } else {
            $roleid = create_role(
                get_string('trainer_role_name', 'local_lernhive_onboarding'),
                self::SHORTNAME,
                get_string('trainer_role_description', 'local_lernhive_onboarding'),
                ''
            );
        }

        // Allow admins to assign the role at system level.
        set_role_contextlevels($roleid, [CONTEXT_SYSTEM]);

        // Grant the learning-path capability (idempotent — Moodle updates
        // the existing grant in place).
        $systemcontext = \context_system::instance();
        assign_capability(
            self::CAPABILITY,
            CAP_ALLOW,
            $roleid,
            $systemcontext->id,
            true
        );

        return $roleid;
    }

    /**
     * Get the role id without creating it.
     *
     * @return int|null Role id or null if the role has not been installed yet.
     */
    public static function get_id(): ?int {
        global $DB;
        $role = $DB->get_record('role', ['shortname' => self::SHORTNAME], 'id');
        return $role ? (int) $role->id : null;
    }
}
