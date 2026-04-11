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
 * CLI script for generating test data via JSON configuration.
 *
 * @package    local_testdata
 * @copyright  2024 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->libdir . '/questionlib.php');

use local_testdata\generator;

// Get options
list($options, $unrecognized) = cli_get_params([
    'config' => '',
    'clean' => false,
    'help' => false,
], [
    'c' => 'config',
]);

if ($options['help'] || empty($options['config'])) {
    cli_writeln('Generate test data from JSON configuration file');
    cli_writeln('');
    cli_writeln('Options:');
    cli_writeln('  -c, --config=FILE      Path to JSON configuration file (REQUIRED)');
    cli_writeln('  --clean                Remove existing dataset of same name first');
    cli_writeln('  -h, --help             This help');
    cli_writeln('');
    cli_writeln('Example:');
    cli_writeln('  php generate.php --config=config.json');
    cli_writeln('  php generate.php --config=config.json --clean');
    exit(0);
}

// Validate file exists
if (!file_exists($options['config'])) {
    cli_error("Configuration file not found: {$options['config']}");
}

// Read and parse JSON
$configjson = file_get_contents($options['config']);
if (!$configjson) {
    cli_error("Could not read configuration file: {$options['config']}");
}

$config = json_decode($configjson, true);
if ($config === null) {
    cli_error("Invalid JSON in configuration file: " . json_last_error_msg());
}

// Progress callback
$progressfn = function($message) {
    cli_writeln($message);
};

// Get dataset name
$datasetname = $config['dataset'] ?? 'default_' . time();

// Clean existing dataset if requested
if ($options['clean']) {
    cli_writeln("Cleaning existing dataset: $datasetname");
    $gen = new generator($datasetname, $progressfn);
    $gen->delete_dataset($datasetname);
}

// Run configuration
cli_writeln("Starting test data generation for dataset: $datasetname");
cli_writeln("Using config file: {$options['config']}");
cli_writeln("");

$generator = new generator($datasetname, $progressfn);

try {
    if ($generator->run_config($config)) {
        cli_writeln("");
        cli_writeln("Dataset generation completed successfully!");
        exit(0);
    } else {
        cli_error("Dataset generation failed. Check output above for errors.");
    }
} catch (Exception $e) {
    cli_error("Fatal error: " . $e->getMessage());
}
