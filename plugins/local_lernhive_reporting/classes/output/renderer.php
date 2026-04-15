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
 * Plugin renderer for local_lernhive_reporting.
 *
 * @package    local_lernhive_reporting
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_reporting\output;

use plugin_renderer_base;

defined('MOODLE_INTERNAL') || die();

/**
 * Reporting renderer.
 */
class renderer extends plugin_renderer_base {

    /**
     * @param dashboard_page $page
     * @return string
     */
    public function render_dashboard_page(dashboard_page $page): string {
        return $this->render_from_template(
            'local_lernhive_reporting/dashboard_page',
            $page->export_for_template($this)
        );
    }

    /**
     * @param users_page $page
     * @return string
     */
    public function render_users_page(users_page $page): string {
        return $this->render_from_template(
            'local_lernhive_reporting/users_page',
            $page->export_for_template($this)
        );
    }

    /**
     * @param popular_page $page
     * @return string
     */
    public function render_popular_page(popular_page $page): string {
        return $this->render_from_template(
            'local_lernhive_reporting/popular_page',
            $page->export_for_template($this)
        );
    }

    /**
     * @param completion_page $page
     * @return string
     */
    public function render_completion_page(completion_page $page): string {
        return $this->render_from_template(
            'local_lernhive_reporting/completion_page',
            $page->export_for_template($this)
        );
    }
}
