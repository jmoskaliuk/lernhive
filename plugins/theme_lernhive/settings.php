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

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $name = 'theme_lernhive/launcherstyle';
    $title = get_string('launcherstyle', 'theme_lernhive');
    $description = get_string('launcherstyledesc', 'theme_lernhive');
    $choices = [
        'base' => get_string('launcherstylebase', 'theme_lernhive'),
        'dock' => get_string('launcherstyledock', 'theme_lernhive'),
    ];
    $setting = new admin_setting_configselect($name, $title, $description, 'base', $choices);
    $settings->add($setting);
}
