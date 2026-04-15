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
 * Completion drilldown report.
 *
 * @package    local_lernhive_reporting
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_lernhive_reporting\output\completion_page;

$context = \core\context\system::instance();
$courseid = optional_param('courseid', 0, PARAM_INT);

require_login();
require_capability('local/lernhive_reporting:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/lernhive_reporting/completion.php', ['courseid' => $courseid]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('tile_completion_title', 'local_lernhive_reporting'));
$PAGE->set_heading(get_string('pluginname', 'local_lernhive_reporting'));
$PAGE->requires->css('/local/lernhive_reporting/styles.css');

/** @var \local_lernhive_reporting\output\renderer $renderer */
$renderer = $PAGE->get_renderer('local_lernhive_reporting');

echo $OUTPUT->header();
echo $renderer->render_completion_page(new completion_page($courseid));
echo $OUTPUT->footer();
