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
 * English strings for local_lernhive_copy.
 *
 * @package    local_lernhive_copy
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'LernHive Copy';

// Capabilities (db/access.php).
$string['lernhive_copy:use'] = 'Use the LernHive Copy wizard';

// Admin tree.
$string['open_wizard'] = 'Open copy wizard';

// Page headings.
$string['page_title_copy'] = 'Copy a course';
$string['page_title_template'] = 'Start from a template';
$string['page_intro_copy'] = 'Pick an existing course to reuse as a starting point. The copy wizard hands off to Moodle backup and restore for the actual work.';
$string['page_intro_template'] = 'Pick a curated template to seed your new course. Templates are maintained by your organisation; changes to the template do not propagate into copies.';

// Modes.
$string['mode_simple'] = 'Simple';
$string['mode_simple_desc'] = 'Copy structure and activities. Skip participants, grades, and attempt data. Recommended for most trainers.';
$string['mode_expert'] = 'Expert';
$string['mode_expert_desc'] = 'Jump into the Moodle backup/restore screen with all options available.';
$string['mode_cta_simple'] = 'Start simple copy';
$string['mode_cta_expert'] = 'Open expert mode';

// Placeholder / empty states.
$string['not_implemented'] = 'This wizard is still under construction — Release 1 will wire it up to Moodle backup/restore. For now, use Site administration → Courses to copy content manually.';

// Privacy.
$string['privacy:metadata'] = 'The LernHive Copy plugin does not store any personal data itself — it hands off copy operations to Moodle core backup and restore, which have their own privacy providers.';
