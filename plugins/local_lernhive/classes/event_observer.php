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

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer for LernHive plugin.
 *
 * Handles user login to ensure level-based capability restrictions
 * are applied, and tracks course module events for future analytics.
 *
 * @package    local_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class event_observer {

    /**
     * Called when a user logs in.
     *
     * Ensures the LernHive capability restrictions are applied
     * based on the user's current level. This covers the case where
     * a teacher's level was set while they were offline, or a new
     * teacher logs in for the first time (gets default level).
     *
     * @param \core\event\user_loggedin $event
     */
    public static function user_loggedin(\core\event\user_loggedin $event): void {
        global $DB;

        $userid = $event->objectid;

        // Wrap everything in try/catch — if the DB tables don't exist yet
        // (e.g. during install/upgrade), login must still work.
        try {
            // Check if this user is an editing teacher anywhere.
            $editingteacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
            if (!$editingteacherrole) {
                return;
            }

            $hasrole = $DB->record_exists('role_assignments', [
                'roleid' => $editingteacherrole->id,
                'userid' => $userid,
            ]);

            if (!$hasrole) {
                return;
            }

            // Get the user's LernHive level (default to configured default or 1).
            $level = level_manager::get_level($userid);

            // Ensure the level record exists (create if first login).
            $record = level_manager::get_level_record($userid);
            if (!$record) {
                // Create initial level record with the default level.
                $defaultlevel = (int) get_config('local_lernhive', 'default_level');
                if ($defaultlevel < 1 || $defaultlevel > level_manager::LEVEL_MAX) {
                    $defaultlevel = 1;
                }
                $now = time();
                $newrecord = new \stdClass();
                $newrecord->userid = $userid;
                $newrecord->level = $defaultlevel;
                $newrecord->updated_by = $userid;
                $newrecord->timemodified = $now;
                $newrecord->timecreated = $now;
                $DB->insert_record('local_lernhive_levels', $newrecord);
                $level = $defaultlevel;
            }

            // Apply the capability restrictions.
            capability_mapper::apply_level($userid, $level);
        } catch (\Throwable $e) {
            // Tables may not exist yet during install/upgrade — silently ignore.
            // IMPORTANT: Do NOT call debugging() here — it produces output that
            // breaks header redirects (session/login), causing "headers already sent".
        }
    }

    /**
     * Called when a course module is created.
     *
     * Placeholder for future analytics / challenge tracking.
     *
     * @param \core\event\course_module_created $event
     */
    public static function course_module_created(\core\event\course_module_created $event): void {
        // Future: track module usage for level-up suggestions.
    }

    /**
     * Called when a course module is updated.
     *
     * Placeholder for future analytics / challenge tracking.
     *
     * @param \core\event\course_module_updated $event
     */
    public static function course_module_updated(\core\event\course_module_updated $event): void {
        // Future: track module usage for level-up suggestions.
    }
}
