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
 * Format base class for LernHive Snack.
 *
 * @package    format_lernhive_snack
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Course format base class.
 */
class format_lernhive_snack extends core_courseformat\base {
    /**
     * Snack courses use sections.
     *
     * @return bool
     */
    public function uses_sections() {
        return true;
    }

    /**
     * Snack format keeps section indentation disabled.
     *
     * @return bool
     */
    public function uses_indentation(): bool {
        return false;
    }

    /**
     * Snack courses should be visible in the course index.
     *
     * @return bool
     */
    public function uses_course_index() {
        return true;
    }

    /**
     * Declare AJAX capabilities.
     *
     * @return stdClass
     */
    public function supports_ajax() {
        $ajaxsupport = new stdClass();
        $ajaxsupport->capable = true;

        return $ajaxsupport;
    }

    /**
     * Allow core activity/component rendering support.
     *
     * @return bool
     */
    public function supports_components() {
        return true;
    }
}
