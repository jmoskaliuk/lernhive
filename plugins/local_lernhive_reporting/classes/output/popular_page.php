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
 * Renderable for the popular-courses report page.
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
 * Popular courses drilldown page renderable.
 */
class popular_page implements renderable, templatable {

    /**
     * @param report_service|null $service Injectable service for tests.
     */
    public function __construct(private readonly ?report_service $service = null) {
    }

    /**
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $service = $this->service ?? new report_service();

        $rows = [];
        foreach ($service->get_popular_courses(25) as $row) {
            $rows[] = [
                'coursename' => $row->coursename,
                'courseurl' => (new \moodle_url('/course/view.php', ['id' => $row->courseid]))->out(false),
                'usercount' => $row->usercount,
            ];
        }

        return [
            'hasrows' => !empty($rows),
            'rows' => $rows,
            'backurl' => (new \moodle_url('/local/lernhive_reporting/index.php'))->out(false),
        ];
    }
}
