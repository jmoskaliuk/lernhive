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

namespace local_lernhive\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for LernHive.
 *
 * @package    local_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_lernhive_levels', [
            'userid' => 'privacy:metadata:local_lernhive_levels:userid',
            'level' => 'privacy:metadata:local_lernhive_levels:level',
            'updated_by' => 'privacy:metadata:local_lernhive_levels:updated_by',
        ], 'privacy:metadata:local_lernhive_levels');

        return $collection;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT ctx.id
                  FROM {local_lernhive_levels} sl
                  JOIN {context} ctx ON ctx.instanceid = 0 AND ctx.contextlevel = :contextlevel
                 WHERE sl.userid = :userid";
        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_SYSTEM,
            'userid' => $userid,
        ]);
        return $contextlist;
    }

    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }
        $sql = "SELECT userid FROM {local_lernhive_levels}";
        $userlist->add_from_sql('userid', $sql, []);
    }

    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        $record = $DB->get_record('local_lernhive_levels', ['userid' => $userid]);
        if ($record) {
            writer::with_context(\context_system::instance())->export_data(
                [get_string('pluginname', 'local_lernhive')],
                (object) [
                    'level' => $record->level,
                    'timemodified' => transform::datetime($record->timemodified),
                    'timecreated' => transform::datetime($record->timecreated),
                ]
            );
        }
    }

    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if ($context->contextlevel === CONTEXT_SYSTEM) {
            $DB->delete_records('local_lernhive_levels');
        }
    }

    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        $DB->delete_records('local_lernhive_levels', ['userid' => $userid]);
    }

    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }
        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }
        list($insql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $DB->delete_records_select('local_lernhive_levels', "userid {$insql}", $inparams);
    }
}
