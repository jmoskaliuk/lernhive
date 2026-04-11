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
 * Dashboard page for managing test data.
 *
 * @package    local_testdata
 * @copyright  2024 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_testdata\generator;

// Check permissions.
admin_externalpage_setup('local_testdata');
require_capability('local/testdata:manage', \core\context\system::instance());

// ---- Handle delete action ----
if ($deletename = optional_param('delete', '', PARAM_ALPHANUMEXT)) {
    require_sesskey();
    $gen = new generator($deletename, function ($msg) {});
    $gen->delete_dataset($deletename);
    redirect(new moodle_url('/local/testdata/index.php'),
        get_string('dataset_deleted', 'local_testdata', $deletename),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// ---- Handle delete all ----
if (optional_param('deleteall', 0, PARAM_INT)) {
    require_sesskey();
    $gen = new generator('', function ($msg) {});
    foreach ($gen->get_datasets() as $ds) {
        $gen->delete_dataset($ds->name);
    }
    redirect(new moodle_url('/local/testdata/index.php'),
        get_string('all_deleted', 'local_testdata'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// ---- Page setup ----
$PAGE->set_url(new moodle_url('/local/testdata/index.php'));
$PAGE->set_title(get_string('dashboard_title', 'local_testdata'));
$PAGE->set_heading(get_string('dashboard_title', 'local_testdata'));

$gen = new generator('');
$datasets = $gen->get_datasets();

echo $OUTPUT->header();

// ---- Action cards ----
echo html_writer::start_div('testdata-actions mb-4');

echo html_writer::start_div('row');

// Generate New button.
$generateurl = new moodle_url('/local/testdata/generate.php');
echo html_writer::start_div('col-md-4 mb-3');
echo html_writer::start_div('card h-100 border-primary');
echo html_writer::start_div('card-body text-center');
echo html_writer::tag('div', '&#128736;', ['class' => 'mb-2', 'style' => 'font-size: 2.5rem;']);
echo html_writer::tag('h5', get_string('action_generate', 'local_testdata'), ['class' => 'card-title']);
echo html_writer::tag('p', get_string('action_generate_desc', 'local_testdata'),
    ['class' => 'card-text text-muted']);
echo html_writer::link($generateurl, get_string('action_generate_btn', 'local_testdata'),
    ['class' => 'btn btn-primary']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Quick LeitnerFlow demo.
echo html_writer::start_div('col-md-4 mb-3');
echo html_writer::start_div('card h-100 border-success');
echo html_writer::start_div('card-body text-center');
echo html_writer::tag('div', '&#9889;', ['class' => 'mb-2', 'style' => 'font-size: 2.5rem;']);
echo html_writer::tag('h5', get_string('action_quickdemo', 'local_testdata'), ['class' => 'card-title']);
echo html_writer::tag('p', get_string('action_quickdemo_desc', 'local_testdata'),
    ['class' => 'card-text text-muted']);

$quickurl = new moodle_url('/local/testdata/quickgen.php', ['template' => 'leitnerflow_demo', 'sesskey' => sesskey()]);
echo html_writer::link($quickurl, get_string('action_quickdemo_btn', 'local_testdata'),
    ['class' => 'btn btn-success',
     'onclick' => "return confirm('" . addslashes(get_string('quickgen_confirm', 'local_testdata')) . "');"]);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Stats card.
echo html_writer::start_div('col-md-4 mb-3');
echo html_writer::start_div('card h-100 border-info');
echo html_writer::start_div('card-body text-center');
echo html_writer::tag('div', '&#128202;', ['class' => 'mb-2', 'style' => 'font-size: 2.5rem;']);
echo html_writer::tag('h5', get_string('action_stats', 'local_testdata'), ['class' => 'card-title']);

$totalitems = 0;
foreach ($datasets as $ds) {
    $totalitems += $ds->itemcount;
}
echo html_writer::tag('p',
    get_string('stats_summary', 'local_testdata', (object) [
        'datasets' => count($datasets),
        'items'    => $totalitems,
    ]),
    ['class' => 'card-text']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div(); // row
echo html_writer::end_div(); // testdata-actions

// ---- Existing datasets table ----
echo html_writer::tag('h4', get_string('datasets_heading', 'local_testdata'), ['class' => 'mb-3']);

if (empty($datasets)) {
    echo $OUTPUT->notification(get_string('no_datasets', 'local_testdata'), 'info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('dataset_name', 'local_testdata'),
        get_string('dataset_description', 'local_testdata'),
        get_string('dataset_created', 'local_testdata'),
        get_string('dataset_items', 'local_testdata'),
        get_string('dataset_actions', 'local_testdata'),
    ];
    $table->attributes = ['class' => 'admintable generaltable'];

    foreach ($datasets as $ds) {
        // Entity type breakdown.
        $items = $gen->get_dataset_items($ds->name);
        $typecounts = [];
        foreach ($items as $item) {
            $typecounts[$item->entitytype] = ($typecounts[$item->entitytype] ?? 0) + 1;
        }
        $badges = '';
        $typeicons = [
            'user'              => '&#128100;',
            'course'            => '&#128218;',
            'question'          => '&#10067;',
            'question_category' => '&#128193;',
            'course_module'     => '&#9881;',
            'enrolment'         => '&#128279;',
        ];
        foreach ($typecounts as $type => $count) {
            $icon = $typeicons[$type] ?? '&#8226;';
            $badges .= html_writer::span(
                "{$icon} {$count} {$type}",
                'badge bg-secondary text-white mr-1 mb-1',
                ['style' => 'font-size: 0.8em; margin-right: 4px;']
            ) . ' ';
        }

        // Actions.
        $deleteurl = new moodle_url('/local/testdata/index.php', [
            'delete'  => $ds->name,
            'sesskey' => sesskey(),
        ]);
        $deletelink = html_writer::link($deleteurl,
            get_string('delete_button', 'local_testdata'),
            ['class' => 'btn btn-sm btn-danger',
             'onclick' => "return confirm('" . addslashes(
                 get_string('delete_confirm', 'local_testdata', $ds->name)
             ) . "');"]
        );

        $table->data[] = [
            html_writer::tag('strong', s($ds->name)),
            s($ds->description ?: '-'),
            userdate($ds->timecreated, get_string('strftimedatetimeshort', 'langconfig')),
            $badges,
            $deletelink,
        ];
    }

    echo html_writer::table($table);

    // Delete all button.
    $deleteallurl = new moodle_url('/local/testdata/index.php', [
        'deleteall' => 1,
        'sesskey'   => sesskey(),
    ]);
    echo html_writer::div(
        html_writer::link($deleteallurl,
            get_string('delete_all_button', 'local_testdata'),
            ['class' => 'btn btn-outline-danger',
             'onclick' => "return confirm('" . addslashes(
                 get_string('delete_all_confirm', 'local_testdata')
             ) . "');"]
        ),
        'mt-2'
    );
}

echo $OUTPUT->footer();
