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
 * Reporting data service for Release 1 tiles.
 *
 * @package    local_lernhive_reporting
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_reporting;

defined('MOODLE_INTERNAL') || die();

/**
 * Thin service layer that reads reporting KPIs from Moodle core tables.
 *
 * Release 1 intentionally stays simple: no custom reporting schema, no
 * background aggregation, no external data source.
 */
class report_service {

    /**
     * Return selectable courses for the current user.
     *
     * Visibility logic (R1):
     * - global reporting users (siteadmin or moodle/site:viewreports) can see all courses
     * - others only see enrolled courses where they hold a reporting-relevant course capability
     *
     * @param int $limit Maximum courses for the global-access fallback.
     * @return array<int, string>
     */
    public function get_selectable_courses(int $limit = 200): array {
        global $DB, $USER;

        $userid = (int)$USER->id;
        $isglobalreporter = $this->user_has_global_reporting_access($userid);
        $options = [];

        if ($isglobalreporter) {
            $records = $DB->get_records_select(
                'course',
                'id <> :siteid',
                ['siteid' => SITEID],
                'fullname ASC',
                'id, fullname',
                0,
                $limit,
            );
            foreach ($records as $record) {
                $courseid = (int)$record->id;
                $options[$courseid] = $this->format_course_name($courseid, $record->fullname);
            }
            return $options;
        }

        $enrolledcourses = enrol_get_all_users_courses($userid, true, 'id, fullname', 'fullname ASC');
        foreach ($enrolledcourses as $course) {
            $courseid = (int)$course->id;
            if (!$this->user_can_access_course($courseid, $userid)) {
                continue;
            }
            $options[$courseid] = $this->format_course_name($courseid, $course->fullname);
        }

        if (!empty($options)) {
            return $options;
        }

        // Fallback for users who are not enrolled but still hold course-level capabilities.
        $records = $DB->get_records_select(
            'course',
            'id <> :siteid',
            ['siteid' => SITEID],
            'fullname ASC',
            'id, fullname',
            0,
            $limit,
        );

        foreach ($records as $record) {
            $courseid = (int)$record->id;
            if (!$this->user_can_access_course($courseid, $userid)) {
                continue;
            }
            $options[$courseid] = $this->format_course_name($courseid, $record->fullname);
        }

        return $options;
    }

    /**
     * Resolve selected course id against currently visible courses.
     *
     * @param int $requestedcourseid
     * @return array{courseid:int,courses:array<int,string>}
     */
    public function resolve_selected_course(int $requestedcourseid): array {
        $courses = $this->get_selectable_courses();
        if (empty($courses)) {
            return [
                'courseid' => 0,
                'courses' => [],
            ];
        }

        if (array_key_exists($requestedcourseid, $courses)) {
            return [
                'courseid' => $requestedcourseid,
                'courses' => $courses,
            ];
        }

        return [
            'courseid' => (int)array_key_first($courses),
            'courses' => $courses,
        ];
    }

    /**
     * Whether a user has global reporting access.
     *
     * @param int $userid
     * @return bool
     */
    public function user_has_global_reporting_access(int $userid): bool {
        if (is_siteadmin($userid)) {
            return true;
        }

        return has_capability('moodle/site:viewreports', \core\context\system::instance(), $userid);
    }

    /**
     * Whether a user may report on a specific course.
     *
     * @param int $courseid
     * @param int|null $userid Defaults to current user.
     * @return bool
     */
    public function user_can_access_course(int $courseid, ?int $userid = null): bool {
        global $USER;

        if ($courseid <= SITEID) {
            return false;
        }

        $userid = $userid ?? (int)$USER->id;

        if ($this->user_has_global_reporting_access($userid)) {
            return true;
        }

        $context = \core\context\course::instance($courseid, IGNORE_MISSING);
        if (!$context) {
            return false;
        }

        $caps = [
            'moodle/course:update',
            'moodle/course:viewparticipants',
            'report/completion:view',
        ];

        foreach ($caps as $cap) {
            if (has_capability($cap, $context, $userid)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Count active enrolled users in a course.
     *
     * @param int $courseid
     * @return int
     */
    public function get_user_count_for_course(int $courseid): int {
        global $DB;

        if ($courseid <= 0) {
            return 0;
        }

        $sql = "SELECT COUNT(DISTINCT u.id)
                  FROM {enrol} e
                  JOIN {user_enrolments} ue
                    ON ue.enrolid = e.id
                   AND ue.status = :ueenabled
                  JOIN {user} u
                    ON u.id = ue.userid
                   AND u.deleted = :notdeleted
                   AND u.suspended = :notsuspended
                 WHERE e.courseid = :courseid
                   AND e.status = :enrolenabled";

        return (int)$DB->count_records_sql($sql, [
            'ueenabled'    => 0,
            'notdeleted'   => 0,
            'notsuspended' => 0,
            'courseid'     => $courseid,
            'enrolenabled' => 0,
        ]);
    }

    /**
     * Participant rows for one course.
     *
     * @param int $courseid
     * @param int $limit
     * @return array<int, \stdClass>
     */
    public function get_course_participants(int $courseid, int $limit = 200): array {
        global $DB;

        if ($courseid <= 0) {
            return [];
        }

        $sql = "SELECT DISTINCT u.id,
                                u.firstname,
                                u.lastname,
                                u.firstnamephonetic,
                                u.lastnamephonetic,
                                u.middlename,
                                u.alternatename,
                                u.email,
                                u.lastaccess
                  FROM {enrol} e
                  JOIN {user_enrolments} ue
                    ON ue.enrolid = e.id
                   AND ue.status = :ueenabled
                  JOIN {user} u
                    ON u.id = ue.userid
                   AND u.deleted = :notdeleted
                   AND u.suspended = :notsuspended
                 WHERE e.courseid = :courseid
                   AND e.status = :enrolenabled
              ORDER BY u.lastname ASC, u.firstname ASC";

        return array_values($DB->get_records_sql($sql, [
            'ueenabled'    => 0,
            'notdeleted'   => 0,
            'notsuspended' => 0,
            'courseid'     => $courseid,
            'enrolenabled' => 0,
        ], 0, $limit));
    }

    /**
     * Get top courses by active participants.
     *
     * @param int $limit
     * @return array<int, \stdClass>
     */
    public function get_popular_courses(int $limit = 5): array {
        global $DB;

        $sql = "SELECT c.id AS courseid,
                       c.fullname AS coursename,
                       COUNT(DISTINCT u.id) AS usercount
                  FROM {course} c
             LEFT JOIN {enrol} e
                    ON e.courseid = c.id
                   AND e.status = :enrolenabled
             LEFT JOIN {user_enrolments} ue
                    ON ue.enrolid = e.id
                   AND ue.status = :ueenabled
             LEFT JOIN {user} u
                    ON u.id = ue.userid
                   AND u.deleted = :notdeleted
                   AND u.suspended = :notsuspended
                 WHERE c.id <> :siteid
              GROUP BY c.id, c.fullname
              ORDER BY usercount DESC, c.fullname ASC";

        $records = $DB->get_records_sql($sql, [
            'enrolenabled' => 0,
            'ueenabled'    => 0,
            'notdeleted'   => 0,
            'notsuspended' => 0,
            'siteid'       => SITEID,
        ], 0, $limit);

        return array_map(function(\stdClass $record): \stdClass {
            $record->courseid = (int)$record->courseid;
            $record->usercount = (int)$record->usercount;
            $record->coursename = $this->format_course_name($record->courseid, $record->coursename);
            return $record;
        }, array_values($records));
    }

    /**
     * Completion overview for one course.
     *
     * @param int $courseid
     * @return array{participants:int,completed:int,pending:int,completionrate:int}
     */
    public function get_completion_for_course(int $courseid): array {
        global $DB;

        $participants = $this->get_user_count_for_course($courseid);
        if ($participants <= 0) {
            return [
                'participants'   => 0,
                'completed'      => 0,
                'pending'        => 0,
                'completionrate' => 0,
            ];
        }

        $sql = "SELECT COUNT(DISTINCT cc.userid)
                  FROM {course_completions} cc
                  JOIN {user} u
                    ON u.id = cc.userid
                   AND u.deleted = :notdeleted
                   AND u.suspended = :notsuspended
                  JOIN {enrol} e
                    ON e.courseid = cc.course
                   AND e.status = :enrolenabled
                  JOIN {user_enrolments} ue
                    ON ue.enrolid = e.id
                   AND ue.userid = cc.userid
                   AND ue.status = :ueenabled
                 WHERE cc.course = :courseid
                   AND cc.timecompleted IS NOT NULL";

        $completed = (int)$DB->count_records_sql($sql, [
            'notdeleted'   => 0,
            'notsuspended' => 0,
            'enrolenabled' => 0,
            'ueenabled'    => 0,
            'courseid'     => $courseid,
        ]);

        $completed = min($completed, $participants);
        $pending = max(0, $participants - $completed);

        return [
            'participants'   => $participants,
            'completed'      => $completed,
            'pending'        => $pending,
            'completionrate' => (int)round(($completed / $participants) * 100),
        ];
    }

    /**
     * Completion drilldown rows for top courses with participants.
     *
     * @param int $limit
     * @return array<int, \stdClass>
     */
    public function get_completion_table(int $limit = 5): array {
        global $DB;

        $sql = "SELECT c.id AS courseid,
                       c.fullname AS coursename,
                       COUNT(DISTINCT u.id) AS participants,
                       COUNT(DISTINCT CASE WHEN cc.timecompleted IS NOT NULL THEN cc.userid ELSE NULL END) AS completed
                  FROM {course} c
             LEFT JOIN {enrol} e
                    ON e.courseid = c.id
                   AND e.status = :enrolenabled
             LEFT JOIN {user_enrolments} ue
                    ON ue.enrolid = e.id
                   AND ue.status = :ueenabled
             LEFT JOIN {user} u
                    ON u.id = ue.userid
                   AND u.deleted = :notdeleted
                   AND u.suspended = :notsuspended
             LEFT JOIN {course_completions} cc
                    ON cc.course = c.id
                   AND cc.userid = u.id
                 WHERE c.id <> :siteid
              GROUP BY c.id, c.fullname
                HAVING COUNT(DISTINCT u.id) > 0
              ORDER BY completed DESC, participants DESC, c.fullname ASC";

        $records = $DB->get_records_sql($sql, [
            'enrolenabled' => 0,
            'ueenabled'    => 0,
            'notdeleted'   => 0,
            'notsuspended' => 0,
            'siteid'       => SITEID,
        ], 0, $limit);

        $result = [];
        foreach ($records as $record) {
            $participants = (int)$record->participants;
            $completed = min((int)$record->completed, $participants);
            $record->courseid = (int)$record->courseid;
            $record->coursename = $this->format_course_name($record->courseid, $record->coursename);
            $record->participants = $participants;
            $record->completed = $completed;
            $record->completionrate = $participants > 0 ? (int)round(($completed / $participants) * 100) : 0;
            $result[] = $record;
        }

        return $result;
    }

    /**
     * Format a course name safely in course context.
     *
     * @param int $courseid
     * @param string $fullname
     * @return string
     */
    private function format_course_name(int $courseid, string $fullname): string {
        return format_string($fullname, true, [
            'context' => \core\context\course::instance($courseid),
        ]);
    }
}
