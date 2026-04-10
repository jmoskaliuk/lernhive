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
 * LernHive simplified course enrolment page.
 *
 * A clean, teacher-friendly enrolment interface for a specific course.
 * Shows enrolled users and allows manual enrolment (search + enrol)
 * and unenrolment.
 *
 * @package    local_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_login();

$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);
require_capability('enrol/manual:enrol', $context);

// Handle actions.
$action  = optional_param('action', '', PARAM_ALPHA);
$userid  = optional_param('uid', 0, PARAM_INT);
$search  = optional_param('search', '', PARAM_RAW);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/lernhive/enrol.php', ['id' => $courseid]));
$PAGE->set_course($course);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('enrol_title', 'local_lernhive', format_string($course->fullname, true)));
$PAGE->set_heading(get_string('enrol_title', 'local_lernhive', format_string($course->fullname, true)));

// Get the manual enrolment plugin instance for this course.
$enrolinstances = enrol_get_instances($courseid, true);
$manualinstance = null;
foreach ($enrolinstances as $inst) {
    if ($inst->enrol === 'manual') {
        $manualinstance = $inst;
        break;
    }
}

// If no manual enrolment, try to enable it.
if (!$manualinstance) {
    $manualplugin = enrol_get_plugin('manual');
    if ($manualplugin) {
        $manualinstance = $manualplugin->add_default_instance($course);
        if ($manualinstance) {
            $manualinstance = $DB->get_record('enrol', ['id' => $manualinstance]);
        }
    }
}

$manualplugin = enrol_get_plugin('manual');

// Process unenrol action.
if ($action === 'unenrol' && $userid && confirm_sesskey()) {
    if ($manualinstance) {
        $manualplugin->unenrol_user($manualinstance, $userid);
    }
    redirect(new moodle_url('/local/lernhive/enrol.php', ['id' => $courseid, 'search' => $search]),
        get_string('enrol_removed', 'local_lernhive'));
}

// Process enrol action.
if ($action === 'enrol' && $userid && confirm_sesskey()) {
    if ($manualinstance) {
        // Get the Student role id.
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $roleid = $studentrole ? $studentrole->id : $manualinstance->roleid;
        $manualplugin->enrol_user($manualinstance, $userid, $roleid);
    }
    redirect(new moodle_url('/local/lernhive/enrol.php', ['id' => $courseid, 'search' => $search]),
        get_string('enrol_added', 'local_lernhive'));
}

// Get enrolled users.
$enrolledusers = get_enrolled_users($context, '', 0, 'u.*', 'u.lastname ASC, u.firstname ASC');

// Search for users to enrol (only when search is active).
$searchresults = [];
if (!empty($search)) {
    $search = trim($search);
    $params = [];
    $whereclauses = ["u.deleted = 0", "u.id > 1", "u.suspended = 0"];

    $whereclauses[] = "(" . $DB->sql_like('u.firstname', ':s1', false) . " OR "
        . $DB->sql_like('u.lastname', ':s2', false) . " OR "
        . $DB->sql_like('u.email', ':s3', false) . " OR "
        . $DB->sql_like($DB->sql_concat('u.firstname', "' '", 'u.lastname'), ':s4', false)
        . ")";
    $params['s1'] = '%' . $DB->sql_like_escape($search) . '%';
    $params['s2'] = '%' . $DB->sql_like_escape($search) . '%';
    $params['s3'] = '%' . $DB->sql_like_escape($search) . '%';
    $params['s4'] = '%' . $DB->sql_like_escape($search) . '%';

    // Exclude already enrolled users.
    $enrolledids = array_keys($enrolledusers);
    if (!empty($enrolledids)) {
        list($insql, $inparams) = $DB->get_in_or_equal($enrolledids, SQL_PARAMS_NAMED, 'eu', false);
        $whereclauses[] = "u.id {$insql}";
        $params = array_merge($params, $inparams);
    }

    $where = implode(' AND ', $whereclauses);
    $userfields = \core_user\fields::for_name()->with_userpic()->including('email');
    $fieldssql = $userfields->get_sql('u', false, '', '', false);
    $searchresults = $DB->get_records_sql(
        "SELECT {$fieldssql->selects}
           FROM {user} u
          WHERE {$where}
          ORDER BY u.lastname ASC, u.firstname ASC",
        array_merge($params, $fieldssql->params),
        0,
        20
    );
}

$courseurl = new moodle_url('/course/view.php', ['id' => $courseid]);

echo $OUTPUT->header();

// Inline CSS — same style as users.php.
echo '<style>
.lh-enrol-back {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 0.85rem; color: #5f6368; text-decoration: none;
    margin-bottom: 20px; transition: color 0.2s;
}
.lh-enrol-back:hover { color: #194866; text-decoration: none; }
.lh-enrol-back svg { width: 16px; height: 16px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
.lh-enrol-section { margin-bottom: 32px; }
.lh-enrol-section-title {
    font-size: 0.9rem; font-weight: 700; color: #1a1a1a;
    text-transform: uppercase; letter-spacing: 0.02em;
    padding-bottom: 10px; border-bottom: 2px solid #e9ecef;
    margin-bottom: 16px;
}
.lh-enrol-search {
    display: flex; align-items: center; gap: 12px; margin-bottom: 16px;
}
.lh-enrol-search input[type="text"] {
    flex: 1; max-width: 400px; border-radius: 20px;
    border: 1px solid #dee2e6; padding: 8px 18px; font-size: 0.9rem;
    background: #f8f9fa; transition: border-color 0.2s, box-shadow 0.2s;
}
.lh-enrol-search input[type="text"]:focus {
    border-color: #194866; background: #fff;
    box-shadow: 0 0 0 3px rgba(25, 72, 102, 0.12); outline: none;
}
.lh-enrol-search button { border-radius: 20px; padding: 8px 20px; font-size: 0.9rem; font-weight: 500; }
.lh-enrol-count { font-size: 0.85rem; color: #6c757d; margin-bottom: 12px; }
.lh-enrol-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.lh-enrol-table thead th {
    background: #f8f9fa; font-size: 0.78rem; font-weight: 600;
    text-transform: uppercase; letter-spacing: 0.04em; color: #6c757d;
    padding: 10px 14px; border-bottom: 2px solid #dee2e6; white-space: nowrap;
}
.lh-enrol-table tbody tr { transition: background 0.15s; }
.lh-enrol-table tbody tr:hover { background: #f0f4f8; }
.lh-enrol-table tbody td {
    padding: 10px 14px; border-bottom: 1px solid #eee;
    font-size: 0.9rem; vertical-align: middle;
}
.lh-enrol-table .user-name-cell {
    display: flex; align-items: center; gap: 10px;
}
.lh-enrol-table .user-name-cell img {
    width: 32px; height: 32px; border-radius: 50%; object-fit: cover;
}
.lh-enrol-table .user-name-cell .name { font-weight: 600; color: #1a1a1a; }
.lh-enrol-action-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 14px; border-radius: 6px; font-size: 0.82rem; font-weight: 600;
    text-decoration: none; transition: all 0.2s; cursor: pointer; border: none;
}
.lh-enrol-action-btn.enrol { background: #e0f5f4; color: #3aadaa; }
.lh-enrol-action-btn.enrol:hover { background: #3aadaa; color: #fff; text-decoration: none; }
.lh-enrol-action-btn.unenrol { background: #fdf0e0; color: #e67e22; }
.lh-enrol-action-btn.unenrol:hover { background: #e67e22; color: #fff; text-decoration: none; }
.lh-enrol-action-btn svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
.lh-enrol-empty { text-align: center; padding: 32px; color: #6c757d; font-size: 0.9rem; }
.lh-enrol-role-badge {
    font-size: 0.72rem; background: #e0eaf2; color: #194866;
    padding: 2px 8px; border-radius: 10px; font-weight: 600;
}
</style>';

// Back link.
echo '<a href="' . $courseurl->out(true) . '" class="lh-enrol-back">'
    . '<svg viewBox="0 0 24 24"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>'
    . get_string('enrol_back_to_course', 'local_lernhive')
    . '</a>';

// ── Section 1: Search & Enrol new users ──
echo '<div class="lh-enrol-section">';
echo '<div class="lh-enrol-section-title">' . get_string('enrol_search_title', 'local_lernhive') . '</div>';

$searchurl = new moodle_url('/local/lernhive/enrol.php', ['id' => $courseid]);
echo '<form class="lh-enrol-search" method="get" action="' . $searchurl->out(false) . '">';
echo '<input type="hidden" name="id" value="' . $courseid . '" />';
echo '<input type="text" name="search" value="' . s($search) . '" placeholder="'
    . get_string('enrol_search_placeholder', 'local_lernhive') . '" autocomplete="off" />';
echo '<button type="submit" class="btn btn-primary">' . get_string('search') . '</button>';
if (!empty($search)) {
    echo '<a href="' . (new moodle_url('/local/lernhive/enrol.php', ['id' => $courseid]))->out(true)
        . '" class="btn btn-outline-secondary">' . get_string('clear') . '</a>';
}
echo '</form>';

if (!empty($search)) {
    if (empty($searchresults)) {
        echo '<div class="lh-enrol-empty">' . get_string('enrol_no_results', 'local_lernhive') . '</div>';
    } else {
        echo '<div class="lh-enrol-count">'
            . get_string('enrol_results_count', 'local_lernhive', count($searchresults)) . '</div>';
        echo '<table class="lh-enrol-table"><thead><tr>';
        echo '<th>' . get_string('user_col_name', 'local_lernhive') . '</th>';
        echo '<th>' . get_string('email') . '</th>';
        echo '<th></th>';
        echo '</tr></thead><tbody>';

        foreach ($searchresults as $u) {
            $userpic = $OUTPUT->user_picture($u, ['size' => 32, 'link' => false]);
            $namehtml = '<div class="user-name-cell">'
                . $userpic
                . '<span class="name">' . s(fullname($u)) . '</span>'
                . '</div>';

            $enrollink = new moodle_url('/local/lernhive/enrol.php', [
                'id' => $courseid,
                'action' => 'enrol',
                'uid' => $u->id,
                'sesskey' => sesskey(),
                'search' => $search,
            ]);

            echo '<tr>';
            echo '<td>' . $namehtml . '</td>';
            echo '<td>' . s($u->email) . '</td>';
            echo '<td><a href="' . $enrollink->out(true) . '" class="lh-enrol-action-btn enrol">'
                . '<svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>'
                . get_string('enrol_btn', 'local_lernhive') . '</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }
}
echo '</div>';

// ── Section 2: Currently enrolled users ──
echo '<div class="lh-enrol-section">';
echo '<div class="lh-enrol-section-title">'
    . get_string('enrol_enrolled_title', 'local_lernhive') . '</div>';
echo '<div class="lh-enrol-count">'
    . get_string('enrol_enrolled_count', 'local_lernhive', count($enrolledusers)) . '</div>';

if (empty($enrolledusers)) {
    echo '<div class="lh-enrol-empty">' . get_string('enrol_none_enrolled', 'local_lernhive') . '</div>';
} else {
    echo '<table class="lh-enrol-table"><thead><tr>';
    echo '<th>' . get_string('user_col_name', 'local_lernhive') . '</th>';
    echo '<th>' . get_string('email') . '</th>';
    echo '<th>' . get_string('role') . '</th>';
    echo '<th></th>';
    echo '</tr></thead><tbody>';

    foreach ($enrolledusers as $u) {
        $userpic = $OUTPUT->user_picture($u, ['size' => 32, 'link' => false]);
        $namehtml = '<div class="user-name-cell">'
            . $userpic
            . '<span class="name">' . s(fullname($u)) . '</span>'
            . '</div>';

        // Get user's role in this course.
        $userroles = get_user_roles($context, $u->id);
        $rolenames = [];
        foreach ($userroles as $r) {
            $rolenames[] = role_get_name($r);
        }
        $rolehtml = !empty($rolenames)
            ? '<span class="lh-enrol-role-badge">' . s(implode(', ', $rolenames)) . '</span>'
            : '-';

        // Unenrol link (don't allow unenrolling yourself or admins).
        $unenrolhtml = '';
        if ($u->id != $USER->id && !is_siteadmin($u->id)) {
            $unenrollink = new moodle_url('/local/lernhive/enrol.php', [
                'id' => $courseid,
                'action' => 'unenrol',
                'uid' => $u->id,
                'sesskey' => sesskey(),
                'search' => $search,
            ]);
            $unenrolhtml = '<a href="' . $unenrollink->out(true) . '" class="lh-enrol-action-btn unenrol"'
                . ' onclick="return confirm(\'' . s(get_string('enrol_confirm_remove', 'local_lernhive')) . '\')">'
                . '<svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="22" y1="11" x2="16" y2="11"/></svg>'
                . get_string('enrol_remove_btn', 'local_lernhive') . '</a>';
        }

        echo '<tr>';
        echo '<td>' . $namehtml . '</td>';
        echo '<td>' . s($u->email) . '</td>';
        echo '<td>' . $rolehtml . '</td>';
        echo '<td>' . $unenrolhtml . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
}
echo '</div>';

echo $OUTPUT->footer();
