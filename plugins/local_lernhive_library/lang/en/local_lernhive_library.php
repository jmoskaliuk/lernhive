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
 * English strings for local_lernhive_library.
 *
 * @package    local_lernhive_library
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'LernHive Library';

// Capabilities (db/access.php).
$string['lernhive_library:import'] = 'Import courses from the LernHive Library';

// Admin tree.
$string['open_library'] = 'Open LernHive Library';

// Catalog page.
$string['catalog_heading'] = 'LernHive Library';
$string['catalog_intro'] = 'Ready-to-use courses curated and maintained by eLeDia. Import a library course to use it as a starting point — you can edit it freely afterwards, and future library updates will stay visible as a version notice.';
$string['catalog_empty'] = 'The library catalog is empty. Your eLeDia account manager seeds the catalog with courses that match your flavour.';

// Catalog entry (placeholder columns for the R1 stub).
$string['label_version'] = 'Version';
$string['label_updated'] = 'Last updated';
$string['label_language'] = 'Language';
$string['btn_import'] = 'Import';

// Privacy.
$string['privacy:metadata'] = 'The LernHive Library plugin does not store any personal data. Import operations are delegated to Moodle core backup and restore, which have their own privacy providers.';
