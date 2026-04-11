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
 * Generate test data page — processes the form and runs the generator.
 *
 * @package    local_testdata
 * @copyright  2024 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_testdata\generator;
use local_testdata\form\generate_form;

admin_externalpage_setup('local_testdata');
require_capability('local/testdata:manage', \core\context\system::instance());

$PAGE->set_url(new moodle_url('/local/testdata/generate.php'));
$PAGE->set_title(get_string('gen_page_title', 'local_testdata'));
$PAGE->set_heading(get_string('gen_page_heading', 'local_testdata'));

$form = new generate_form(new moodle_url('/local/testdata/generate.php'));

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/testdata/index.php'));
}

if ($data = $form->get_data()) {
    // Collect log messages.
    $logmessages = [];
    $progresscb = function ($msg) use (&$logmessages) {
        $logmessages[] = $msg;
    };

    $gen = new generator($data->datasetname, $progresscb);

    if ($data->template !== 'none') {
        // ---- Template-based generation ----
        $configpath = __DIR__ . '/configs/' . $data->template . '.json';
        if (!file_exists($configpath)) {
            redirect(new moodle_url('/local/testdata/index.php'),
                get_string('gen_error_template_not_found', 'local_testdata'),
                null,
                \core\output\notification::NOTIFY_ERROR
            );
        }
        $config = json_decode(file_get_contents($configpath), true);
        $config['dataset'] = $data->datasetname;
        $config['description'] = $data->datasetdesc ?: ($config['description'] ?? '');
        $gen->run_config($config);
    } else {
        // ---- Custom generation ----
        $config = [
            'dataset' => $data->datasetname,
            'description' => $data->datasetdesc ?? '',
        ];

        // Build users array.
        if (!empty($data->gen_users) && $data->user_count > 0) {
            $config['users'] = [];
            $prefix = $data->user_prefix ?: 'testuser';
            for ($i = 1; $i <= $data->user_count; $i++) {
                $num = str_pad($i, 3, '0', STR_PAD_LEFT);
                $config['users'][] = [
                    'username'  => "{$prefix}{$num}",
                    'firstname' => ucfirst($prefix),
                    'lastname'  => "User {$num}",
                    'password'  => $data->user_password ?: 'Test1234!',
                ];
            }
        }

        // Build courses array.
        if (!empty($data->gen_courses) && $data->course_count > 0) {
            $config['courses'] = [];
            $cprefix = $data->course_prefix ?: 'Testkurs';
            for ($c = 1; $c <= $data->course_count; $c++) {
                $coursedef = [
                    'fullname'  => "{$cprefix} {$c}",
                    'shortname' => strtolower(str_replace(' ', '', $cprefix)) . "_{$c}_" . time(),
                ];

                // Questions.
                if (!empty($data->gen_questions) && $data->questions_per_course > 0) {
                    $items = [];
                    for ($q = 1; $q <= $data->questions_per_course; $q++) {
                        $items[] = [
                            'name'         => "{$cprefix} {$c} — Frage {$q}",
                            'questiontext' => "<p>Frage {$q} aus {$cprefix} {$c}: Was ist die richtige Antwort?</p>",
                            'answer'       => 'Antwort A (richtig)',
                            'wrong'        => ['Antwort B', 'Antwort C', 'Antwort D'],
                        ];
                    }
                    $coursedef['questions'] = [[
                        'category' => "{$cprefix} {$c} Fragen",
                        'items'    => $items,
                    ]];
                }

                // Activities.
                if (!empty($data->gen_activities)) {
                    $coursedef['activities'] = [[
                        'module'  => $data->activity_type ?? 'leitnerflow',
                        'name'    => "LeitnerFlow: {$cprefix} {$c}",
                        'section' => 1,
                        'settings' => [
                            'questioncategoryid' => '$auto',
                            'sessionsize'        => 5,
                            'boxcount'           => 3,
                            'correcttolearn'     => 2,
                            'wrongbehavior'      => 0,
                            'questionrotation'   => 1,
                            'prioritystrategy'   => 0,
                            'grade'              => 100,
                            'grademethod'        => 1,
                        ],
                    ]];
                }

                // Enrolments.
                if (!empty($data->gen_enrol)) {
                    $coursedef['enrol'] = '$all_users';
                }

                $config['courses'][] = $coursedef;
            }
        }

        $gen->run_config($config);
    }

    // Show results page.
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('gen_results_title', 'local_testdata'));

    // Success notification.
    echo $OUTPUT->notification(
        get_string('gen_results_success', 'local_testdata', $data->datasetname),
        \core\output\notification::NOTIFY_SUCCESS
    );

    // Log output.
    if (!empty($logmessages)) {
        echo html_writer::start_tag('div', ['class' => 'testdata-log card mb-3']);
        echo html_writer::start_tag('div', ['class' => 'card-body']);
        echo html_writer::tag('h5', get_string('gen_results_log', 'local_testdata'), ['class' => 'card-title']);
        echo html_writer::start_tag('pre', ['class' => 'bg-light p-3 rounded', 'style' => 'max-height: 400px; overflow-y: auto;']);
        foreach ($logmessages as $msg) {
            $icon = '&#9654; ';
            if (strpos($msg, 'Error') !== false) {
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

    // Back button.
    echo html_writer::div(
        $OUTPUT->single_button(
            new moodle_url('/local/testdata/index.php'),
            get_string('gen_back_to_dashboard', 'local_testdata'),
            'get'
        ),
        'mb-3'
    );

    echo $OUTPUT->footer();
    exit;
}

// ---- Show form ----
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('gen_page_heading', 'local_testdata'));
$form->display();
echo $OUTPUT->footer();
