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
 * LernHive plugin installation hook.
 *
 * @package    local_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * LernHive plugin installation function.
 *
 * Tour categories are now managed by local_lernhive_start.
 * This install hook is intentionally empty — the DB tables
 * (local_lernhive_levels, local_lernhive_teacher_cats) are
 * created automatically from install.xml.
 */
function xmldb_local_lernhive_install() {
    // Nothing to do — tables are created from install.xml,
    // tour seeding happens in local_lernhive_start.
}
