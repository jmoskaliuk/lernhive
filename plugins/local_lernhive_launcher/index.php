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
 * Minimal launcher inspection page for Release 1 implementation.
 *
 * @package    local_lernhive_launcher
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_url(new moodle_url('/local/lernhive_launcher/index.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_lernhive_launcher'));
$PAGE->set_heading(get_string('pluginname', 'local_lernhive_launcher'));

$actions = \local_lernhive_launcher\action_provider::get_visible_actions();
$launcher = new \local_lernhive_launcher\output\launcher($actions);
$renderer = $PAGE->get_renderer('local_lernhive_launcher');

echo $OUTPUT->header();
echo $renderer->render_launcher($launcher);
echo $OUTPUT->footer();
