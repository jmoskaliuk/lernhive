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
 * Quick-generate test data from a predefined template.
 *
 * @package    local_testdata
 * @copyright  2024 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_testdata\generator;

admin_externalpage_setup('local_testdata');
require_capability('local/testdata:manage', \core\context\system::instance());
require_sesskey();

$template = required_param('template', PARAM_ALPHANUMEXT);
$configpath = __DIR__ . '/configs/' . $template . '.json';

if (!file_exists($configpath)) {
    redirect(new moodle_url('/local/testdata/index.php'),
        get_string('gen_error_template_not_found', 'local_testdata'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

$config = json_decode(file_get_contents($configpath), true);
if (empty($config)) {
    redirect(new moodle_url('/local/testdata/index.php'),
        get_string('gen_error_invalid_json', 'local_testdata'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

// Use template name + timestamp as dataset name to avoid conflicts.
$datasetname = $template . '_' . date('His');
$config['dataset'] = $datasetname;

$logmessages = [];
$gen = new generator($datasetname, function ($msg) use (&$logmessages) {
    $logmessages[] = $msg;
});

$success = $gen->run_config($config);

// Show results page.
$PAGE->set_url(new moodle_url('/local/testdata/quickgen.php', ['template' => $template]));
$PAGE->set_title(get_string('gen_results_title', 'local_testdata'));
$PAGE->set_heading(get_string('gen_results_title', 'local_testdata'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('gen_results_title', 'local_testdata'));

if ($success) {
    echo $OUTPUT->notification(
        get_string('gen_results_success', 'local_testdata', $datasetname),
        \core\output\notification::NOTIFY_SUCCESS
    );
} else {
    echo $OUTPUT->notification(
        get_string('gen_results_error', 'local_testdata'),
        \core\output\notification::NOTIFY_ERROR
    );
}

// Log output.
if (!empty($logmessages)) {
    echo html_writer::start_tag('div', ['class' => 'card mb-3']);
    echo html_writer::start_tag('div', ['class' => 'card-body']);
    echo html_writer::tag('h5', get_string('gen_results_log', 'local_testdata'), ['class' => 'card-title']);
    echo html_writer::start_tag('pre', ['class' => 'bg-light p-3 rounded', 'style' => 'max-height: 400px; overflow-y: auto;']);
    foreach ($logmessages as $msg) {
        $icon = '&#9654; ';
        if (strpos($msg, 'Error') !== false || strpos($msg, 'Warning') !== false) {
            $icon = '&#10060; ';
        } else if (strpos($msg, 'Created') !== false || strpos($msg, 'successfully') !== false) {
            $icon = '&#9989; ';
        }
        echo $icon . s($msg) . "\n";
    }
    echo html_writer::end_tag('pre');
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
}

echo html_writer::div(
    $OUTPUT->single_button(
        new moodle_url('/local/testdata/index.php'),
        get_string('gen_back_to_dashboard', 'local_testdata'),
        'get'
    ),
    'mb-3'
);

echo $OUTPUT->footer();
