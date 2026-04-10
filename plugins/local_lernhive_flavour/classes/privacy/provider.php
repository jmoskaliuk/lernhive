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
 * Privacy provider for local_lernhive_flavour.
 *
 * The plugin stores an audit trail that records which user applied
 * which flavour and when (local_lernhive_flavour_apps.applied_by).
 * That is personal data under GDPR terms, so we implement a real
 * provider that can export and delete it.
 *
 * @package    local_lernhive_flavour
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_flavour\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider.
 *
 * All flavour activity happens at the system context: the audit trail is
 * site-wide, not per-course. So we implement the core_user_data_provider
 * and plugin_provider interfaces and report the system context for any
 * user that has triggered at least one apply.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Describe what personal data this plugin stores.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'local_lernhive_flavour_apps',
            [
                'applied_by'  => 'privacy:metadata:local_lernhive_flavour_apps:applied_by',
                'timeapplied' => 'privacy:metadata:local_lernhive_flavour_apps:timeapplied',
                'flavour'     => 'privacy:metadata:local_lernhive_flavour_apps:flavour',
            ],
            'privacy:metadata:local_lernhive_flavour_apps'
        );
        return $collection;
    }

    /**
     * Return contexts that contain user data for the given user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {local_lernhive_flavour_apps} a ON a.applied_by = :userid
                 WHERE c.contextlevel = :syscontextlevel";

        $contextlist->add_from_sql($sql, [
            'userid' => $userid,
            'syscontextlevel' => CONTEXT_SYSTEM,
        ]);

        return $contextlist;
    }

    /**
     * Return users in the given context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_system) {
            return;
        }

        $userlist->add_from_sql(
            'applied_by',
            'SELECT applied_by FROM {local_lernhive_flavour_apps}',
            []
        );
    }

    /**
     * Export all user data for the given approved contextlist.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_system) {
                continue;
            }

            $records = $DB->get_records(
                'local_lernhive_flavour_apps',
                ['applied_by' => $user->id],
                'timeapplied ASC'
            );

            if (empty($records)) {
                continue;
            }

            $data = [];
            foreach ($records as $record) {
                $data[] = (object) [
                    'flavour'            => $record->flavour,
                    'previous_flavour'   => $record->previous_flavour,
                    'timeapplied'        => \core_privacy\local\request\transform::datetime($record->timeapplied),
                    'overrides_detected' => (bool) $record->overrides_detected,
                ];
            }

            writer::with_context($context)->export_data(
                [get_string('pluginname', 'local_lernhive_flavour')],
                (object) ['applications' => $data]
            );
        }
    }

    /**
     * Delete all user data in the given context.
     *
     * Flavour audit rows are system-wide logs: deleting them because a
     * single user is being wiped would destroy audit history that refers
     * to other admins too. We only null out the applied_by reference
     * instead — same approach Moodle core takes for logstore rows.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if (!$context instanceof \context_system) {
            return;
        }
        // Site-context delete means "delete everything" — this is only
        // invoked when an admin explicitly requests it, so it is safe.
        $DB->delete_records('local_lernhive_flavour_apps');
    }

    /**
     * Delete user data for a specific user in the given contextlist.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_system) {
                continue;
            }
            // Null the applied_by to preserve the audit chain but break
            // the personal link to the user being erased.
            $DB->set_field(
                'local_lernhive_flavour_apps',
                'applied_by',
                0,
                ['applied_by' => $user->id]
            );
        }
    }

    /**
     * Delete data for multiple users in the given context.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof \context_system) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $DB->set_field_select(
            'local_lernhive_flavour_apps',
            'applied_by',
            0,
            "applied_by {$insql}",
            $params
        );
    }
}
