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

$bodyattributes = $OUTPUT->body_attributes(['theme-lernhive', 'theme-lernhive-course']);
$sidepreblocks = $OUTPUT->blocks('side-pre');
$hassidepre = (strpos($sidepreblocks, 'data-block=') !== false || !empty(trim($sidepreblocks)));
$regionmainsettingsmenu = $OUTPUT->region_main_settings_menu();
$hasregionmainsettingsmenu = !empty($regionmainsettingsmenu);
$launcherstyle = get_config('theme_lernhive', 'launcherstyle') ?: 'base';

$launchercontext = theme_lernhive_get_launcher_context();
$launchercontext['launcherisbase'] = $launcherstyle === 'base';
$launchercontext['launcherisdock'] = $launcherstyle === 'dock';

$templatecontext = [
    'sitename' => format_string($SITE->fullname, true, ['context' => context_system::instance()]),
    'output' => $OUTPUT,
    'bodyattributes' => $bodyattributes,
    'sidepreblocks' => $sidepreblocks,
    'hassidepre' => $hassidepre,
    'hasregionmainsettingsmenu' => $hasregionmainsettingsmenu,
    'regionmainsettingsmenu' => $regionmainsettingsmenu,
    'launcherisbase' => $launcherstyle === 'base',
    'launcherisdock' => $launcherstyle === 'dock',
    'launcher' => $launchercontext,
];

echo $OUTPUT->render_from_template('theme_lernhive/course', $templatecontext);
