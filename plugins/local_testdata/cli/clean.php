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
 * CLI script for cleaning up test data.
 *
 * @package    local_testdata
 * @copyright  2024 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/user/lib.php');

use local_testdata\generator;

// Get options
list($options, $unrecognized) = cli_get_params([
    'dataset' => '',
    'all' => false,
    'list' => false,
    'help' => false,
], []);

if ($options['help']) {
    cli_writeln('Clean up test data sets');
    cli_writeln('');
    cli_writeln('Options:');
    cli_writeln('  --dataset=NAME         Name of dataset to clean');
    cli_writeln('  --all                  Clean all datasets');
    cli_writeln('  --list                 List all datasets');
    cli_writeln('  -h, --help             This help');
    cli_writeln('');
    cli_writeln('Example:');
    cli_writeln('  php clean.php --list');
    cli_writeln('  php clean.php --dataset=demo_2024');
    cli_writeln('  php clean.php --all');
    exit(0);
}

// Progress callback
$progressfn = function($message) {
    cli_writeln($message);
};

// List datasets
if ($options['list']) {
    cli_writeln("Test Data Sets:");
    cli_writeln("");

    $generator = new generator('', $progressfn);
    $datasets = $generator->get_datasets();

    if (empty($datasets)) {
        cli_writeln("  (no datasets found)");
    } else {
        foreach ($datasets as $dataset) {
            $createddate = userdate($dataset->timecreated, '%Y-%m-%d %H:%M:%S');
            cli_writeln("  - {$dataset->name} ({$dataset->itemcount} items, created: $createddate)");
        }
    }
    exit(0);
}

// Clean all datasets
if ($options['all']) {
    $generator = new generator('', $progressfn);
    $datasets = $generator->get_datasets();

    if (empty($datasets)) {
        cli_writeln("No datasets to clean.");
        exit(0);
    }

    cli_writeln("Cleaning all datasets (" . count($datasets) . " total)...");
    cli_writeln("");

    $deleted = 0;
    foreach ($datasets as $dataset) {
        try {
            $generator->delete_dataset($dataset->name);
            $deleted++;
        } catch (Exception $e) {
            cli_error("Error cleaning dataset {$dataset->name}: " . $e->getMessage(), false);
        }
    }

    cli_writeln("");
    cli_writeln("Cleaned $deleted dataset(s).");
    exit(0);
}

// Clean specific dataset
if (!empty($options['dataset'])) {
    $datasetname = $options['dataset'];
    cli_writeln("Cleaning dataset: $datasetname");
    cli_writeln("");

    $generator = new generator($datasetname, $progressfn);

    try {
        $generator->delete_dataset($datasetname);
        cli_writeln("");
        cli_writeln("Dataset cleaned successfully!");
        exit(0);
    } catch (Exception $e) {
        cli_error("Error: " . $e->getMessage());
    }
}

// No action specified
cli_writeln('No action specified. Use --help for usage information.');
exit(1);
