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
$regionmainsettingsmenu = $OUTPUT->region_main_settings_menu();
$hasregionmainsettingsmenu = !empty($regionmainsettingsmenu);
$launcherstyle = get_config('theme_lernhive', 'launcherstyle') ?: 'base';
$showlauncher = isloggedin() && !isguestuser();

$launchercontext = theme_lernhive_get_launcher_context();
$launchercontext['launcherisbase'] = $launcherstyle === 'base';
$launchercontext['launcherisdock'] = $launcherstyle === 'dock';

// Course pages always show the page header (user block, dock, launcher).
// The flag exists to let the frontpage suppress it — we never want to suppress
// it on a course.
$showpageheader = true;

$blockregions = theme_lernhive_get_block_regions_context($OUTPUT);

// Context Dock — floating action strip (Teacher/Trainer actions per page).
// Matches drawers.php so course pages render the same context dock as all
// other learner-facing pages.
$dockitems = theme_lernhive_get_context_dock_items();

// Header Dock — unified side-panel system (0.9.36).
$sidepanelitems = theme_lernhive_get_sidepanel_items();

// User header context (avatar → profile, language menu, logout) — same helper
// drawers.php uses, shared across layouts for consistent header chrome.
$userheaderctx = theme_lernhive_get_header_user_context($OUTPUT);

// Primary navigation — shared helper. On course pages we render the reduced
// "Standard-Kürzel" variant via sidebar_course.mustache, but we still need the
// full $navitems array for template parts that expect it (none today, but
// keeping it for symmetry with drawers.php + future partials).
$navitems = theme_lernhive_get_primary_navitems($PAGE);

// Reduced nav + course index for the course-specific sidebar variant.
// See theme_lernhive_get_course_sidebar_context() for the whitelist and the
// core course-index render path.
$coursesidebar = theme_lernhive_get_course_sidebar_context($PAGE);

// --- Course Plugin Shell header context (Zone A + Zone B) -------------------
// Builds a lightweight header that matches the Plugin Shell pattern used by
// all LernHive local plugins. Replaces Moodle's full_header() on course pages
// so the course view is visually consistent with ContentHub, Copy, etc.
//
// Zone A: back-to-my-courses arrow, course title.
// Zone B: category breadcrumb + participant count stub + secondary nav tabs.
//
// Secondary nav (Nutzer/innen / Einstellungen tabs) is rendered separately via
// output.secondary_nav so we don't lose it when full_header is suppressed.
$courseheaderctx = [];
if (!empty($COURSE) && $COURSE->id > 1) {
    $coursecontext = \core\context\course::instance($COURSE->id);
    $coursename    = format_string($COURSE->fullname, true, ['context' => $coursecontext]);
    $shortname     = format_string($COURSE->shortname, true, ['context' => $coursecontext]);

    // Category — fetch once; guard with IGNORE_MISSING so broken data never
    // breaks the page render.
    $categoryname = '';
    if (!empty($COURSE->category)) {
        try {
            $cat = \core_course_category::get($COURSE->category, IGNORE_MISSING);
            $categoryname = $cat ? format_string($cat->name) : '';
        } catch (\Throwable $e) {
            $categoryname = '';
        }
    }

    $courseheaderctx = [
        'coursename'      => $coursename,
        'shortname'       => $shortname,
        'hascategoryname' => $categoryname !== '',
        'categoryname'    => $categoryname,
        'backurl'         => (new moodle_url('/my/courses.php'))->out(false),
        'backlabel'       => get_string('mycourses'),
        'hascourseheader' => true,
    ];
}

$templatecontext = array_merge([
    'sitename' => format_string($SITE->fullname, true, ['context' => context_system::instance()]),
    'output' => $OUTPUT,
    'bodyattributes' => $bodyattributes,
    'hasregionmainsettingsmenu' => $hasregionmainsettingsmenu,
    'regionmainsettingsmenu' => $regionmainsettingsmenu,
    'launcherisbase' => $launcherstyle === 'base',
    'launcherisdock' => $launcherstyle === 'dock',
    'showlauncher' => $showlauncher,
    'showpageheader' => $showpageheader,
    'launcher' => $launchercontext,
    'navitems' => $navitems,
    'coursesidebar' => $coursesidebar,
    'sidepanelitems' => $sidepanelitems,
    'hassidepanel' => !empty($sidepanelitems),
    'dockitems' => $dockitems,
    'hasdockitems' => !empty($dockitems),
    'courseheader' => $courseheaderctx,
    'hascourseheader' => !empty($courseheaderctx),
], $blockregions, $userheaderctx);

echo $OUTPUT->render_from_template('theme_lernhive/course', $templatecontext);
