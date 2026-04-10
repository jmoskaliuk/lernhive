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
 * Minimal Create Snack entry page.
 *
 * @package    local_lernhive_discovery
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_url(new moodle_url('/local/lernhive_discovery/createsnack.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('createsnacktitle', 'local_lernhive_discovery'));
$PAGE->set_heading(get_string('createsnacktitle', 'local_lernhive_discovery'));

echo $OUTPUT->header();
echo html_writer::tag('div',
    html_writer::tag('p', get_string('createsnacksummary', 'local_lernhive_discovery')) .
    html_writer::tag('p', get_string('createsnacknote', 'local_lernhive_discovery')),
    ['class' => 'alert alert-info']
);
echo $OUTPUT->footer();

