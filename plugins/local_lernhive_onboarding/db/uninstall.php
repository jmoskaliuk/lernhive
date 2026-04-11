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
 * LernHive Onboarding plugin uninstall hook.
 *
 * @package    local_lernhive_onboarding
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * LernHive Onboarding plugin uninstall function.
 *
 * ## What we clean up
 *
 * - `local_lernhive_onboarding.democourseid` — the plugin config key
 *   that points at the Onboarding Sandbox course.
 *
 * ## What we intentionally DO NOT delete
 *
 * - **The sandbox course itself.** An admin may have added real
 *   content to it (graded assignments, participants, files), and
 *   deleting a course is destructive and non-recoverable. Uninstalling
 *   a plugin must not be a data-loss event. Admins who genuinely want
 *   to remove the course have to do so manually via
 *   Site administration → Courses → Manage courses and categories.
 * - **The `lernhive_trainer` role.** Core Moodle handles role
 *   cleanup via `db/access.php` when our capability rows disappear,
 *   and we do not want to remove a role that an admin may have
 *   reassigned to non-onboarding users.
 * - **`tool_usertours_tours` rows imported by this plugin.** Moodle
 *   core's own plugin uninstaller handles `local_lhonb_*` tables via
 *   the install.xml schema, but user tours shipped through our
 *   `tour_importer` live in `tool_usertours` and stay there — they
 *   are independent Moodle resources now, not plugin-owned data.
 *
 * If a future release decides to drop the sandbox course on uninstall
 * it should go through an explicit admin setting so the choice is
 * auditable ("I deliberately deleted this course").
 */
function xmldb_local_lernhive_onboarding_uninstall() {
    \local_lernhive_onboarding\sandbox_course::forget();
}
