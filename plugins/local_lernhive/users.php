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
 * LernHive simplified user list.
 *
 * A clean, teacher-friendly user list that replaces /admin/user.php
 * for teachers with the browseusers capability.
 *
 * @package    local_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_login();

$context = context_system::instance();
require_capability('local/lernhive:browseusers', $context);

// Handle actions: suspend / unsuspend / delete (with confirmation).
$action  = optional_param('action', '', PARAM_ALPHA);
$userid  = optional_param('uid', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);
$search  = optional_param('search', '', PARAM_RAW);
$page    = optional_param('page', 0, PARAM_INT);
$perpage = 20;

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/lernhive/users.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('user_list_title', 'local_lernhive'));
$PAGE->set_heading(get_string('user_list_title', 'local_lernhive'));

// Process actions.
if ($action && $userid && confirm_sesskey()) {
    $targetuser = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

    // Prevent actions on admins.
    if (is_siteadmin($targetuser)) {
        throw new moodle_exception('nopermissions', 'error', '', 'modify admin accounts');
    }

    switch ($action) {
        case 'suspend':
            if ($targetuser->suspended) {
                // Unsuspend.
                $targetuser->suspended = 0;
                user_update_user($targetuser, false);
                \core\session\manager::kill_user_sessions($targetuser->id);
            } else {
                // Suspend.
                $targetuser->suspended = 1;
                user_update_user($targetuser, false);
                \core\session\manager::kill_user_sessions($targetuser->id);
            }
            redirect(new moodle_url('/local/lernhive/users.php', ['search' => $search, 'page' => $page]));
            break;

        case 'delete':
            if (!$confirm) {
                // Show confirmation page.
                echo $OUTPUT->header();
                $confirmurl = new moodle_url('/local/lernhive/users.php', [
                    'action' => 'delete',
                    'uid' => $userid,
                    'confirm' => 1,
                    'sesskey' => sesskey(),
                    'search' => $search,
                    'page' => $page,
                ]);
                $cancelurl = new moodle_url('/local/lernhive/users.php', ['search' => $search, 'page' => $page]);
                $message = get_string('user_delete_confirm', 'local_lernhive', fullname($targetuser));
                echo $OUTPUT->confirm($message, $confirmurl, $cancelurl);
                echo $OUTPUT->footer();
                die;
            }
            // Actually delete.
            delete_user($targetuser);
            redirect(new moodle_url('/local/lernhive/users.php', ['search' => $search, 'page' => $page]),
                get_string('user_deleted', 'local_lernhive', fullname($targetuser)));
            break;
    }
}

// Build user query.
$params = [];
$whereclauses = ["u.deleted = 0", "u.id > 1"]; // Exclude guest (id=1).

if (!empty($search)) {
    $search = trim($search);
    $whereclauses[] = "(" . $DB->sql_like('u.firstname', ':s1', false) . " OR "
        . $DB->sql_like('u.lastname', ':s2', false) . " OR "
        . $DB->sql_like('u.email', ':s3', false) . " OR "
        . $DB->sql_like($DB->sql_concat('u.firstname', "' '", 'u.lastname'), ':s4', false)
        . ")";
    $params['s1'] = '%' . $DB->sql_like_escape($search) . '%';
    $params['s2'] = '%' . $DB->sql_like_escape($search) . '%';
    $params['s3'] = '%' . $DB->sql_like_escape($search) . '%';
    $params['s4'] = '%' . $DB->sql_like_escape($search) . '%';
}

$where = implode(' AND ', $whereclauses);
$totalusers = $DB->count_records_sql("SELECT COUNT(*) FROM {user} u WHERE {$where}", $params);
// Use core_user\fields to get all required name + userpic fields.
$userfields = \core_user\fields::for_name()->with_userpic()->including('email', 'lastaccess', 'suspended');
$fieldssql = $userfields->get_sql('u', false, '', '', false);
$users = $DB->get_records_sql(
    "SELECT {$fieldssql->selects}
       FROM {user} u
      WHERE {$where}
      ORDER BY u.lastname ASC, u.firstname ASC",
    array_merge($params, $fieldssql->params),
    $page * $perpage,
    $perpage
);

echo $OUTPUT->header();

// Inline CSS for the simplified user list (Explorer-level styling).
echo '<style>
.lernhive-userlist-search {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
}
.lernhive-userlist-search input[type="text"] {
    flex: 1;
    max-width: 400px;
    border-radius: 20px;
    border: 1px solid #dee2e6;
    padding: 8px 18px;
    font-size: 0.9rem;
    background: #f8f9fa;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.lernhive-userlist-search input[type="text"]:focus {
    border-color: #194866;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(25, 72, 102, 0.12);
    outline: none;
}
.lernhive-userlist-search button {
    border-radius: 20px;
    padding: 8px 20px;
    font-size: 0.9rem;
    font-weight: 500;
}
.lernhive-userlist-count {
    font-size: 0.85rem;
    color: #6c757d;
    margin-bottom: 16px;
}
.lernhive-userlist {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}
.lernhive-userlist thead th {
    background: #f8f9fa;
    font-size: 0.78rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #6c757d;
    padding: 10px 14px;
    border-bottom: 2px solid #dee2e6;
    white-space: nowrap;
}
.lernhive-userlist tbody tr {
    transition: background 0.15s;
}
.lernhive-userlist tbody tr:hover {
    background: #f0f4f8;
}
.lernhive-userlist tbody tr.suspended {
    opacity: 0.55;
}
.lernhive-userlist tbody td {
    padding: 10px 14px;
    border-bottom: 1px solid #eee;
    font-size: 0.9rem;
    vertical-align: middle;
}
.lernhive-userlist .user-name-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}
.lernhive-userlist .user-name-cell img {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
}
.lernhive-userlist .user-name-cell .name {
    font-weight: 600;
    color: #1a1a1a;
}
.lernhive-userlist .user-actions {
    display: flex;
    align-items: center;
    gap: 6px;
}
.lernhive-userlist .user-actions a {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    color: #6c757d;
    text-decoration: none;
    transition: background 0.15s, color 0.15s;
}
.lernhive-userlist .user-actions a:hover {
    background: #e9ecef;
    color: #1a1a1a;
}
.lernhive-userlist .user-actions a.action-info:hover { color: #65a1b3; background: #dfedf1; }
.lernhive-userlist .user-actions a.action-edit:hover { color: #194866; background: #e0eaf2; }
.lernhive-userlist .user-actions a.action-suspend:hover { color: #e67e22; background: #fdf0e0; }
.lernhive-userlist .user-actions a.action-unsuspend:hover { color: #3aadaa; background: #e0f5f4; }
.lernhive-userlist .user-actions a.action-delete:hover { color: #ab1d79; background: #f5e0ed; }
.lernhive-userlist .badge-suspended {
    font-size: 0.72rem;
    background: #fdf0e0;
    color: #e67e22;
    padding: 2px 8px;
    border-radius: 10px;
    font-weight: 600;
    margin-left: 8px;
}
</style>';

// Search form.
$searchurl = new moodle_url('/local/lernhive/users.php');
echo '<form class="lernhive-userlist-search" method="get" action="' . $searchurl->out(false) . '">';
echo '<input type="text" name="search" value="' . s($search) . '" placeholder="'
    . get_string('user_search_placeholder', 'local_lernhive') . '" autocomplete="off" />';
echo '<button type="submit" class="btn btn-primary">'
    . get_string('search') . '</button>';
if (!empty($search)) {
    echo '<a href="' . $searchurl->out(true) . '" class="btn btn-outline-secondary">'
        . get_string('clear') . '</a>';
}
echo '</form>';

// Count.
echo '<div class="lernhive-userlist-count">'
    . get_string('user_list_count', 'local_lernhive', $totalusers) . '</div>';

// Table.
echo '<table class="lernhive-userlist">';
echo '<thead><tr>';
echo '<th>' . get_string('user_col_name', 'local_lernhive') . '</th>';
echo '<th>' . get_string('email') . '</th>';
echo '<th>' . get_string('user_col_lastaccess', 'local_lernhive') . '</th>';
echo '<th>' . get_string('user_col_actions', 'local_lernhive') . '</th>';
echo '</tr></thead>';
echo '<tbody>';

if (empty($users)) {
    echo '<tr><td colspan="4" style="text-align:center; padding:32px; color:#6c757d;">'
        . get_string('user_none_found', 'local_lernhive') . '</td></tr>';
} else {
    foreach ($users as $u) {
        $rowclass = $u->suspended ? ' class="suspended"' : '';
        $userpic = $OUTPUT->user_picture($u, ['size' => 32, 'link' => false]);

        $namehtml = '<div class="user-name-cell">'
            . $userpic
            . '<span class="name">' . s(fullname($u)) . '</span>';
        if ($u->suspended) {
            $namehtml .= '<span class="badge-suspended">'
                . get_string('user_suspended_badge', 'local_lernhive') . '</span>';
        }
        $namehtml .= '</div>';

        // Last access.
        if ($u->lastaccess) {
            $lastaccess = userdate($u->lastaccess, get_string('strftimedatetime', 'langconfig'));
        } else {
            $lastaccess = get_string('never');
        }

        // Action icons (SVG inline).
        $profileurl = (new moodle_url('/user/profile.php', ['id' => $u->id]))->out(true);
        $editurl = (new moodle_url('/user/editadvanced.php', ['id' => $u->id]))->out(true);
        $suspendurl = (new moodle_url('/local/lernhive/users.php', [
            'action' => 'suspend', 'uid' => $u->id, 'sesskey' => sesskey(),
            'search' => $search, 'page' => $page,
        ]))->out(true);
        $deleteurl = (new moodle_url('/local/lernhive/users.php', [
            'action' => 'delete', 'uid' => $u->id, 'sesskey' => sesskey(),
            'search' => $search, 'page' => $page,
        ]))->out(true);

        $svginfo = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>';
        $svgedit = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>';
        $svgsuspend = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="10" y1="15" x2="10" y2="9"/><line x1="14" y1="15" x2="14" y2="9"/></svg>';
        $svgunsuspend = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>';
        $svgdelete = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>';

        $actions = '<div class="user-actions">';
        $actions .= '<a href="' . $profileurl . '" class="action-info" title="'
            . get_string('info') . '">' . $svginfo . '</a>';
        $actions .= '<a href="' . $editurl . '" class="action-edit" title="'
            . get_string('edit') . '">' . $svgedit . '</a>';

        if ($u->suspended) {
            $actions .= '<a href="' . $suspendurl . '" class="action-unsuspend" title="'
                . get_string('user_unsuspend', 'local_lernhive') . '">' . $svgunsuspend . '</a>';
        } else {
            $actions .= '<a href="' . $suspendurl . '" class="action-suspend" title="'
                . get_string('user_suspend', 'local_lernhive') . '">' . $svgsuspend . '</a>';
        }

        $actions .= '<a href="' . $deleteurl . '" class="action-delete" title="'
            . get_string('delete') . '">' . $svgdelete . '</a>';
        $actions .= '</div>';

        echo "<tr{$rowclass}>";
        echo '<td>' . $namehtml . '</td>';
        echo '<td>' . s($u->email) . '</td>';
        echo '<td>' . $lastaccess . '</td>';
        echo '<td>' . $actions . '</td>';
        echo '</tr>';
    }
}

echo '</tbody></table>';

// Pagination.
$baseurl = new moodle_url('/local/lernhive/users.php', ['search' => $search]);
echo $OUTPUT->paging_bar($totalusers, $page, $perpage, $baseurl);

echo $OUTPUT->footer();
