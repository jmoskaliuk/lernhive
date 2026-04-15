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

$bodyattributes = $OUTPUT->body_attributes(['theme-lernhive', 'limitedwidth']);

// ── Hero panel context for split-screen login page ───────────────────────────
// Static feature list and tagline — rendered on the left side of the login page.
$herofeatures = [
    [
        'icon'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>',
        'title'   => get_string('hero_feature1_title', 'theme_lernhive'),
        'desc'    => get_string('hero_feature1_desc',  'theme_lernhive'),
    ],
    [
        'icon'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
        'title'   => get_string('hero_feature2_title', 'theme_lernhive'),
        'desc'    => get_string('hero_feature2_desc',  'theme_lernhive'),
    ],
    [
        'icon'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'title'   => get_string('hero_feature3_title', 'theme_lernhive'),
        'desc'    => get_string('hero_feature3_desc',  'theme_lernhive'),
    ],
];

// Available courses for the hero panel teaser — max 3, skip site course (id=1),
// only visible courses. Wrapped in try/catch so a broken DB never kills login.
$herocourses = [];
try {
    $syscontext = \core\context\system::instance();
    $fields = 'id, fullname, shortname, visible, category';
    $allcourses = get_courses('all', 'c.sortorder ASC', $fields);
    $dotcolors = ['#f98012', '#65a1b3', '#3aadaa', '#a855f7', '#10b981'];
    $ci = 0;
    foreach ($allcourses as $c) {
        if ($c->id == 1 || !$c->visible) {
            continue;
        }
        $herocourses[] = [
            'name'  => format_string($c->fullname, true, ['context' => $syscontext]),
            'color' => $dotcolors[$ci % count($dotcolors)],
            'url'   => (new moodle_url('/course/view.php', ['id' => $c->id]))->out(false),
        ];
        $ci++;
        if ($ci >= 3) {
            break;
        }
    }
} catch (\Throwable $e) {
    $herocourses = [];
}

$templatecontext = [
    'sitename'     => format_string($SITE->fullname, true, ['context' => context_system::instance()]),
    'output'       => $OUTPUT,
    'bodyattributes' => $bodyattributes,
    'herotagline'  => get_string('hero_tagline', 'theme_lernhive'),
    'herodesc'     => get_string('hero_desc',    'theme_lernhive'),
    'herofeatures' => $herofeatures,
    'herocourses'  => $herocourses,
    'hascourses'   => !empty($herocourses),
];

echo $OUTPUT->render_from_template('theme_lernhive/login', $templatecontext);
