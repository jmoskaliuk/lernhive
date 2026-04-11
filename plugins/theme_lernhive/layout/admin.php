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

// Admin layout: LernHive app-shell (sidebar) + Moodle full_header (admin nav tree).
// The sidebar gives consistent top-level navigation (Home / Dashboard / MyCourses /
// SiteAdmin). The full_header renders Moodle's secondary navigation which includes
// the admin settings category tree on admin pages.

$bodyattributes = $OUTPUT->body_attributes(['theme-lernhive', 'theme-lernhive-admin']);
$regionmainsettingsmenu = $OUTPUT->region_main_settings_menu();
$hasregionmainsettingsmenu = !empty($regionmainsettingsmenu);
$launcherstyle = get_config('theme_lernhive', 'launcherstyle') ?: 'base';
$showlauncher = isloggedin() && !isguestuser();

$launchercontext = theme_lernhive_get_launcher_context();
$launchercontext['launcherisbase'] = $launcherstyle === 'base';
$launchercontext['launcherisdock'] = $launcherstyle === 'dock';

// Build primary navigation items (same logic as drawers.php).
$navitems = [];

$navitems[] = [
    'url'      => (new moodle_url('/'))->out(false),
    'text'     => get_string('home'),
    'key'      => 'home',
    'isactive' => false,
    'faicon'   => 'home',
];

if (isloggedin() && !isguestuser()) {
    $navitems[] = [
        'url'      => (new moodle_url('/my/'))->out(false),
        'text'     => get_string('myhome'),
        'key'      => 'myhome',
        'isactive' => false,
        'faicon'   => 'tachometer',
    ];
    $navitems[] = [
        'url'      => (new moodle_url('/my/courses.php'))->out(false),
        'text'     => get_string('mycourses'),
        'key'      => 'mycourses',
        'isactive' => false,
        'faicon'   => 'graduation-cap',
    ];
}

if (is_siteadmin()) {
    $navitems[] = [
        'url'      => (new moodle_url('/admin/index.php'))->out(false),
        'text'     => get_string('administrationsite'),
        'key'      => 'siteadmin',
        'isactive' => true,   // always active on admin pages
        'faicon'   => 'cog',
    ];
}

// User header context: avatar → profile link, language menu, logout.
$userheaderctx = theme_lernhive_get_header_user_context($OUTPUT);

// Header Dock — unified side-panel system (0.9.36).
$sidepanelitems = theme_lernhive_get_sidepanel_items();

// Admin secondary navigation — render via the canonical Boost pipeline:
//   more_menu($PAGE->secondarynav) → core/moremenu template partial.
// This produces the standard admin category tab bar (General | Users |
// Courses | Grades | Plugins | Appearance | Server | Reports | Development, …)
// exactly like the Boost theme, so admins see a familiar navigation surface.
// 0.9.34: replaces the custom theme_lernhive_get_admin_topnav() helper that
// walked admin_get_root() directly and produced an L1/L2-mixed tab list.
$secondarymoremenu = false;
$overflow = '';
if ($PAGE->has_secondary_navigation()) {
    $tablistnav = $PAGE->has_tablist_secondary_navigation();
    $moremenu = new \core\navigation\output\more_menu($PAGE->secondarynav, 'nav-tabs', true, $tablistnav);
    $secondarymoremenu = $moremenu->export_for_template($OUTPUT);
    $overflowdata = $PAGE->secondarynav->get_overflow_menu_data();
    if (!is_null($overflowdata)) {
        $overflow = $overflowdata->export_for_template($OUTPUT);
    }
}

// Admin pages have no block regions — keep $blockregions empty rather than
// calling theme_lernhive_get_block_regions_context() with regions that don't
// exist in this layout (config.php maps admin → regions: []).
$templatecontext = array_merge([
    'sitename'                  => format_string($SITE->fullname, true, ['context' => context_system::instance()]),
    'output'                    => $OUTPUT,
    'bodyattributes'            => $bodyattributes,
    'hasregionmainsettingsmenu' => $hasregionmainsettingsmenu,
    'regionmainsettingsmenu'    => $regionmainsettingsmenu,
    'launcherisbase'            => $launcherstyle === 'base',
    'launcherisdock'            => $launcherstyle === 'dock',
    'showlauncher'              => $showlauncher,
    'launcher'                  => $launchercontext,
    'navitems'                  => $navitems,
    'secondarymoremenu'         => $secondarymoremenu ?: false,
    'overflow'                  => $overflow,
    'sidepanelitems'            => $sidepanelitems,
    'hassidepanel'              => !empty($sidepanelitems),
], $userheaderctx);

echo $OUTPUT->render_from_template('theme_lernhive/admin', $templatecontext);
