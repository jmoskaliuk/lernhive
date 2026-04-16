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
 * English language strings for LernHive.
 *
 * @package    local_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'LernHive';
$string['admin_dashboard'] = 'LernHive Dashboard';
$string['settings'] = 'LernHive Settings';
$string['setting_level_configuration'] = 'Level configuration';

// Level names (English-first — canonical LernHive level names, identical in all languages).
$string['level_explorer'] = 'Explorer';
$string['level_creator'] = 'Creator';
$string['level_pro'] = 'Pro';
$string['level_expert'] = 'Expert';
$string['level_master'] = 'Master';

// Level descriptions.
$string['level_explorer_desc'] = 'First steps — files, pages and links';
$string['level_creator_desc'] = 'Tasks & feedback — assignments and grading';
$string['level_pro_desc'] = 'Tests & interaction — quizzes, H5P and learning paths';
$string['level_expert_desc'] = 'Collaboration — wiki, glossary and peer review';
$string['level_master_desc'] = 'Full access — all Moodle features';

// Dashboard.
$string['current_level'] = 'Level {$a->level} — {$a->name}';
$string['current_level_short'] = 'LernHive Level';
$string['total_teachers'] = 'Total teachers';
$string['save_level'] = 'Save';
$string['search_placeholder'] = 'Search by name or email...';
$string['no_teachers_found'] = 'No teachers found. Make sure users have the "Teacher" role assigned.';
$string['level_changed_success'] = '{$a->user}\'s level has been changed to {$a->level} — {$a->name}.';

// Level bar.
$string['next_level_hint'] = 'Next level ({$a->levelname}): {$a->modules}';

// Settings.
$string['setting_default_level'] = 'Default level for new teachers';
$string['setting_default_level_desc'] = 'Which level should new teachers receive by default?';
$string['setting_show_levelbar'] = 'Show level bar';
$string['setting_show_levelbar_desc'] = 'Displays the current LernHive level as a bar at the top of course pages for teachers.';
$string['setting_heading_level_configuration'] = 'Feature level configuration';
$string['setting_heading_level_configuration_desc'] = 'Set per-feature overrides for availability levels. "Default" keeps the registry level or flavour preset, "Disabled" hides the feature on all levels.';
$string['setting_feature_group'] = 'Category: {$a->category}';
$string['setting_feature_override_default'] = 'Default';
$string['setting_feature_override_disabled'] = 'Disabled';
$string['setting_feature_override_desc'] = 'Feature ID: {$a->featureid}<br>Default level: {$a->defaultlevel}<br>Required capability: <code>{$a->capability}</code>';

// Events.
$string['event_level_changed'] = 'LernHive level changed';
$string['event_feature_override_changed'] = 'LernHive feature override changed';

// Capabilities.
$string['lernhive:managelevel'] = 'Manage LernHive levels';
$string['lernhive:viewownlevel'] = 'View own LernHive level';

// Settings — headings.
$string['setting_heading_levels'] = 'Level settings';
$string['setting_heading_course_creation'] = 'Course creation';
$string['setting_heading_user_creation'] = 'User creation';

// Settings — course creation.
$string['setting_allow_course_creation'] = 'Allow teachers to create courses';
$string['setting_allow_course_creation_desc'] = 'When enabled, each teacher gets a personal course category and can create courses themselves. A "Create course" button is shown on the dashboard.';
$string['setting_parent_category'] = 'Parent category for teacher course areas';
$string['setting_parent_category_desc'] = 'Under which category should personal teacher course areas be created?';
$string['setting_parent_category_top'] = 'Top level';

// Settings — user creation.
$string['setting_allow_user_creation'] = 'Allow teachers to create users';
$string['setting_allow_user_creation_desc'] = 'When enabled, teachers can create new user accounts. The form is shown in simplified mode. A "Create user" button is shown on the dashboard.';

// Dashboard buttons.
$string['btn_create_course'] = 'Create course';
$string['btn_create_user'] = 'Create user';

// Course manager.
$string['teacher_category_desc'] = 'Personal course area of {$a}';

// Onboarding link (points to local_lernhive_start).
$string['onboarding_nav_link'] = 'Onboarding';

// Settings — user browsing.
$string['setting_heading_user_browse'] = 'User browsing';
$string['setting_allow_user_browse'] = 'Allow teachers to browse user list';
$string['setting_allow_user_browse_desc'] = 'When enabled, teachers can view the full list of users in this installation. The view is simplified for Explorer level.';

// User list page.
$string['user_list_title'] = 'Users';
$string['user_search_placeholder'] = 'Search by name or email...';
$string['user_list_count'] = '{$a} users';
$string['user_col_name'] = 'Name';
$string['user_col_lastaccess'] = 'Last access';
$string['user_col_actions'] = 'Actions';
$string['user_none_found'] = 'No users found.';
$string['user_suspend'] = 'Suspend';
$string['user_unsuspend'] = 'Unsuspend';
$string['user_suspended_badge'] = 'Suspended';
$string['user_delete_confirm'] = 'Do you really want to delete the account of "{$a}"? This cannot be undone.';
$string['user_deleted'] = 'User "{$a}" has been deleted.';

// Capabilities.
$string['lernhive:browseusers'] = 'Browse user list';

// Sidebar.
$string['nav_userlist'] = 'Users';

// Course enrolment page.
$string['enrol_title'] = 'Enrolment: {$a}';
$string['enrol_back_to_course'] = 'Back to course';
$string['enrol_search_title'] = 'Enrol users';
$string['enrol_search_placeholder'] = 'Search by name or email...';
$string['enrol_no_results'] = 'No matching users found.';
$string['enrol_results_count'] = '{$a} users found';
$string['enrol_btn'] = 'Enrol';
$string['enrol_enrolled_title'] = 'Enrolled users';
$string['enrol_enrolled_count'] = '{$a} enrolled';
$string['enrol_none_enrolled'] = 'No users enrolled yet.';
$string['enrol_remove_btn'] = 'Remove';
$string['enrol_confirm_remove'] = 'Really remove this user from the course?';
$string['enrol_added'] = 'User has been enrolled.';
$string['enrol_removed'] = 'User has been removed from the course.';

// Support page.
$string['support_title'] = 'Usage Support';
$string['support_onboarding_title'] = 'Onboarding Tours';
$string['support_onboarding_desc'] = 'Step-by-step guides help you learn all features available at your current level.';
$string['support_courses_title'] = 'Managing Courses';
$string['support_courses_desc'] = 'Learn how to create courses, add content and adjust settings.';
$string['support_users_title'] = 'Managing Users';
$string['support_users_desc'] = 'Learn how to create users, enrol them and assign roles.';
$string['support_contact_title'] = 'Need more help?';
$string['support_contact_desc'] = 'If you need further assistance, please contact your administrator.';
$string['nav_support'] = 'Support';

// Privacy.
$string['privacy:metadata:local_lernhive_levels'] = 'Stores the LernHive level for each teacher.';
$string['privacy:metadata:local_lernhive_levels:userid'] = 'The user ID of the teacher.';
$string['privacy:metadata:local_lernhive_levels:level'] = 'The current level (1-5).';
$string['privacy:metadata:local_lernhive_levels:updated_by'] = 'The user ID of the admin who changed the level.';
