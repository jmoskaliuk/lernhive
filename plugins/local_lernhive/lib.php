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
 * LernHive library functions and callbacks.
 *
 * Note: The before_standard_top_of_body_html callback has been migrated
 * to the new Moodle 5.x hook system. See:
 * - classes/hook_callbacks.php (implementation)
 * - db/hooks.php (registration)
 *
 * @package    local_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add LernHive navigation nodes.
 *
 * @param global_navigation $navigation
 */
function local_lernhive_extend_navigation(global_navigation $navigation) {
    // Nothing needed here for MVP.
}

/**
 * Extend the settings navigation for the plugin.
 *
 * @param settings_navigation $settingsnav
 * @param context $context
 */
function local_lernhive_extend_settings_navigation(settings_navigation $settingsnav, context $context) {
    // Nothing needed here for MVP — settings.php handles admin menu.
}
