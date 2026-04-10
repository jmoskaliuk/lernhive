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
 * Minimal ContentHub entry page for Release 1.
 *
 * @package    local_lernhive_contenthub
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_url(new moodle_url('/local/lernhive_contenthub/index.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_lernhive_contenthub'));
$PAGE->set_heading(get_string('pluginname', 'local_lernhive_contenthub'));

$contenthub = new \local_lernhive_contenthub\output\contenthub();
$renderer = $PAGE->get_renderer('local_lernhive_contenthub');

echo $OUTPUT->header();
echo $renderer->render_contenthub($contenthub);
echo $OUTPUT->footer();

