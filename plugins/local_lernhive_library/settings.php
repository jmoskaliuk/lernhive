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
 * Admin registration for local_lernhive_library.
 *
 * @package    local_lernhive_library
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_category(
        'local_lernhive_library_cat',
        get_string('pluginname', 'local_lernhive_library')
    ));

    $ADMIN->add('local_lernhive_library_cat', new admin_externalpage(
        'local_lernhive_library_catalog',
        get_string('open_library', 'local_lernhive_library'),
        new moodle_url('/local/lernhive_library/index.php'),
        'local/lernhive_library:import'
    ));

    $settings = new admin_settingpage(
        'local_lernhive_library_settings',
        get_string('settings_title', 'local_lernhive_library')
    );

    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_heading(
            'local_lernhive_library/heading_catalog_feed',
            get_string('heading_catalog_feed', 'local_lernhive_library'),
            get_string('heading_catalog_feed_desc', 'local_lernhive_library')
        ));

        $settings->add(new admin_setting_configtextarea(
            'local_lernhive_library/catalog_manifest_json',
            get_string('setting_catalog_manifest_json', 'local_lernhive_library'),
            get_string('setting_catalog_manifest_json_desc', 'local_lernhive_library'),
            '',
            PARAM_RAW,
            16,
            120
        ));
    }

    $ADMIN->add('local_lernhive_library_cat', $settings);
}

// Settings page is registered above in the admin tree.
$settings = null;
