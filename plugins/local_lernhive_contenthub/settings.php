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
 * Admin registration for local_lernhive_contenthub.
 *
 * Exposes a single category "LernHive ContentHub" under Local plugins
 * with two leaves:
 *   - Open ContentHub — the externalpage that renders the hub
 *   - ContentHub settings — one checkbox that gates the AI card
 *
 * @package    local_lernhive_contenthub
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    $ADMIN->add('localplugins', new admin_category(
        'local_lernhive_contenthub_cat',
        get_string('pluginname', 'local_lernhive_contenthub')
    ));

    $ADMIN->add('local_lernhive_contenthub_cat', new admin_externalpage(
        'local_lernhive_contenthub_hub',
        get_string('open_hub', 'local_lernhive_contenthub'),
        new moodle_url('/local/lernhive_contenthub/index.php'),
        'local/lernhive_contenthub:view'
    ));

    $settings = new admin_settingpage(
        'local_lernhive_contenthub_settings',
        get_string('settings_title', 'local_lernhive_contenthub')
    );

    $settings->add(new admin_setting_configcheckbox(
        'local_lernhive_contenthub/show_ai_card',
        get_string('setting_show_ai_card', 'local_lernhive_contenthub'),
        get_string('setting_show_ai_card_desc', 'local_lernhive_contenthub'),
        0
    ));

    $ADMIN->add('local_lernhive_contenthub_cat', $settings);
}

// Leaves are registered above — no top-level $settings handled here.
$settings = null;
