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
 * Renderable for the users-in-course report page.
 *
 * @package    local_lernhive_reporting
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_reporting\output;

use local_lernhive_reporting\report_service;
use renderable;
use renderer_base;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 * Users drilldown page renderable.
 */
class users_page implements renderable, templatable {

    /**
     * @param int $courseid Requested course id.
     * @param report_service|null $service Injectable service for tests.
     */
    public function __construct(
        private readonly int $courseid,
        private readonly ?report_service $service = null,
    ) {
    }

    /**
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $service = $this->service ?? new report_service();
        $selection = $service->resolve_selected_course($this->courseid);
        $selectedcourseid = $selection['courseid'];
        $coursemap = $selection['courses'];

        $courseoptions = [];
        foreach ($coursemap as $id => $name) {
            $courseoptions[] = [
                'value' => (int)$id,
                'label' => $name,
                'selected' => ((int)$id === $selectedcourseid),
            ];
        }

        $rows = [];
        if ($selectedcourseid > 0) {
            foreach ($service->get_course_participants($selectedcourseid) as $participant) {
                $rows[] = [
                    'fullname' => fullname($participant),
                    'email' => s($participant->email),
                    'lastaccess' => $participant->lastaccess > 0
                        ? userdate($participant->lastaccess, get_string('strftimedatetime', 'langconfig'))
                        : get_string('never_accessed', 'local_lernhive_reporting'),
                    'profileurl' => (new \moodle_url('/user/profile.php', ['id' => $participant->id]))->out(false),
                ];
            }
        }

        return [
            'hascourses' => !empty($coursemap),
            'hasrows' => !empty($rows),
            'courseoptions' => $courseoptions,
            'rows' => $rows,
            'selectedcoursename' => $selectedcourseid > 0
                ? $coursemap[$selectedcourseid]
                : get_string('no_course_available', 'local_lernhive_reporting'),
            'backurl' => (new \moodle_url('/local/lernhive_reporting/index.php', ['courseid' => $selectedcourseid]))->out(false),
            'selecturl' => (new \moodle_url('/local/lernhive_reporting/users.php'))->out(false),
        ];
    }
}
