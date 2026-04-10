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
 * Theme settings for the LernHive theme.
 *
 * @package    theme_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings = new admin_settingpage('theme_lernhive', get_string('configtitle', 'theme_lernhive'));

    $name = 'theme_lernhive/launcherstyle';
    $title = get_string('launcherstyle', 'theme_lernhive');
    $description = get_string('launcherstyledesc', 'theme_lernhive');
    $choices = [
        'base' => get_string('launcherstylebase', 'theme_lernhive'),
        'dock' => get_string('launcherstyledock', 'theme_lernhive'),
    ];
    $setting = new admin_setting_configselect($name, $title, $description, 'base', $choices);
    $settings->add($setting);

    $name = 'theme_lernhive/customcss';
    $title = get_string('customcss', 'theme_lernhive');
    $description = get_string('customcss_desc', 'theme_lernhive');
    $setting = new admin_setting_configtextarea($name, $title, $description, '', PARAM_RAW, 8, 80);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);
}
