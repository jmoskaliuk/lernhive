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
 * LernHive Lernpfad (Learning Path) — Tour overview page.
 *
 * Shows tour categories for the user's current level, with progress
 * tracking per category and overall.
 *
 * @package    local_lernhive_onboarding
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$level = \local_lernhive\level_manager::get_level($USER->id);
$levelname = \local_lernhive\level_manager::get_level_name($level);

// Page setup.
$PAGE->set_url(new moodle_url('/local/lernhive_onboarding/tours.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('tours_pagetitle', 'local_lernhive_onboarding'));
$PAGE->set_heading(get_string('tours_heading', 'local_lernhive_onboarding'));

// Get level progress data.
$progress = \local_lernhive_onboarding\tour_manager::get_level_progress($level, $USER->id);

// Build template context.
$categories = [];
$iconmap = [
    'create_users'      => 'user-plus',
    'enrol_users'       => 'users',
    'create_courses'    => 'book-plus',
    'course_settings'   => 'settings',
    'create_activities' => 'plus-square',
    'communication'     => 'message-circle',
];
$colormap = [
    'create_users'      => 'blue',
    'enrol_users'       => 'green',
    'create_courses'    => 'purple',
    'course_settings'   => 'orange',
    'create_activities' => 'teal',
    'communication'     => 'red',
];

foreach ($progress['categories'] as $cat) {
    $p = $cat->progress;
    $status = 'not_started';
    if ($p['done']) {
        $status = 'completed';
    } else if ($p['completed'] > 0) {
        $status = 'in_progress';
    }

    // Get localised name and description.
    $catname = get_string('tourcat_' . $cat->shortname, 'local_lernhive_onboarding');
    $catdesc = get_string('tourcat_' . $cat->shortname . '_desc', 'local_lernhive_onboarding');

    // Build tour list for expanded detail.
    $tours = [];
    foreach ($p['tours'] as $i => $tour) {
        $tours[] = [
            'tourid' => $tour->tourid,
            'num' => $i + 1,
            'completed' => $tour->completed,
            'start_url' => (new moodle_url('/local/lernhive_onboarding/starttour.php', [
                'tourid' => $tour->tourid,
                'sesskey' => sesskey(),
            ]))->out(false),
        ];
    }

    $totalstr = ($p['total'] == 1) ? 'tours_x_of_y_tour' : 'tours_x_of_y_tours';

    $categories[] = [
        'id' => $cat->id,
        'shortname' => $cat->shortname,
        'name' => $catname,
        'description' => $catdesc,
        'icon' => $iconmap[$cat->shortname] ?? 'circle',
        'color' => $colormap[$cat->shortname] ?? 'blue',
        'total' => $p['total'],
        'completed' => $p['completed'],
        'percent' => $p['percent'],
        'done' => $p['done'],
        'status' => $status,
        'status_str' => get_string('tours_status_' . $status, 'local_lernhive_onboarding'),
        'progress_str' => get_string($totalstr, 'local_lernhive_onboarding', (object) [
            'done' => $p['completed'],
            'total' => $p['total'],
        ]),
        'tours' => $tours,
        'has_tours' => !empty($tours),
    ];
}

// Next level info.
$nextlevel = min($level + 1, 5);
$nextlevelname = \local_lernhive\level_manager::get_level_name($nextlevel);
$remaining = $progress['total_tours'] - $progress['completed_tours'];

$templatecontext = [
    'level' => $level,
    'levelname' => $levelname,
    'level_badge' => get_string('tours_level_badge', 'local_lernhive_onboarding', (object) [
        'level' => $level,
        'name' => $levelname,
    ]),
    'intro' => get_string('tours_intro', 'local_lernhive_onboarding'),
    'overall_label' => get_string('tours_overall_progress', 'local_lernhive_onboarding'),
    'overall_percent' => $progress['percent'],
    'overall_text' => get_string('tours_x_of_y', 'local_lernhive_onboarding', (object) [
        'done' => $progress['completed_tours'],
        'total' => $progress['total_tours'],
    ]),
    'categories' => $categories,
    'has_categories' => !empty($categories),
    'all_done' => $progress['done'],
    'can_unlock' => $progress['done'] && $level < 5,
    'at_max_level' => ($level >= 5),
    'unlock_text' => get_string('tours_unlock_text', 'local_lernhive_onboarding', (object) [
        'cats' => $progress['total_categories'],
        'nextlevel' => $nextlevel,
        'nextname' => $nextlevelname,
        'remaining' => $remaining,
    ]),
    'unlock_btn' => get_string('tours_unlock_btn', 'local_lernhive_onboarding', (object) [
        'level' => $nextlevel,
        'name' => $nextlevelname,
    ]),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_lernhive_onboarding/tour_overview', $templatecontext);
echo $OUTPUT->footer();
