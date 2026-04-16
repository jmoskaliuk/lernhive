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
 * Library functions for the LernHive theme.
 *
 * @package    theme_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Get the pre-SCSS content.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_lernhive_get_pre_scss($theme) {
    global $CFG;

    $prescss = '';

    $variablesfile = $CFG->dirroot . '/theme/lernhive/scss/lernhive/_variables.scss';
    if (file_exists($variablesfile)) {
        $prescss .= file_get_contents($variablesfile);
    }

    $prefile = $CFG->dirroot . '/theme/lernhive/scss/pre.scss';
    if (file_exists($prefile)) {
        $prescss .= "\n" . file_get_contents($prefile);
    }

    return $prescss;
}

/**
 * Get the extra SCSS content.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_lernhive_get_extra_scss($theme) {
    global $CFG;

    $extrascss = '';

    $variablesfile = $CFG->dirroot . '/theme/lernhive/scss/lernhive/_variables.scss';
    if (file_exists($variablesfile)) {
        $vars = file_get_contents($variablesfile);
        $vars = str_replace(' !default', '', $vars);
        $extrascss .= "\n// --- Force-inject variables for extra SCSS ---\n";
        $extrascss .= $vars . "\n";
    }

    $scssdir = $CFG->dirroot . '/theme/lernhive/scss/lernhive';
    // 0.9.5: mobile-first rewrite — single-responsibility partials in load order.
    // _base       → global resets, body, a11y, Moodle chrome hide
    // _layout     → app shell, sidebar, page, page-header (single source of truth)
    // _navigation → brand, flat_navigation, sidebar blocks/notes
    // _components → cards, buttons, badges, progress, alerts, tables, forms
    // _blocks     → block regions (content-top/bottom, footer columns)
    // _dashboard  → Learner/Trainer dashboard surfaces (NO admin, NO course content)
    // _course     → course page shell only (NO section/activity content — ADR-P01)
    // _login      → login page (full-screen, does NOT use app-shell grid)
    $partials = [
        '_base.scss',
        '_layout.scss',
        '_navigation.scss',
        '_components.scss',
        '_blocks.scss',
        '_dashboard.scss',
        '_course.scss',
        '_login.scss',
        '_dock.scss',        // 0.9.21: Context Dock — floating action strip
        '_plugin-shell.scss', // 0.9.27: Plugin Shell — 2-zone page header for local plugins
        '_icons.scss',       // 0.9.32: Icon Taxonomy — nav / artifact / action / info (0.9.38) classes
        '_buttons.scss',     // 0.9.66: Button system — lh-btn-start/open/ghost/action helper classes (all 8px)
        '_sidepanel.scss',   // 0.9.36: Header Dock + Side Panel — unified overlay system
    ];

    foreach ($partials as $partial) {
        $filepath = $scssdir . '/' . $partial;
        if (file_exists($filepath)) {
            $extrascss .= "\n// --- {$partial} ---\n";
            $extrascss .= file_get_contents($filepath);
        }
    }

    $postfile = $CFG->dirroot . '/theme/lernhive/scss/post.scss';
    if (file_exists($postfile)) {
        $extrascss .= "\n// --- post.scss ---\n";
        $extrascss .= file_get_contents($postfile);
    }

    $extrascss .= <<<'CSSVARS'

// --- Bootstrap 5 CSS Custom Properties override (LernHive / eLeDia CI) ---
:root {
    --bs-primary: #194866;
    --bs-primary-rgb: 25, 72, 102;
    --bs-secondary: #65a1b3;
    --bs-secondary-rgb: 101, 161, 179;
    --bs-success: #3aadaa;
    --bs-success-rgb: 58, 173, 170;
    --bs-warning: #f98012;
    --bs-warning-rgb: 249, 128, 18;
    --bs-danger: #ab1d79;
    --bs-danger-rgb: 171, 29, 121;
    --bs-info: #65a1b3;
    --bs-info-rgb: 101, 161, 179;
    --lh-accent: #f98012;
    --lh-accent-rgb: 249, 128, 18;
    --bs-body-bg: #ffffff;
    --bs-body-bg-rgb: 255, 255, 255;
    --bs-body-color: #353535;
    --bs-body-color-rgb: 53, 53, 53;
    --bs-body-font-family: "Open Sans", "Helvetica Neue", Arial, sans-serif;
    --bs-body-font-size: 1rem;
    --bs-link-color: #194866;
    --bs-link-color-rgb: 25, 72, 102;
    --bs-link-hover-color: #0f2d3f;
    --bs-link-hover-color-rgb: 15, 45, 63;
    --bs-border-radius: 8px;
    --bs-border-radius-sm: 6px;
    --bs-border-radius-lg: 12px;
    --bs-border-color: #e9e9e9;
}
CSSVARS;

    $extrascss .= <<<'SCSS'

.theme-lernhive .lernhive-sr-only {
  position: absolute !important;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
SCSS;

    $customcss = get_config('theme_lernhive', 'customcss');
    if (!empty($customcss)) {
        $extrascss .= "\n" . $customcss;
    }

    return $extrascss;
}

/**
 * Serve the theme's files.
 *
 * @param stdClass $course The course object.
 * @param stdClass $cm The course module object.
 * @param context $context The context.
 * @param string $filearea The file area.
 * @param array $args Extra arguments.
 * @param bool $forcedownload Force download.
 * @param array $options Additional options.
 * @return bool
 */
function theme_lernhive_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel == CONTEXT_SYSTEM) {
        $theme = theme_config::load('lernhive');
        return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
    }
    send_file_not_found();
}

/**
 * Build launcher context for the theme shell.
 *
 * @return array<string, mixed>
 */
function theme_lernhive_get_launcher_context(): array {
    $fallbackcontext = [
        'title' => get_string('launcher', 'theme_lernhive'),
        'description' => get_string('launcherdesc', 'theme_lernhive'),
        'empty' => true,
        'emptytext' => get_string('launchernoactions', 'theme_lernhive'),
        'actions' => [],
    ];

    if (!class_exists(\local_lernhive_launcher\launcher_manager::class)) {
        return $fallbackcontext;
    }

    try {
        return \local_lernhive_launcher\launcher_manager::get_theme_context();
    } catch (\Throwable $e) {
        return $fallbackcontext;
    }
}

/**
 * Build the Context Dock items array for the current page and user.
 *
 * Returns an array of dock items suitable for the context_dock.mustache template.
 * Returns an empty array when the dock should not be shown (guest, no context).
 *
 * Each item has keys: key, icon, label, url, active, divider.
 *
 * @return array<int, array<string, mixed>>
 */
function theme_lernhive_get_context_dock_items(): array {
    global $PAGE, $COURSE;

    if (!isloggedin() || isguestuser()) {
        return [];
    }

    $items = [];
    $layout = $PAGE->pagelayout;
    $pagepath = $PAGE->url->get_path();
    $isdashboardpage = (($PAGE->pagetype ?? '') === 'my-index');

    $editingon = $PAGE->user_is_editing();

    // Course-scope actions — only when inside an actual course (id > 1).
    if ($COURSE->id > 1 && in_array($layout, ['course', 'incourse', 'report'], true)) {
        $coursecontext = context_course::instance($COURSE->id);

        if (has_capability('moodle/course:manageactivities', $coursecontext)) {
            // Course edit-mode toggle (activities + resources).
            $items[] = [
                'key'     => 'edit_mode',
                'icon'    => $editingon ? 'check' : 'pencil',
                'label'   => $editingon ? get_string('turneditingoff') : get_string('turneditingon'),
                'url'     => (new moodle_url('/course/view.php', [
                    'id'      => $COURSE->id,
                    'sesskey' => sesskey(),
                    'edit'    => $editingon ? 'off' : 'on',
                ]))->out(false),
                'active'  => $editingon,
                'divider' => false,
            ];

            // Participants list.
            $items[] = [
                'key'     => 'participants',
                'icon'    => 'users',
                'label'   => get_string('participants'),
                'url'     => (new moodle_url('/user/index.php', ['id' => $COURSE->id]))->out(false),
                'active'  => false,
                'divider' => false,
            ];

            // Gradebook.
            $items[] = [
                'key'     => 'gradebook',
                'icon'    => 'bar-chart',
                'label'   => get_string('grades'),
                'url'     => (new moodle_url('/grade/report/grader/index.php', ['id' => $COURSE->id]))->out(false),
                'active'  => false,
                'divider' => false,
            ];

            // Course settings.
            $items[] = [
                'key'     => 'course_settings',
                'icon'    => 'cog',
                'label'   => get_string('editsettings'),
                'url'     => (new moodle_url('/course/edit.php', ['id' => $COURSE->id]))->out(false),
                'active'  => false,
                'divider' => false,
            ];
        }
    }

    // Block editing toggle — shown on any page where blocks can be edited
    // (dashboard, frontpage, course pages, etc.). Separate from course edit mode
    // because blocks exist in non-course contexts (e.g. /my/ dashboard) where
    // there are no activities to edit. Both use $PAGE->user_is_editing() state
    // because Moodle's edit preference covers both activity and block editing.
    if ($PAGE->user_can_edit_blocks()) {
        $blockediturl = new moodle_url($PAGE->url, [
            'sesskey' => sesskey(),
            'edit'    => $editingon ? 'off' : 'on',
        ]);
        $items[] = [
            'key'     => 'block_editing',
            'icon'    => 'pen-to-square',
            'label'   => $editingon
                    ? get_string('dockblocksoff', 'theme_lernhive')
                    : get_string('dockblockson', 'theme_lernhive'),
            'url'     => $blockediturl->out(false),
            'active'  => $editingon,
            'divider' => false,
        ];
    }

    // Site-admin dashboard controls:
    // 1) On /my/, jump into the default-dashboard editor.
    // 2) On /my/indexsys.php while editing, expose "save for everyone" in the dock.
    if (is_siteadmin() && $isdashboardpage && $pagepath === '/my/index.php') {
        $items[] = [
            'key'     => 'default_dashboard_edit',
            'icon'    => 'cog',
            'label'   => get_string('mypage', 'admin'),
            'url'     => (new moodle_url('/my/indexsys.php', [
                'sesskey' => sesskey(),
                'edit' => 'on',
            ]))->out(false),
            'active'  => false,
            'divider' => false,
        ];
    }

    if (is_siteadmin() && $isdashboardpage && $pagepath === '/my/indexsys.php' && $editingon) {
        $items[] = [
            'key'     => 'default_dashboard_save_all',
            'icon'    => 'save',
            'label'   => get_string('reseteveryonesdashboard', 'my'),
            'url'     => (new moodle_url('/my/indexsys.php', [
                'resetall' => 1,
                'sesskey'  => sesskey(),
            ]))->out(false),
            'active'  => false,
            'divider' => !empty($items),
        ];
    }

    // Site-admin shortcut — only on non-admin pages so admins can jump quickly.
    if (is_siteadmin() && $layout !== 'admin') {
        $items[] = [
            'key'     => 'site_admin',
            'icon'    => 'shield',
            'label'   => get_string('administrationsite'),
            'url'     => (new moodle_url('/admin/index.php'))->out(false),
            'active'  => false,
            'divider' => !empty($items), // separator when other items precede it
        ];
    }

    return $items;
}

/**
 * Build the Side Panel items for the header dock.
 *
 * Returns the list of dock triggers + their panel payloads. Each entry renders
 * one icon button in the top-right dock plus a hidden <template> that is
 * injected into the shared #lh-sidepanel container when the button is clicked.
 *
 * v1 scope (0.9.36): Messages + Notifications panels show a stub empty state
 * with a CTA to the existing Moodle full pages. Real popover content
 * (unread lists, threads, live counts) is wired in a follow-up release.
 * AI Assistant is a "coming soon" stub. Help panel shows a curated link list.
 *
 * Returns an empty array when the user is not logged in — the dock is
 * hidden entirely on the login page and for guests.
 *
 * @return array<int, array<string, mixed>>
 */
function theme_lernhive_get_sidepanel_items(): array {
    if (!isloggedin() || isguestuser()) {
        return [];
    }

    // --- Inline SVG markup for each dock icon (Lucide-style, stroke-only). ---
    $iconmessage = '<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">'
        . '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>'
        . '</svg>';
    $iconbell = '<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">'
        . '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>'
        . '<path d="M13.73 21a2 2 0 0 1-3.46 0"/>'
        . '</svg>';
    $iconai = '<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">'
        . '<path d="M12 2a4 4 0 0 0-4 4c0 1 .5 2 1 3L5 12l4 4 3-3 3 3 4-4-4-3c.5-1 1-2 1-3a4 4 0 0 0-4-4z"/>'
        . '</svg>';
    $iconhelp = '<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">'
        . '<circle cx="12" cy="12" r="10"/>'
        . '<path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>'
        . '<line x1="12" y1="17" x2="12.01" y2="17"/>'
        . '</svg>';

    // --- URL targets (use core Moodle paths as the "full view" fallback). ---
    $messagesurl = (new moodle_url('/message/index.php'))->out(false);
    $notificationsurl = (new moodle_url('/message/output/popup/notifications.php'))->out(false);
    $preferencesurl = (new moodle_url('/user/preferences.php'))->out(false);
    $docsbase = rtrim(get_docs_url(''), '/');

    // --- Panel definitions. -------------------------------------------------
    $items = [];

    // 1. Messages.
    $items[] = [
        'key'           => 'messages',
        'label'         => get_string('messages', 'theme_lernhive'),
        'size'          => '',
        'badge'         => false, // v1: no live count (wired in next release).
        'badgedot'      => false,
        'iconsvg'       => $iconmessage,
        'title'         => get_string('messages', 'theme_lernhive'),
        'subtitle'      => get_string('messages_sub', 'theme_lernhive'),
        'emptytext'     => get_string('messages_empty', 'theme_lernhive'),
        'primaryurl'    => $messagesurl,
        'primarylabel'  => get_string('messages_openfull', 'theme_lernhive'),
        'secondaryurl'  => '',
        'secondarylabel' => '',
        'helplinks'     => [],
    ];

    // 2. Notifications.
    $items[] = [
        'key'           => 'notifications',
        'label'         => get_string('notifications', 'theme_lernhive'),
        'size'          => '',
        'badge'         => false,
        'badgedot'      => false,
        'iconsvg'       => $iconbell,
        'title'         => get_string('notifications', 'theme_lernhive'),
        'subtitle'      => get_string('notifications_sub', 'theme_lernhive'),
        'emptytext'     => get_string('notifications_empty', 'theme_lernhive'),
        'primaryurl'    => $notificationsurl,
        'primarylabel'  => get_string('notifications_openfull', 'theme_lernhive'),
        'secondaryurl'  => $preferencesurl,
        'secondarylabel' => get_string('notifications_prefs', 'theme_lernhive'),
        'helplinks'     => [],
    ];

    // 3. AI Assistant (stub in v1 — infrastructure for future KI integration).
    $items[] = [
        'key'           => 'aiassistant',
        'label'         => get_string('aiassistant', 'theme_lernhive'),
        'size'          => '',
        'badge'         => '·',
        'badgedot'      => true,
        'iconsvg'       => $iconai,
        'title'         => get_string('aiassistant', 'theme_lernhive'),
        'subtitle'      => get_string('aiassistant_sub', 'theme_lernhive'),
        'emptytext'     => get_string('aiassistant_empty', 'theme_lernhive'),
        'primaryurl'    => '',
        'primarylabel'  => '',
        'secondaryurl'  => '',
        'secondarylabel' => '',
        'helplinks'     => [],
    ];

    // 4. Help — curated link list.
    $helplinks = [
        [
            'title' => get_string('help_startguide', 'theme_lernhive'),
            'desc'  => get_string('help_startguide_desc', 'theme_lernhive'),
            'url'   => $docsbase ?: 'https://docs.moodle.org',
        ],
        [
            'title' => get_string('help_dashboard', 'theme_lernhive'),
            'desc'  => get_string('help_dashboard_desc', 'theme_lernhive'),
            'url'   => (new moodle_url('/my/'))->out(false),
        ],
        [
            'title' => get_string('help_preferences', 'theme_lernhive'),
            'desc'  => get_string('help_preferences_desc', 'theme_lernhive'),
            'url'   => $preferencesurl,
        ],
    ];
    $items[] = [
        'key'           => 'help',
        'label'         => get_string('help', 'theme_lernhive'),
        'size'          => '',
        'badge'         => false,
        'badgedot'      => false,
        'iconsvg'       => $iconhelp,
        'title'         => get_string('help', 'theme_lernhive'),
        'subtitle'      => get_string('help_sub', 'theme_lernhive'),
        'emptytext'     => '',
        'primaryurl'    => '',
        'primarylabel'  => '',
        'secondaryurl'  => '',
        'secondarylabel' => '',
        'helplinks'     => $helplinks,
        'hashelplinks'  => !empty($helplinks),
    ];

    // Fill in `hashelplinks` for the other panels so Mustache can switch on it.
    foreach ($items as &$item) {
        if (!array_key_exists('hashelplinks', $item)) {
            $item['hashelplinks'] = false;
        }
    }
    unset($item);

    return $items;
}

/**
 * Build context data for the page-header user section (avatar → profile link,
 * language selector, preferences, logout).
 *
 * Centralises the logic used by both drawers.php and admin.php so neither
 * layout file duplicates the code.
 *
 * @param core_renderer $OUTPUT The theme's core_renderer.
 * @return array<string, mixed>
 */
function theme_lernhive_get_header_user_context($OUTPUT): array {
    global $USER;

    if (!isloggedin() || isguestuser()) {
        return [
            'isloggedin'  => false,
            'haslangmenu' => false,
            'langmenu'    => '',
        ];
    }

    $langmenu = $OUTPUT->lang_menu();
    return [
        'isloggedin'   => true,
        'profileurl'   => (new moodle_url('/user/profile.php', ['id' => $USER->id]))->out(false),
        'useravatar'   => $OUTPUT->user_picture($USER, ['size' => 35, 'link' => false, 'class' => 'lernhive-avatar']),
        'logouturl'    => (new moodle_url('/login/logout.php', ['sesskey' => sesskey()]))->out(false),
        'prefsurl'     => (new moodle_url('/user/preferences.php'))->out(false),
        'langmenu'     => $langmenu,
        'haslangmenu'  => !empty($langmenu),
    ];
}

/**
 * Build the rendered block HTML and has-flags for every LernHive block region.
 *
 * Returns an array that can be merged into the Mustache template context, e.g.:
 *   [
 *     'contenttop'          => '<section ...>...</section>',
 *     'hascontenttop'       => true,
 *     'contentbottom'       => '',
 *     'hascontentbottom'    => false,
 *     ...
 *     'hasfooterblocks'     => true,  // any footer-* region has content
 *   ]
 *
 * Region keys intentionally use camel-lite (no dashes) so Mustache tags like
 * {{{ contenttop }}} / {{#hascontenttop}} work cleanly.
 *
 * @param core_renderer $output The theme's core_renderer (usually $OUTPUT).
 * @return array<string, mixed>
 */
function theme_lernhive_get_block_regions_context($output): array {
    $regions = [
        'content-top'     => 'contenttop',
        'content-bottom'  => 'contentbottom',
        'sidebar-bottom'  => 'sidebarbottom',
        'footer-left'     => 'footerleft',
        'footer-center'   => 'footercenter',
        'footer-right'    => 'footerright',
    ];

    $context = [];
    $hasfooter = false;

    foreach ($regions as $region => $key) {
        $html = $output->blocks($region);
        // data-block="_add_block" is the edit-mode "Add a block" button — it must
        // NOT count as "has blocks" or empty regions show as white cards in edit mode.
        // We only count real block instances (value never starts with underscore).
        $hasblocks = (bool) preg_match('/data-block="(?!_)[^"]/i', $html);
        $context[$key] = $html;
        $context['has' . $key] = $hasblocks;
        if ($hasblocks && strpos($region, 'footer-') === 0) {
            $hasfooter = true;
        }
    }

    $context['hasfooterblocks'] = $hasfooter;
    return $context;
}

/**
 * Build a Plugin Shell context for core Moodle pages so they render with the
 * same Zone A (sticky header) + Zone B (info bar) pattern that LernHive local
 * plugins use.
 *
 * Returns null when the current page is NOT a known core page — in that case
 * the layout template renders nothing extra and lets the page render as today.
 * LernHive local plugins that own their own Plugin Shell output (ContentHub,
 * Copy, …) are intentionally excluded: their pagetype is not in the whitelist,
 * so the theme never injects a second, duplicate header on top of theirs.
 *
 * Each returned context carries the same keys the `plugin_shell_header`
 * partial consumes: `name`, `tagline`, `subtitle`, zero-or-more `tags`, and a
 * `hint` string for Zone B. Strings are resolved to the current language via
 * get_string() up front — the partial stays purely presentational.
 *
 * Handled core pagetypes (0.9.40+):
 *   - my-index           → /my/                  (Dashboard)
 *   - my-courses         → /my/courses.php       (My courses)
 *   - user-profile       → /user/profile.php     (Profile)
 *   - user-preferences   → /user/preferences.php (Preferences — admin layout)
 *
 * Note: some Moodle setups still expose /my/courses.php as pagetype "my-index".
 * We therefore resolve "mycourses" via URL path and pagelayout first, then fall
 * back to pagetype mapping.
 *
 * @param moodle_page $page The current $PAGE object.
 * @return array<string, mixed>|null Shell context, or null when not applicable.
 */
function theme_lernhive_get_plugin_shell_context(\moodle_page $page): ?array {
    $pagetype = $page->pagetype ?? '';
    $pagelayout = $page->pagelayout ?? '';
    $pagepath = $page->url ? $page->url->get_path() : '';

    // Resolve page family with robust fallbacks:
    // 1) my/courses.php by path/layout (some installs report pagetype my-index),
    // 2) explicit pagetype whitelist for everything else.
    if ($pagepath === '/my/courses.php' || $pagelayout === 'mycourses' || strpos($pagetype, 'my-courses') === 0) {
        $key = 'mycourses';
    } else {
        // Whitelist of core Moodle pagetypes that should receive the Plugin Shell
        // treatment. Each entry maps to the lang-string suffix used below.
        $map = [
            'my-index'         => 'dashboard',
            'user-profile'     => 'profile',
            'user-preferences' => 'preferences',
        ];
        if ($pagelayout === 'mydashboard') {
            $key = 'dashboard';
        } else if (isset($map[$pagetype])) {
            $key = $map[$pagetype];
        } else {
            return null;
        }
    }

    // Page-specific tag row. Keep it to one contextual pill per page — the
    // Plugin Shell SCSS (`_plugin-shell.scss`) caps tag density at 4 and the
    // guidance in the file header says "pair with icon where possible".
    $tags = [];
    switch ($key) {
        case 'dashboard':
            $tags[] = [
                'modifier' => 'type',
                'faicon'   => 'fa-home',
                'label'    => get_string('shell_tag_overview', 'theme_lernhive'),
            ];
            break;
        case 'mycourses':
            $tags[] = [
                'modifier' => 'type',
                'faicon'   => 'fa-graduation-cap',
                'label'    => get_string('shell_tag_courses', 'theme_lernhive'),
            ];
            break;
        case 'profile':
            $tags[] = [
                'modifier' => 'type',
                'faicon'   => 'fa-user',
                'label'    => get_string('shell_tag_account', 'theme_lernhive'),
            ];
            break;
        case 'preferences':
            $tags[] = [
                'modifier' => 'type',
                'faicon'   => 'fa-cog',
                'label'    => get_string('shell_tag_settings', 'theme_lernhive'),
            ];
            break;
    }

    return [
        'name'     => get_string('shell_name_' . $key, 'theme_lernhive'),
        'tagline'  => get_string('shell_tagline_' . $key, 'theme_lernhive'),
        'subtitle' => get_string('shell_subtitle_' . $key, 'theme_lernhive'),
        'hint'     => get_string('shell_hint_' . $key, 'theme_lernhive'),
        'tags'     => $tags,
        'hastags'  => !empty($tags),
    ];
}

/**
 * Build the LernHive primary navigation items ($navitems) used by all layouts
 * that render the left sidebar.
 *
 * This is the single source of truth for the primary nav — previously duplicated
 * in drawers.php and admin.php. Adding or removing items here updates every
 * layout at once, including the reduced course-sidebar variant which filters
 * this same list by key.
 *
 * Each item is an associative array consumed by sidebar.mustache:
 *   url, text, key, isactive, faicon
 *
 * @param moodle_page $page Current $PAGE (for pagelayout-aware active states).
 * @return array<int, array<string, mixed>>
 */
function theme_lernhive_get_primary_navitems(\moodle_page $page): array {
    $items = [];

    // Home — always visible.
    $items[] = [
        'url'      => (new moodle_url('/'))->out(false),
        'text'     => get_string('home'),
        'key'      => 'home',
        'isactive' => ($page->pagelayout === 'frontpage'),
        'faicon'   => 'home',
    ];

    if (isloggedin() && !isguestuser()) {
        // Dashboard.
        $items[] = [
            'url'      => (new moodle_url('/my/'))->out(false),
            'text'     => get_string('myhome'),
            'key'      => 'myhome',
            'isactive' => ($page->pagelayout === 'mydashboard'),
            'faicon'   => 'tachometer',
        ];
        // My Courses.
        $items[] = [
            'url'      => (new moodle_url('/my/courses.php'))->out(false),
            'text'     => get_string('mycourses'),
            'key'      => 'mycourses',
            'isactive' => ($page->pagelayout === 'mycourses'),
            'faicon'   => 'graduation-cap',
        ];
        // Explore (Discovery) — only when the local plugin is installed.
        // Guard with class_exists so the theme stays usable on sites where the
        // plugin has not been deployed yet (e.g. fresh upstream clones).
        if (get_string_manager()->string_exists('pluginname', 'local_lernhive_discovery')) {
            $items[] = [
                'url'      => (new moodle_url('/local/lernhive_discovery/index.php'))->out(false),
                'text'     => get_string('pluginname', 'local_lernhive_discovery'),
                'key'      => 'explore',
                'isactive' => (strpos($page->pagetype ?? '', 'local-lernhive_discovery') === 0),
                'faicon'   => 'compass',
            ];
        }
    }

    if (is_siteadmin()) {
        // Site Administration.
        $items[] = [
            'url'      => (new moodle_url('/admin/index.php'))->out(false),
            'text'     => get_string('administrationsite'),
            'key'      => 'siteadmin',
            'isactive' => ($page->pagelayout === 'admin'),
            'faicon'   => 'cog',
        ];
    }

    return $items;
}

/**
 * Build the reduced course-sidebar context — a whitelisted subset of primary
 * navigation plus a divider-separated course navigation pulled from the core
 * course renderer.
 *
 * The whitelist keeps the reading flow tight on course pages: only the items
 * a learner realistically needs while *inside* a course (jumping back to
 * Dashboard, the course list, or Explore) survive. Everything else is already
 * reachable via Header Dock / launcher, so we do not sacrifice navigation
 * density for focus.
 *
 * The course navigation itself is the core `courseindex_drawer` renderable —
 * the same structure Boost renders into its left-hand drawer. We render it
 * inline inside our fixed sidebar rather than in a toggleable drawer (see
 * Variant A decision in theme_lernhive docs/ADR-01).
 *
 * @param moodle_page $page Current $PAGE.
 * @return array<string, mixed> Context keys: reducednavitems, hascourseindex,
 *   courseindex (HTML string), coursenavlabel, primarynavlabel.
 */
function theme_lernhive_get_course_sidebar_context(\moodle_page $page): array {
    // Whitelist of primary-nav keys that survive on course pages. This is the
    // "Standard-Kürzel" variant chosen 2026-04-11: Dashboard + My Courses +
    // Explore — enough to escape the course cleanly, not enough to distract.
    $keep = ['myhome', 'mycourses', 'explore'];

    $allitems = theme_lernhive_get_primary_navitems($page);
    $reduced = array_values(array_filter($allitems, static function ($item) use ($keep) {
        return in_array($item['key'] ?? '', $keep, true);
    }));

    // Render the core course index via the CANONICAL drawer path. This is
    // what `core_course_drawer()` in /course/lib.php does internally, and
    // what Boost uses in theme/boost/layout/drawers.php. The drawer itself
    // is an AMD-hydrated placeholder (see core_courseformat/local/courseindex
    // /drawer.mustache) — `course_index_drawer()` calls `include_course_editor
    // ()`, which registers the js_call_amd('core_courseformat/courseeditor',
    // 'setViewFormat', ...) call on $PAGE->requires. The placeholder then
    // gets filled client-side by the drawer AMD module via the
    // `core_course_get_state` webservice.
    //
    // Guard note (0.9.51 fix): on our course.php layout, $page->course is
    // *not* reliably set to the active course at the time this helper runs,
    // even though require_login($course) has already executed in /course/
    // view.php. The empirically-verified workaround is to fall back to the
    // $COURSE global, which require_login() sets synchronously. When the
    // diag instrumentation was live we saw status=using-global-course fire
    // on every successful render; $page->course reports as an object with
    // id=1 (SITE) instead of the real course id.
    global $COURSE;
    $courseindexhtml = '';
    $course = null;
    if (!empty($page->course) && is_object($page->course) && !empty($page->course->id) && $page->course->id > SITEID) {
        $course = $page->course;
    } else if (isset($COURSE) && is_object($COURSE) && !empty($COURSE->id) && $COURSE->id > SITEID) {
        $course = $COURSE;
    }

    if ($course !== null) {
        try {
            $format = course_get_format($course);
            $renderer = $format->get_renderer($page);
            if (method_exists($renderer, 'course_index_drawer')) {
                $courseindexhtml = (string) $renderer->course_index_drawer($format);
            }
        } catch (\Throwable $e) {
            // Fail soft — an empty course index is always better than a
            // fatal course page. The divider is suppressed downstream via
            // hascourseindex when this happens. The debugging() output
            // lands in Moodle's debug log when developer debug is on.
            debugging(
                'theme_lernhive: course index render failed — ' . $e->getMessage(),
                DEBUG_DEVELOPER
            );
            $courseindexhtml = '';
        }
    }

    return [
        'reducednavitems' => $reduced,
        'courseindex'     => $courseindexhtml,
        'hascourseindex'  => trim($courseindexhtml) !== '',
        'primarynavlabel' => get_string('primarynavigation', 'theme_lernhive'),
        'coursenavlabel'  => get_string('coursenavigation', 'theme_lernhive'),
    ];
}
