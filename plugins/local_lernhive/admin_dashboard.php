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
 * LernHive Admin Dashboard — manage teacher levels.
 *
 * @package    local_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_lernhive_dashboard');

$context = context_system::instance();
require_capability('local/lernhive:managelevel', $context);

$page = optional_param('page', 0, PARAM_INT);
$search = optional_param('search', '', PARAM_TEXT);
$action = optional_param('action', '', PARAM_ALPHA);
$perpage = 30;

// Handle level change form submission.
if ($action === 'setlevel' && confirm_sesskey()) {
    $userid = required_param('userid', PARAM_INT);
    $newlevel = required_param('newlevel', PARAM_INT);

    try {
        \local_lernhive\level_manager::set_level($userid, $newlevel, $USER->id);
        $targetuser = $DB->get_record('user', ['id' => $userid]);
        $fullname = fullname($targetuser);
        $levelname = \local_lernhive\level_manager::get_level_name($newlevel);
        \core\notification::success(
            get_string('level_changed_success', 'local_lernhive', [
                'user' => $fullname,
                'level' => $newlevel,
                'name' => $levelname,
            ])
        );
    } catch (\Exception $e) {
        \core\notification::error($e->getMessage());
    }

    redirect(new moodle_url('/local/lernhive/admin_dashboard.php', ['search' => $search, 'page' => $page]));
}

// Get data.
$result = \local_lernhive\level_manager::get_all_teachers($search, $page, $perpage);
$stats = \local_lernhive\level_manager::get_stats();

// Output.
$PAGE->set_title(get_string('admin_dashboard', 'local_lernhive'));
$PAGE->set_heading(get_string('admin_dashboard', 'local_lernhive'));
$PAGE->requires->css('/local/lernhive/styles.css');

echo $OUTPUT->header();

// Statistics cards.
echo '<div class="lernhive-stats-grid">';
$levelicons = [1 => "\xF0\x9F\x8C\xB1", 2 => "\xE2\x9C\x8F\xEF\xB8\x8F", 3 => "\xF0\x9F\x8E\xAF", 4 => "\xF0\x9F\x9A\x80", 5 => "\xF0\x9F\x91\x91"];
$totalteachers = array_sum($stats);
echo '<div class="lernhive-stat-card lernhive-stat-total">';
echo '<div class="lernhive-stat-number">' . $totalteachers . '</div>';
echo '<div class="lernhive-stat-label">' . get_string('total_teachers', 'local_lernhive') . '</div>';
echo '</div>';

for ($i = 1; $i <= 5; $i++) {
    $name = \local_lernhive\level_manager::get_level_name($i);
    $icon = $levelicons[$i];
    $count = $stats[$i] ?? 0;
    echo '<div class="lernhive-stat-card lernhive-stat-level-' . $i . '">';
    echo '<div class="lernhive-stat-number">' . $count . '</div>';
    echo '<div class="lernhive-stat-label">' . $icon . ' ' . $name . '</div>';
    echo '</div>';
}
echo '</div>';

// Search form.
echo '<form method="get" action="" class="lernhive-search-form mb-3 mt-3">';
echo '<div class="input-group">';
echo '<input type="text" name="search" value="' . s($search) . '" class="form-control" placeholder="' .
     get_string('search_placeholder', 'local_lernhive') . '">';
echo '<div class="input-group-append">';
echo '<button type="submit" class="btn btn-primary">' . get_string('search') . '</button>';
if (!empty($search)) {
    echo ' <a href="' . new moodle_url('/local/lernhive/admin_dashboard.php') .
         '" class="btn btn-secondary ml-1">' . get_string('clear') . '</a>';
}
echo '</div></div></form>';

// Teachers table.
if (empty($result['users'])) {
    echo $OUTPUT->notification(get_string('no_teachers_found', 'local_lernhive'), 'info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('fullname'),
        get_string('email'),
        get_string('current_level_short', 'local_lernhive'),
        get_string('lastaccess'),
        get_string('actions'),
    ];
    $table->attributes['class'] = 'generaltable lernhive-dashboard-table';

    foreach ($result['users'] as $teacher) {
        $fullname = fullname($teacher);
        $level = (int) $teacher->lernhive_level;
        $levelname = \local_lernhive\level_manager::get_level_name($level);
        $icon = $levelicons[$level];

        $levelbadge = '<span class="badge lernhive-badge-level-' . $level . '">' .
                      $icon . ' ' . $level . ' — ' . $levelname . '</span>';

        $lastaccess = $teacher->lastaccess
            ? userdate($teacher->lastaccess, get_string('strftimedatetime', 'langconfig'))
            : get_string('never');

        // Level change form.
        $levelform = '<form method="post" action="" class="form-inline lernhive-level-form">';
        $levelform .= '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
        $levelform .= '<input type="hidden" name="action" value="setlevel">';
        $levelform .= '<input type="hidden" name="userid" value="' . $teacher->id . '">';
        $levelform .= '<input type="hidden" name="search" value="' . s($search) . '">';
        $levelform .= '<input type="hidden" name="page" value="' . $page . '">';
        $levelform .= '<select name="newlevel" class="custom-select custom-select-sm mr-1">';
        for ($i = 1; $i <= 5; $i++) {
            $selected = ($i === $level) ? ' selected' : '';
            $lname = \local_lernhive\level_manager::get_level_name($i);
            $levelform .= '<option value="' . $i . '"' . $selected . '>' .
                          $levelicons[$i] . ' ' . $i . ' — ' . $lname . '</option>';
        }
        $levelform .= '</select>';
        $levelform .= '<button type="submit" class="btn btn-sm btn-outline-primary">' .
                       get_string('save_level', 'local_lernhive') . '</button>';
        $levelform .= '</form>';

        $table->data[] = [
            $fullname,
            $teacher->email,
            $levelbadge,
            $lastaccess,
            $levelform,
        ];
    }

    echo html_writer::table($table);

    // Pagination.
    echo $OUTPUT->paging_bar($result['total'], $page, $perpage,
        new moodle_url('/local/lernhive/admin_dashboard.php', ['search' => $search]));
}

echo $OUTPUT->footer();