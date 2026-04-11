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
 * English language strings for LernHive Onboarding.
 *
 * @package    local_lernhive_onboarding
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'LernHive Onboarding';

// Privacy.
$string['privacy:null_reason'] = 'This plugin does not store any user data. Tour completion is tracked by Moodle core in user preferences.';

// Capabilities.
$string['lernhive_onboarding:viewtours'] = 'View onboarding tours';
$string['lernhive_onboarding:receivelearningpath'] = 'See the Trainer learning-path dashboard banner';

// Trainer role (created by install.php / upgrade.php).
$string['trainer_role_name'] = 'LernHive Trainer';
$string['trainer_role_description'] = 'Marks a user as a LernHive trainer who receives the guided Learning Path dashboard banner and onboarding tours.';

// Dashboard banner.
$string['banner_heading'] = 'Your Trainer Learning Path';
$string['banner_intro_start'] = 'Start your step-by-step onboarding — guided tours walk you through every Level 1 skill.';
$string['banner_intro_resume'] = 'Pick up where you left off — a few more tours and you\'ve completed Level 1.';
$string['banner_cta_start'] = 'Start Learning Path';
$string['banner_cta_resume'] = 'Continue Learning Path';
$string['banner_progress_label'] = '{$a->done} of {$a->total} tours';

// Tour overview (Onboarding).
$string['tours_pagetitle'] = 'Your Onboarding';
$string['tours_heading'] = 'Your Onboarding';
$string['tours_intro'] = 'Step by step through LernHive — interactive tours guide you through all key features.';

// --- Plugin Shell (0.2.2) ---
// Zone A (sticky header) + Zone B (info bar) strings for tours.php.
$string['shell_name'] = 'Onboarding';
$string['shell_tagline'] = 'Learning Path';
$string['shell_subtitle'] = 'Step by step through LernHive — interactive tours guide you through every key Trainer skill.';
$string['shell_hint'] = 'Complete a category to unlock the next level of tours.';
$string['shell_tag_level'] = 'Level {$a}';
$string['tours_level_badge'] = 'Level {$a->level}: {$a->name}';
$string['tours_overall_progress'] = 'Overall progress';
$string['tours_x_of_y'] = '{$a->done} of {$a->total} tours';
$string['tours_status_not_started'] = 'Not started';
$string['tours_status_in_progress'] = 'In progress';
$string['tours_status_completed'] = 'Completed';
$string['tours_x_of_y_tours'] = '{$a->done} of {$a->total} tours';
$string['tours_x_of_y_tour'] = '{$a->done} of {$a->total} tour';
$string['tours_start'] = 'Start tour';
$string['tours_restart'] = 'Restart';
$string['tours_unlock_title'] = 'Ready for the next level?';
$string['tours_unlock_text'] = 'Complete all {$a->cats} topics to unlock Level {$a->nextlevel} ({$a->nextname}). {$a->remaining} tours remaining.';
$string['tours_unlock_btn'] = 'Unlock Level {$a->level}: {$a->name}';
$string['tours_all_done'] = 'You\'ve completed all tours on this level!';
$string['tours_nav_link'] = 'Learning Path';

// Tour categories (Level 1: Explorer).
$string['tourcat_create_users'] = 'Create users';
$string['tourcat_create_users_desc'] = 'Learn how to create user accounts — individually or via CSV upload.';
$string['tourcat_enrol_users'] = 'Enrol users';
$string['tourcat_enrol_users_desc'] = 'Enrol users in courses — manually or via self-enrolment.';
$string['tourcat_create_courses'] = 'Create courses';
$string['tourcat_create_courses_desc'] = 'Create your first course with the streamlined LernHive interface.';
$string['tourcat_course_settings'] = 'Course settings';
$string['tourcat_course_settings_desc'] = 'Understand the key settings — format, visibility, completion tracking.';
$string['tourcat_create_activities'] = 'Create activities';
$string['tourcat_create_activities_desc'] = 'Add assignments, forums and upload files — the foundation of every course.';
$string['tourcat_communication'] = 'Communication';
$string['tourcat_communication_desc'] = 'Post announcements and send messages to users.';

// Tour names (Level 1: Explorer).
$string['tour_create_user_single'] = 'Create a single user';
$string['tour_create_user_single_desc'] = 'Create a new account step by step.';
$string['tour_create_user_csv'] = 'CSV upload';
$string['tour_create_user_csv_desc'] = 'Create multiple users at once via CSV file.';
$string['tour_enrol_manual'] = 'Manual enrolment';
$string['tour_enrol_manual_desc'] = 'Enrol users individually in a course.';
$string['tour_enrol_self'] = 'Set up self-enrolment';
$string['tour_enrol_self_desc'] = 'Allow users to enrol themselves in the course.';
$string['tour_create_course'] = 'Create a course';
$string['tour_create_course_desc'] = 'Create a new course with the streamlined interface.';
$string['tour_course_format'] = 'Course format & visibility';
$string['tour_course_format_desc'] = 'Adjust the format, visibility and appearance of your course.';
$string['tour_course_completion'] = 'Completion tracking';
$string['tour_course_completion_desc'] = 'Set up course completion criteria for users.';
$string['tour_activity_assignment'] = 'Create an assignment';
$string['tour_activity_assignment_desc'] = 'Create a submission assignment for your class.';
$string['tour_activity_forum'] = 'Create a forum';
$string['tour_activity_forum_desc'] = 'Set up a discussion forum for your class.';
$string['tour_activity_file'] = 'Upload a file';
$string['tour_activity_file_desc'] = 'Provide PDFs, images or other files in your course.';
$string['tour_communication_announcements'] = 'Post announcements';
$string['tour_communication_announcements_desc'] = 'Share news with everyone via the announcements forum.';
$string['tour_communication_messaging'] = 'Send messages';
$string['tour_communication_messaging_desc'] = 'Send direct messages to individual users.';

// Onboarding sandbox course — hidden course that {DEMOCOURSEID} resolves to.
$string['sandbox_course_fullname'] = 'LernHive Onboarding Sandbox';
$string['sandbox_course_summary'] = '<p>Hidden sandbox course used by LernHive trainer onboarding tours as a safe target for course-context features. Delete at your own risk — tours that rely on <code>{DEMOCOURSEID}</code> will fall back to an invalid course if you do.</p>';

// Admin settings.
$string['setting_trainercoursecategoryid'] = 'Trainer course category';
$string['setting_trainercoursecategoryid_desc'] = 'The course category that the "Create a course" onboarding tour lands novice trainers in. Admins should point this at the category where their trainers are expected to create new courses — especially on multi-tenant installs where the default <em>Miscellaneous</em> category is typically hidden from trainers.';
