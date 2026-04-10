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
 * Admin settings for LernHive Flavour.
 *
 * @package    local_lernhive_flavour
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Create admin category.
    $category = new admin_category(
        'local_lernhive_flavour_category',
        get_string('pluginname', 'local_lernhive_flavour')
    );
    $ADMIN->add('localplugins', $category);

    // Add external page for flavour setup.
    $page = new admin_externalpage(
        'local_lernhive_flavour_setup',
        get_string('page_title', 'local_lernhive_flavour'),
        new moodle_url('/local/lernhive_flavour/admin_flavour.php'),
        'local/lernhive_flavour:manage'
    );
    $ADMIN->add('local_lernhive_flavour_category', $page);

    // Settings page for active flavour selection.
    $settings = new admin_settingpage(
        'local_lernhive_flavour_settings',
        get_string('pluginname', 'local_lernhive_flavour')
    );

    $options = [
        'school' => get_string('flavour_school', 'local_lernhive_flavour'),
        'lxp' => get_string('flavour_lxp', 'local_lernhive_flavour'),
    ];

    $settings->add(new admin_setting_configselect(
        'local_lernhive_flavour/active_flavour',
        get_string('setting_active_flavour', 'local_lernhive_flavour'),
        get_string('setting_active_flavour_desc', 'local_lernhive_flavour'),
        'school',
        $options
    ));

    $ADMIN->add('local_lernhive_flavour_category', $settings);
}
