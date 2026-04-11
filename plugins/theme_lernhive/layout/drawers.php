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

$bodyattributes = $OUTPUT->body_attributes(['theme-lernhive']);
$regionmainsettingsmenu = $OUTPUT->region_main_settings_menu();
$hasregionmainsettingsmenu = !empty($regionmainsettingsmenu);
$launcherstyle = get_config('theme_lernhive', 'launcherstyle') ?: 'base';
$isfrontpage = $PAGE->pagelayout === 'frontpage';
$showlauncher = isloggedin() && !isguestuser();

// NOTE: Do NOT call $OUTPUT->main_content() here — it returns empty because
// page content hasn't been buffered yet at layout-file execution time.
// Use {{{ output.main_content }}} directly in the Mustache template instead.

// Suppress the page-header card on the frontpage — the brand in the sidebar
// already anchors the identity; a header bar here just looks empty.
$showpageheader = !$isfrontpage;

// Note: primary navigation is rendered via a manual navitems array rather than
// output.primary_nav, which returned empty in Moodle 5.x for unknown reasons.
// The canonical builder lives in lib.php as theme_lernhive_get_primary_navitems()
// so all layouts share the exact same list (see 0.9.43 DRY refactor).

$launchercontext = theme_lernhive_get_launcher_context();
$launchercontext['launcherisbase'] = $launcherstyle === 'base';
$launchercontext['launcherisdock'] = $launcherstyle === 'dock';

// Primary navigation — single source of truth in lib.php.
$navitems = theme_lernhive_get_primary_navitems($PAGE);

$blockregions = theme_lernhive_get_block_regions_context($OUTPUT);

// Plugin Shell: build a Zone A / Zone B context for the handful of core
// Moodle pages (Dashboard, My courses, Profile) that should look like the
// LernHive local plugins. Returns null for everything else — local plugins
// render their own Plugin Shell inside `output.main_content`, so we never
// inject a second header on top of theirs.
$pluginshell = theme_lernhive_get_plugin_shell_context($PAGE);
$haspluginshell = $pluginshell !== null;

// Context Dock — floating action strip (Teacher/Trainer actions per page context).
$dockitems = theme_lernhive_get_context_dock_items();

// Header Dock — unified side-panel system (messages, notifications, AI, help).
// Always visible in the top-right, one shared panel, only one open at a time.
$sidepanelitems = theme_lernhive_get_sidepanel_items();

// User header context: avatar → profile link, language menu, logout.
$userheaderctx = theme_lernhive_get_header_user_context($OUTPUT);

$templatecontext = array_merge([
    'sitename'                  => format_string($SITE->fullname, true, ['context' => context_system::instance()]),
    'output'                    => $OUTPUT,
    'bodyattributes'            => $bodyattributes,
    'hasregionmainsettingsmenu' => $hasregionmainsettingsmenu,
    'regionmainsettingsmenu'    => $regionmainsettingsmenu,
    'launcherisbase'            => $launcherstyle === 'base',
    'launcherisdock'            => $launcherstyle === 'dock',
    'showlauncher'              => $showlauncher,
    'showpageheader'            => $showpageheader,
    'launcher'                  => $launchercontext,
    'navitems'                  => $navitems,
    'isfrontpage'               => $isfrontpage,
    'dockitems'                 => $dockitems,
    'hasdockitems'              => !empty($dockitems),
    'sidepanelitems'            => $sidepanelitems,
    'hassidepanel'              => !empty($sidepanelitems),
    'pluginshell'               => $pluginshell,
    'haspluginshell'            => $haspluginshell,
], $blockregions, $userheaderctx);

echo $OUTPUT->render_from_template('theme_lernhive/drawers', $templatecontext);
