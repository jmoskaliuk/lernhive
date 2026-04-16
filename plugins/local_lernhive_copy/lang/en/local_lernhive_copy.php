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
$string['page_title_course'] = 'Copy a course';
$string['page_intro_course'] = 'Pick an existing course to reuse as a starting point. The Simple copy wizard hands the source course to Moodle backup and restore, which run asynchronously in the background.';
$string['page_title_copy'] = 'Copy a course';
$string['page_title_template'] = 'Start from a template';
$string['page_intro_copy'] = 'Pick an existing course to reuse as a starting point. The Simple copy wizard hands the source course to Moodle backup and restore, which run asynchronously in the background.';
$string['page_intro_template'] = 'Pick a curated template from the Library catalog. After selecting one, continue in Simple or Expert mode.';
$string['shell_tag_copy'] = 'Copy workflow';
$string['shell_tag_course'] = 'Course source';
$string['shell_tag_template'] = 'Template source';
$string['infobar_hint'] = 'Pick Simple for the guided flow or Expert for Moodle core copy controls.';

// Modes (stub / template fallback).
$string['mode_simple'] = 'Simple';
$string['mode_simple_desc'] = 'Copy structure and activities. Skip participants, grades, and attempt data. Recommended for most trainers.';
$string['mode_expert'] = 'Expert';
$string['mode_expert_desc'] = 'Jump into the Moodle backup/restore screen with all options available.';
$string['mode_cta_simple'] = 'Start simple copy';
$string['mode_cta_expert'] = 'Open expert mode';
$string['expert_intro'] = 'Expert mode opens Moodle core copy options, including advanced user-data and date controls.';
$string['expert_submit'] = 'Open expert copy options';

// Placeholder / empty states.
$string['not_implemented'] = 'Template copy is still under construction. The course copy flow on the other card is live — use it to duplicate an existing course.';

// Simple copy form.
$string['form_source_course'] = 'Source course';
$string['form_source_course_help'] = 'The existing course you want to copy. Only courses you are allowed to back up are shown.';
$string['form_target_fullname'] = 'New course full name';
$string['form_target_shortname'] = 'New course short name';
$string['form_target_category'] = 'Target category';
$string['form_target_visible'] = 'Visible';
$string['form_include_userdata'] = 'Include participants and progress';
$string['form_include_userdata_help'] = 'When enabled, enrolments, grades, and attempt data are copied along with the course structure. Keep this off if you want a clean copy to reuse for a new cohort.';
$string['form_include_userdata_hint'] = 'Default is a clean copy (no participants/progress). Enable this only when you intentionally want to clone learner data.';
$string['form_submit'] = 'Start copy';
$string['form_queued'] = 'Copy queued. Moodle will finish the copy in the background; you can follow progress here.';
$string['return_to_contenthub'] = 'Back to ContentHub';
$string['active_template'] = 'Selected template:';

// Template catalog.
$string['template_catalog_heading'] = 'Available templates';
$string['template_catalog_empty'] = 'No templates are currently available in the Library catalog.';
$string['template_meta_version'] = 'Version';
$string['template_meta_updated'] = 'Last updated';
$string['template_meta_language'] = 'Language';
$string['template_select'] = 'Use template';
$string['template_info'] = 'Template details';
$string['template_unavailable'] = 'Unavailable';
$string['template_unavailable_hint'] = 'This template is listed in the catalog but has no source-course mapping yet.';
$string['template_library_missing'] = 'Template mode requires the LernHive Library backend. Ask your admin to install and configure local_lernhive_library.';
$string['template_library_unsupported'] = 'Template mode requires a newer local_lernhive_library version with template mapping support. Please upgrade the Library plugin.';
$string['template_not_found'] = 'The selected template is not available: {$a}';
$string['template_source_missing'] = 'The selected template currently has no usable source course.';
$string['template_source_deleted'] = 'The selected template points to a course that no longer exists.';

// Privacy.
$string['privacy:metadata'] = 'The LernHive Copy plugin stores a per-user preference for the default target category and delegates copy operations to Moodle core backup/restore.';
$string['privacy:metadata:preference:defaultcategory'] = 'The user preference storing the default target category for new copy operations.';
