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
 * Language strings for local_testdata plugin.
 *
 * @package    local_testdata
 * @copyright  2024 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Test Data Generator';
$string['manage_capability'] = 'Manage test data';

// Capabilities.
$string['testdata:manage'] = 'Manage test data generator';

// Dashboard.
$string['dashboard_title'] = 'Test Data Generator';
$string['datasets_heading'] = 'Generated Datasets';
$string['no_datasets'] = 'No test datasets have been generated yet. Use the buttons above to create some!';
$string['dataset_name'] = 'Dataset';
$string['dataset_description'] = 'Description';
$string['dataset_created'] = 'Created';
$string['dataset_items'] = 'Contents';
$string['dataset_actions'] = 'Actions';

// Action cards.
$string['action_generate'] = 'Custom Generator';
$string['action_generate_desc'] = 'Create a custom dataset by choosing exactly which entities to generate: users, courses, questions, activities.';
$string['action_generate_btn'] = 'Open Generator';
$string['action_quickdemo'] = 'LeitnerFlow Demo';
$string['action_quickdemo_desc'] = 'Generate a complete demo setup: 4 courses, 20 students, questions and LeitnerFlow activities.';
$string['action_quickdemo_btn'] = 'Generate Demo Data';
$string['action_stats'] = 'Statistics';
$string['stats_summary'] = '{$a->datasets} dataset(s) with {$a->items} total entities';

// Buttons.
$string['delete_button'] = 'Delete';
$string['delete_all_button'] = 'Delete All Datasets';

// Confirmations.
$string['delete_confirm'] = 'Really delete dataset "{$a}" and ALL its entities (courses, users, questions)?';
$string['delete_all_confirm'] = 'Really delete ALL test datasets? This removes all generated courses, users, questions, etc.';
$string['quickgen_confirm'] = 'Generate LeitnerFlow demo data? This will create courses, users, questions and activities.';

// Messages.
$string['dataset_deleted'] = 'Dataset "{$a}" and all its entities have been deleted.';
$string['all_deleted'] = 'All test datasets have been deleted.';
$string['deletion_failed'] = 'Failed to delete dataset "{$a}".';

// Generate form.
$string['gen_page_title'] = 'Generate Test Data';
$string['gen_page_heading'] = 'Generate Test Data';
$string['gen_dataset_header'] = 'Dataset';
$string['gen_dataset_name'] = 'Dataset name';
$string['gen_dataset_desc'] = 'Description (optional)';

$string['gen_template_header'] = 'Template';
$string['gen_template'] = 'Use template';
$string['gen_template_help'] = 'Select a predefined configuration template, or choose "None" to configure everything manually below.';
$string['gen_template_none'] = 'None — configure manually';

$string['gen_users_header'] = 'Users';
$string['gen_users_enable'] = 'Generate users';
$string['gen_users_count'] = 'Number of users';
$string['gen_users_password'] = 'Password for all users';
$string['gen_users_prefix'] = 'Username prefix';

$string['gen_courses_header'] = 'Courses';
$string['gen_courses_enable'] = 'Generate courses';
$string['gen_courses_count'] = 'Number of courses';
$string['gen_courses_prefix'] = 'Course name prefix';

$string['gen_questions_header'] = 'Questions';
$string['gen_questions_enable'] = 'Generate multichoice questions';
$string['gen_questions_per_course'] = 'Questions per course';

$string['gen_activities_header'] = 'Activities';
$string['gen_activities_enable'] = 'Create activities in each course';
$string['gen_activity_type'] = 'Activity type';

$string['gen_enrol_header'] = 'Enrolments';
$string['gen_enrol_enable'] = 'Enrol all generated users in all courses';
$string['gen_enrol_role'] = 'Role';

$string['gen_submit'] = 'Generate Test Data';
$string['gen_back_to_dashboard'] = 'Back to Dashboard';

// Results.
$string['gen_results_title'] = 'Generation Results';
$string['gen_results_success'] = 'Dataset "{$a}" was generated successfully!';
$string['gen_results_error'] = 'Errors occurred during generation. Check the log below.';
$string['gen_results_log'] = 'Generation Log';

// Errors.
$string['gen_error_name_exists'] = 'A dataset with this name already exists.';
$string['gen_error_nothing_selected'] = 'Select at least one entity type to generate.';
$string['gen_error_template_not_found'] = 'Template configuration file not found.';
$string['gen_error_invalid_json'] = 'Template configuration contains invalid JSON.';

// Items table (legacy).
$string['item_id'] = 'ID';
$string['item_type'] = 'Type';
$string['item_name'] = 'Name';
$string['item_created'] = 'Created';

// Admin page (legacy).
$string['admin_title'] = 'Test Data Generator';
$string['admin_heading'] = 'Generated Test Data Sets';
$string['show_items_button'] = 'View Items';

// Generator progress messages.
$string['creating_users'] = 'Creating {$a} user(s)...';
$string['users_created'] = 'Created {$a} user(s).';
$string['creating_courses'] = 'Creating {$a} course(s)...';
$string['courses_created'] = 'Created {$a} course(s).';
$string['creating_questions'] = 'Creating {$a} question(s)...';
$string['questions_created'] = 'Created {$a} question(s).';
$string['enrolling_users'] = 'Enrolling {$a} user(s)...';
$string['users_enrolled'] = 'Enrolled {$a} user(s).';
$string['creating_activities'] = 'Creating {$a} activity(ies)...';
$string['activities_created'] = 'Created {$a} activity(ies).';
$string['error_creating_entity'] = 'Error creating {$a->type}: {$a->error}';
$string['config_loaded'] = 'Configuration loaded for dataset: {$a}';
$string['run_complete'] = 'Dataset "{$a}" created successfully.';
$string['cleaning_dataset'] = 'Cleaning dataset "{$a}"...';
$string['dataset_cleaned'] = 'Dataset "{$a}" cleaned successfully.';
