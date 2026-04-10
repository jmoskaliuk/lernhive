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
 * English strings for local_lernhive_flavour.
 *
 * @package    local_lernhive_flavour
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'LernHive Flavour';

// Admin page.
$string['page_title'] = 'Flavour Setup';
$string['page_intro'] = 'Choose the flavour that best matches your organisation. A flavour is a starting point: it applies sensible defaults for all LernHive plugins, but you can override any setting afterwards.';

// Flavour labels and descriptions.
$string['flavour_school'] = 'School';
$string['flavour_school_desc'] = 'Traditional LMS for schools and training providers. Teachers own their courses, manage their learners, and the level bar guides teacher onboarding.';
$string['flavour_lxp'] = 'LXP';
$string['flavour_lxp_desc'] = 'Learning Experience Platform. Explore replaces the dashboard, discovery is prominent, and teachers focus on Snacks rather than full courses.';
$string['flavour_highered'] = 'Higher Education';
$string['flavour_highered_desc'] = 'Universities and higher education institutions. Experimental starting point — currently inherits the School defaults until Higher Ed specifics are defined.';
$string['flavour_corporate'] = 'Corporate Academy';
$string['flavour_corporate_desc'] = 'In-house academies and corporate training. Experimental starting point — currently inherits the School defaults until Corporate Academy specifics are defined.';

// Card badges and buttons.
$string['badge_active'] = 'Active';
$string['badge_experimental'] = 'Experimental';
$string['btn_apply'] = 'Apply flavour';
$string['btn_current'] = 'Current flavour';
$string['btn_confirm_apply'] = 'Apply {$a} anyway';
$string['btn_cancel'] = 'Cancel';

// Confirm diff dialog.
$string['diff_heading'] = 'Confirm flavour change';
$string['diff_intro'] = 'Applying {$a} will change the following settings. Review the diff below and confirm if you want to proceed. All existing values will be overwritten.';
$string['diff_col_component'] = 'Component';
$string['diff_col_setting'] = 'Setting';
$string['diff_col_current'] = 'Current value';
$string['diff_col_target'] = 'New value';
$string['value_unset'] = '(not set)';

// Result notifications.
$string['flavour_applied'] = 'Flavour "{$a}" has been applied successfully.';
$string['flavour_applied_with_overrides'] = 'Flavour "{$a}" has been applied. Previously customised settings were overwritten — see the audit log for details.';
$string['current_flavour'] = 'Current flavour: {$a}';

// Errors.
$string['err_unknown_flavour'] = 'Unknown flavour key.';

// Events.
$string['event_flavour_applied'] = 'Flavour applied';

// Privacy.
$string['privacy:metadata'] = 'LernHive Flavour stores an audit trail of flavour applications. The audit trail records the user ID of the admin who triggered each apply, but does not store any other personal data.';
$string['privacy:metadata:local_lernhive_flavour_apps'] = 'Audit trail of flavour applications.';
$string['privacy:metadata:local_lernhive_flavour_apps:applied_by'] = 'The user who applied the flavour.';
$string['privacy:metadata:local_lernhive_flavour_apps:timeapplied'] = 'When the flavour was applied.';
$string['privacy:metadata:local_lernhive_flavour_apps:flavour'] = 'The flavour that was applied.';
