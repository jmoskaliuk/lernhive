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
 * English strings for local_lernhive_reporting.
 *
 * @package    local_lernhive_reporting
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'LernHive Reporting';

// Capabilities.
$string['lernhive_reporting:view'] = 'View the LernHive reporting dashboard';

// Admin tree.
$string['open_reporting'] = 'Open LernHive Reporting';

// Dashboard headings.
$string['tile_users_title'] = 'How many users are in my course?';
$string['tile_popular_title'] = 'Which courses are the most popular?';
$string['tile_completion_title'] = 'Course completion report';
$string['dashboard_tagline'] = 'Dashboard';
$string['dashboard_subtitle'] = 'Simple answers to users, popularity, and completion in one place.';
$string['tag_release1'] = 'Release 1';
$string['tag_moodlebased'] = 'Moodle-based';
$string['filter_heading'] = 'Course filter';
$string['filter_helptext'] = 'Select one course to update all dashboard tiles consistently.';
$string['filter_apply'] = 'Show report';
$string['course_label'] = 'Course';
$string['top_course_label'] = 'Top course';
$string['popular_subtitle'] = 'Top usage across accessible courses.';
$string['popular_courses_ranked'] = 'courses ranked';
$string['no_course_available'] = 'No course available';
$string['no_data'] = 'No data yet';
$string['no_courses_notice'] = 'You do not have any accessible courses yet.';
$string['no_users_in_course_notice'] = 'No active participants are enrolled in this course yet.';
$string['no_popular_rows_notice'] = 'No course popularity data is available yet.';
$string['no_completion_rows_notice'] = 'No completion rows are available yet.';
$string['empty_users_in_selected_course'] = 'This course currently has no active participants.';
$string['empty_completion_no_participants'] = 'Completion is not available yet because this course has no active participants.';
$string['empty_completion_no_completions'] = 'Participants exist, but none have completed the course yet.';
$string['drilldown_popular'] = 'Popular courses';
$string['drilldown_users'] = 'Users in selected course';
$string['drilldown_completion'] = 'Completion by course';
$string['open_users_report'] = 'Open users report';
$string['open_popular_report'] = 'Open popular courses report';
$string['open_completion_report'] = 'Open completion report';
$string['export_csv'] = 'Export CSV';
$string['back_to_dashboard'] = 'Back to dashboard';
$string['participants_label'] = 'Participants';
$string['completed_label'] = 'Completed';
$string['pending_label'] = 'Pending';
$string['completion_rate_label'] = 'Completion rate';
$string['rows_label'] = 'rows';
$string['name_label'] = 'Name';
$string['email_label'] = 'Email';
$string['lastaccess_label'] = 'Last access';
$string['never_accessed'] = 'Never';

// Privacy.
$string['privacy:metadata'] = 'The LernHive Reporting plugin does not store any personal data. It reads Moodle reporting and completion data to render dashboard tiles.';
