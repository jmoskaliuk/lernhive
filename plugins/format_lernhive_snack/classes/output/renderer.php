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
 * Output renderer for format_lernhive_snack.
 *
 * @package    format_lernhive_snack
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_lernhive_snack\output;

use core_courseformat\base as format_base;
use core_courseformat\output\section_renderer;

defined('MOODLE_INTERNAL') || die();

/**
 * Main renderer for the Snack course format.
 */
class renderer extends section_renderer {
    /**
     * Render section title with link (if available).
     *
     * @param stdClass $section
     * @param stdClass $course
     * @return string
     */
    public function section_title($section, $course): string {
        return $this->render(format_base::instance($course)->inplace_editable_render_section_name($section));
    }

    /**
     * Render section title without a link.
     *
     * @param stdClass $section
     * @param stdClass $course
     * @return string
     */
    public function section_title_without_link($section, $course): string {
        return $this->render(format_base::instance($course)->inplace_editable_render_section_name($section, false));
    }
}
