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
 * AJAX handler for retrieving dataset items.
 *
 * @package    local_testdata
 * @copyright  2024 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_testdata\generator;

// Check permissions
require_login();
require_capability('local/testdata:manage', context_system::instance());

// Get dataset name
$datasetname = required_param('dataset', PARAM_ALPHANUMEXT);

// Get items
$generator = new generator($datasetname);
$items = $generator->get_dataset_items($datasetname);

// Build HTML table
$output = '';

if (empty($items)) {
    $output = '<p>No items found for this dataset.</p>';
} else {
    $table = new html_table();
    $table->head = [
        get_string('item_type', 'local_testdata'),
        get_string('item_name', 'local_testdata'),
        get_string('item_id', 'local_testdata'),
        get_string('item_created', 'local_testdata'),
    ];
    $table->attributes = ['class' => 'table table-sm table-striped'];
    $table->data = [];

    foreach ($items as $item) {
        $createddate = userdate($item->timecreated, '%Y-%m-%d %H:%M:%S');

        $table->data[] = [
            $item->entitytype,
            $item->entityname ?? '(unnamed)',
            $item->entityid,
            $createddate,
        ];
    }

    $output = html_writer::table($table);
}

echo $output;
