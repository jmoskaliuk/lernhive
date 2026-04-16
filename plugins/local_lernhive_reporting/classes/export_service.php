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
 * CSV export service for reporting drilldowns.
 *
 * @package    local_lernhive_reporting
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_reporting;

defined('MOODLE_INTERNAL') || die();

/**
 * Builds and streams CSV exports for R1 drilldown reports.
 */
class export_service {

    /**
     * @param report_service|null $service Injectable service for tests.
     */
    public function __construct(private readonly ?report_service $service = null) {
    }

    /**
     * Download users-in-course drilldown as CSV.
     *
     * @param int $requestedcourseid Requested course id from URL.
     * @return never
     */
    public function download_users_csv(int $requestedcourseid): never {
        $service = $this->service ?? new report_service();
        $selection = $service->resolve_selected_course($requestedcourseid);
        $courseid = $selection['courseid'];

        $rows = [];
        if ($courseid > 0) {
            foreach ($service->get_course_participants($courseid) as $participant) {
                $rows[] = [
                    fullname($participant),
                    $participant->email,
                    $participant->lastaccess > 0
                        ? userdate($participant->lastaccess, get_string('strftimedatetime', 'langconfig'))
                        : get_string('never_accessed', 'local_lernhive_reporting'),
                ];
            }
        }

        $this->download_csv(
            'lernhive-report-users',
            [
                get_string('name_label', 'local_lernhive_reporting'),
                get_string('email_label', 'local_lernhive_reporting'),
                get_string('lastaccess_label', 'local_lernhive_reporting'),
            ],
            $rows
        );
    }

    /**
     * Download popular-courses drilldown as CSV.
     *
     * @return never
     */
    public function download_popular_csv(): never {
        $service = $this->service ?? new report_service();

        $rows = [];
        foreach ($service->get_popular_courses(25) as $row) {
            $rows[] = [
                $row->coursename,
                $row->usercount,
            ];
        }

        $this->download_csv(
            'lernhive-report-popular',
            [
                get_string('course_label', 'local_lernhive_reporting'),
                get_string('participants_label', 'local_lernhive_reporting'),
            ],
            $rows
        );
    }

    /**
     * Download completion drilldown as CSV.
     *
     * @return never
     */
    public function download_completion_csv(): never {
        $service = $this->service ?? new report_service();

        $rows = [];
        foreach ($service->get_completion_table(25) as $row) {
            $pending = max(0, (int)$row->participants - (int)$row->completed);
            $rows[] = [
                $row->coursename,
                $row->participants,
                $row->completed,
                $pending,
                $row->completionrate . '%',
            ];
        }

        $this->download_csv(
            'lernhive-report-completion',
            [
                get_string('course_label', 'local_lernhive_reporting'),
                get_string('participants_label', 'local_lernhive_reporting'),
                get_string('completed_label', 'local_lernhive_reporting'),
                get_string('pending_label', 'local_lernhive_reporting'),
                get_string('completion_rate_label', 'local_lernhive_reporting'),
            ],
            $rows
        );
    }

    /**
     * Stream CSV response and stop script execution.
     *
     * @param string $prefix Filename prefix.
     * @param array<int, string> $header Header cells.
     * @param array<int, array<int, string|int>> $rows Data rows.
     * @return never
     */
    private function download_csv(string $prefix, array $header, array $rows): never {
        global $CFG;

        require_once($CFG->libdir . '/csvlib.class.php');

        $writer = new \csv_export_writer();
        $writer->set_filename($prefix . '-' . gmdate('Ymd-His'));
        $writer->add_data($header);
        foreach ($rows as $row) {
            $writer->add_data($row);
        }
        $writer->download_file();
        exit;
    }
}
