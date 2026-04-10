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

namespace local_lernhive;

defined('MOODLE_INTERNAL') || die();

/**
 * Manages LernHive levels for users.
 *
 * @package    local_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class level_manager {

    /** @var int Minimum level */
    const LEVEL_MIN = 1;

    /** @var int Maximum level */
    const LEVEL_MAX = 5;

    /** @var array Level names (English-first, matching canonical LernHive level identifiers) */
    const LEVEL_NAMES = [
        1 => 'explorer',
        2 => 'creator',
        3 => 'pro',
        4 => 'expert',
        5 => 'master',
    ];

    /**
     * Get the current level for a user.
     *
     * @param int $userid The user ID.
     * @return int The user's level (defaults to 1).
     */
    public static function get_level(int $userid): int {
        global $DB;

        try {
            $record = $DB->get_record('local_lernhive_levels', ['userid' => $userid]);
            if ($record) {
                return (int) $record->level;
            }
        } catch (\dml_exception $e) {
            // Table may not exist yet (e.g. during install/upgrade).
            return self::LEVEL_MIN;
        }
        return self::LEVEL_MIN;
    }

    /**
     * Get the level record for a user, or null if none exists.
     *
     * @param int $userid The user ID.
     * @return object|null The database record.
     */
    public static function get_level_record(int $userid): ?object {
        global $DB;
        try {
            $record = $DB->get_record('local_lernhive_levels', ['userid' => $userid]);
            return $record ?: null;
        } catch (\dml_exception $e) {
            // Table may not exist yet (e.g. during install/upgrade).
            return null;
        }
    }

    /**
     * Set the level for a user.
     *
     * @param int $userid The user ID.
     * @param int $level The new level (1-5).
     * @param int|null $updatedby The admin user ID who made the change.
     * @return bool True on success.
     * @throws \invalid_parameter_exception If level is out of range.
     */
    public static function set_level(int $userid, int $level, ?int $updatedby = null): bool {
        global $DB;

        if ($level < self::LEVEL_MIN || $level > self::LEVEL_MAX) {
            throw new \invalid_parameter_exception(
                "Level must be between " . self::LEVEL_MIN . " and " . self::LEVEL_MAX
            );
        }

        $now = time();
        $record = $DB->get_record('local_lernhive_levels', ['userid' => $userid]);

        if ($record) {
            $record->level = $level;
            $record->updated_by = $updatedby;
            $record->timemodified = $now;
            $DB->update_record('local_lernhive_levels', $record);
        } else {
            $record = new \stdClass();
            $record->userid = $userid;
            $record->level = $level;
            $record->updated_by = $updatedby;
            $record->timemodified = $now;
            $record->timecreated = $now;
            $DB->insert_record('local_lernhive_levels', $record);
        }

        // Apply capability changes for the new level.
        capability_mapper::apply_level($userid, $level);

        // Trigger event.
        // objectid is required because the event defines objecttable.
        // Fetch the record id from the levels table.
        $levelrecord = $DB->get_record('local_lernhive_levels', ['userid' => $userid]);
        $event = \local_lernhive\event\level_changed::create([
            'context' => \context_system::instance(),
            'objectid' => $levelrecord ? $levelrecord->id : 0,
            'userid' => $updatedby ?? $userid,
            'relateduserid' => $userid,
            'other' => ['level' => $level],
        ]);
        $event->trigger();

        return true;
    }

    /**
     * Get all teachers with their LernHive levels.
     *
     * @param string $search Optional search string for user name/email.
     * @param int $page Page number (0-based).
     * @param int $perpage Items per page.
     * @return array ['users' => array, 'total' => int]
     */
    public static function get_all_teachers(string $search = '', int $page = 0, int $perpage = 50): array {
        global $DB;

        // Get users who have the editing teacher role anywhere.
        $editingteacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
        if (!$editingteacherrole) {
            return ['users' => [], 'total' => 0];
        }

        $params = ['roleid' => $editingteacherrole->id];
        $searchsql = '';

        if (!empty($search)) {
            $searchsql = " AND (" . $DB->sql_like('u.firstname', ':search1', false) .
                         " OR " . $DB->sql_like('u.lastname', ':search2', false) .
                         " OR " . $DB->sql_like('u.email', ':search3', false) . ")";
            $params['search1'] = '%' . $DB->sql_like_escape($search) . '%';
            $params['search2'] = '%' . $DB->sql_like_escape($search) . '%';
            $params['search3'] = '%' . $DB->sql_like_escape($search) . '%';
        }

        $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email, u.lastaccess,
                       u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename,
                       COALESCE(sl.level, 1) AS lernhive_level,
                       sl.timemodified AS level_changed
                  FROM {user} u
                  JOIN {role_assignments} ra ON ra.userid = u.id
             LEFT JOIN {local_lernhive_levels} sl ON sl.userid = u.id
                 WHERE ra.roleid = :roleid
                   AND u.deleted = 0
                   AND u.suspended = 0
                   {$searchsql}
              ORDER BY u.lastname, u.firstname";

        $countsql = "SELECT COUNT(DISTINCT u.id)
                       FROM {user} u
                       JOIN {role_assignments} ra ON ra.userid = u.id
                  LEFT JOIN {local_lernhive_levels} sl ON sl.userid = u.id
                      WHERE ra.roleid = :roleid
                        AND u.deleted = 0
                        AND u.suspended = 0
                        {$searchsql}";

        $total = $DB->count_records_sql($countsql, $params);
        $users = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

        return ['users' => array_values($users), 'total' => $total];
    }

    /**
     * Get the localized name for a level.
     *
     * @param int $level The level number.
     * @return string The level name.
     */
    public static function get_level_name(int $level): string {
        $key = self::LEVEL_NAMES[$level] ?? 'explorer';
        return get_string('level_' . $key, 'local_lernhive');
    }

    /**
     * Get level statistics.
     *
     * @return array Counts per level.
     */
    public static function get_stats(): array {
        global $DB;

        $stats = [];
        for ($i = self::LEVEL_MIN; $i <= self::LEVEL_MAX; $i++) {
            $stats[$i] = 0;
        }

        try {
            $records = $DB->get_records_sql(
                "SELECT level, COUNT(*) AS cnt FROM {local_lernhive_levels} GROUP BY level"
            );
            foreach ($records as $record) {
                $stats[(int) $record->level] = (int) $record->cnt;
            }
        } catch (\dml_exception $e) {
            // Table may not exist yet.
        }

        return $stats;
    }
}
