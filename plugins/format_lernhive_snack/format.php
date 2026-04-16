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
 * Course rendering for format_lernhive_snack.
 *
 * @package    format_lernhive_snack
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/completionlib.php');

$format = core_courseformat\base::instance($course);
$course = $format->get_course();

course_create_sections_if_missing($course, 0);

$renderer = $format->get_renderer($PAGE);
if (!empty($displaysection)) {
    $format->set_section_number($displaysection);
}

$outputclass = $format->get_output_classname('content');
$widget = new $outputclass($format);

echo $renderer->render($widget);
