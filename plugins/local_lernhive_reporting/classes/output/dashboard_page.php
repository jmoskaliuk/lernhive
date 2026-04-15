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
 * Renderable for the reporting dashboard.
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
 * Dashboard page renderable.
 */
class dashboard_page implements renderable, templatable {

    /**
     * @param int $courseid Course ID selected in the filter.
     * @param report_service|null $service Injectable service for tests.
     */
    public function __construct(
        private readonly int $courseid = 0,
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
        $coursemap = $selection['courses'];
        $selectedcourseid = $selection['courseid'];

        $courseoptions = [];
        foreach ($coursemap as $id => $name) {
            $courseoptions[] = [
                'value'    => (int)$id,
                'label'    => $name,
                'selected' => ((int)$id === $selectedcourseid),
            ];
        }

        $selectedcoursename = $selectedcourseid > 0
            ? $coursemap[$selectedcourseid]
            : get_string('no_course_available', 'local_lernhive_reporting');

        $usercount = 0;
        $completion = [
            'participants'   => 0,
            'completed'      => 0,
            'pending'        => 0,
            'completionrate' => 0,
        ];
        if ($selectedcourseid > 0) {
            $usercount = $service->get_user_count_for_course($selectedcourseid);
            $completion = $service->get_completion_for_course($selectedcourseid);
        }

        $popularrows = [];
        foreach ($service->get_popular_courses() as $row) {
            $popularrows[] = [
                'courseid'   => $row->courseid,
                'coursename' => $row->coursename,
                'courseurl'  => (new \moodle_url('/course/view.php', ['id' => $row->courseid]))->out(false),
                'usercount'  => $row->usercount,
            ];
        }

        $completionrows = [];
        foreach ($service->get_completion_table() as $row) {
            $completionrows[] = [
                'courseid'       => $row->courseid,
                'coursename'     => $row->coursename,
                'courseurl'      => (new \moodle_url('/course/view.php', ['id' => $row->courseid]))->out(false),
                'participants'   => $row->participants,
                'completed'      => $row->completed,
                'completionrate' => $row->completionrate,
                'completiontext' => $row->completionrate . '%',
            ];
        }

        $toppopular = $popularrows[0] ?? null;
        $usersisempty = ($selectedcourseid > 0 && $usercount === 0);
        $completionisempty = ($selectedcourseid > 0 && $completion['participants'] === 0);
        $completionnotstarted = ($selectedcourseid > 0 && $completion['participants'] > 0 && $completion['completed'] === 0);

        return [
            'hascourses'                => !empty($coursemap),
            'haspopularrows'            => !empty($popularrows),
            'hascompletionrows'         => !empty($completionrows),
            'hasselectedcourse'         => ($selectedcourseid > 0),
            'usersisempty'              => $usersisempty,
            'completionisempty'         => $completionisempty,
            'completionnotstarted'      => $completionnotstarted,
            'selecturl'                 => (new \moodle_url('/local/lernhive_reporting/index.php'))->out(false),
            'usersdrilldownurl'         => (new \moodle_url('/local/lernhive_reporting/users.php', ['courseid' => $selectedcourseid]))->out(false),
            'populardrilldownurl'       => (new \moodle_url('/local/lernhive_reporting/popular.php'))->out(false),
            'completiondrilldownurl'    => (new \moodle_url('/local/lernhive_reporting/completion.php', ['courseid' => $selectedcourseid]))->out(false),
            'courseoptions'             => $courseoptions,
            'selectedcoursename'        => $selectedcoursename,
            'usercount'                 => $usercount,
            'toppopularcoursename'      => $toppopular['coursename'] ?? get_string('no_data', 'local_lernhive_reporting'),
            'toppopularcourseusers'     => $toppopular['usercount'] ?? 0,
            'selectedcompletionrate'    => $completion['completionrate'],
            'selectedcompletionratetext' => $completion['completionrate'] . '%',
            'selectedparticipants'      => $completion['participants'],
            'selectedcompleted'         => $completion['completed'],
            'selectedpending'           => $completion['pending'],
            'popularrows'               => $popularrows,
            'completionrows'            => $completionrows,
        ];
    }
}
