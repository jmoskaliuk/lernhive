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
        '_icons.scss',       // 0.9.32: Icon Taxonomy — nav / artifact / action classes
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
 * Build the admin top-navigation array for the admin layout.
 *
 * Mirrors Boost's admin secondary navigation: a single flat tab bar with
 * the synthetic "General" landing tab (→ /admin/index.php) followed by
 * every visible direct admin_category child of admin_get_root() — Users,
 * Courses, Grades, Plugins, Appearance, Server, Reports, Development, …
 * No custom collapse, no second tab row. The Level-2 landing page
 * (/admin/category.php) and the Level-3 settings page
 * (/admin/settings.php) are rendered by Moodle core with their own
 * subcategory groupings, which is exactly what Boost Union-style admins
 * expect.
 *
 * Active-tab detection walks the admin tree recursively, so a settings
 * page reached via `?section=calendar` still highlights the correct
 * top-level tab (appearance) because `calendar` lives underneath it.
 *
 * The return shape keeps the legacy `adminsecondnav`/`hasadminsecondnav`
 * keys (always empty now) so template contexts that still reference them
 * do not blow up during the upgrade cycle.
 *
 * @param moodle_page $PAGE The current page object.
 * @return array{
 *   admintopnav: array<int, array<string, mixed>>,
 *   hasadmintopnav: bool,
 *   adminsecondnav: array<int, array<string, mixed>>,
 *   hasadminsecondnav: bool,
 * }
 */
function theme_lernhive_get_admin_topnav($PAGE): array {
    $empty = [
        'admintopnav'       => [],
        'hasadmintopnav'    => false,
        'adminsecondnav'    => [],
        'hasadminsecondnav' => false,
    ];

    if (!is_siteadmin() || !function_exists('admin_get_root')) {
        return $empty;
    }

    // Only render on actual admin pages (/admin/ URL path).
    // Plugins registered as admin_externalpage (e.g. ContentHub) still use
    // the admin layout but live under /local/ — they don't need the admin nav.
    $pagepath = parse_url($PAGE->url->out(false), PHP_URL_PATH) ?? '';
    if (strpos($pagepath, '/admin/') === false) {
        return $empty;
    }

    $adminroot = admin_get_root(false, false);
    if (!$adminroot) {
        return $empty;
    }

    $urlcategory = $PAGE->url->get_param('category') ?? '';
    $urlsection  = $PAGE->url->get_param('section')  ?? '';

    // Helper: check if a node is visible and accessible.
    $is_visible = function($node): bool {
        if (isset($node->hidden) && $node->hidden) {
            return false;
        }
        try {
            return $node->check_access();
        } catch (Exception $e) {
            return false;
        }
    };

    // Helper: resolve the display label of an admin node (handles lang_string).
    $node_label = function($node): string {
        $name = $node->visiblename ?? '';
        return $name instanceof lang_string ? $name->out() : (string) $name;
    };

    // Helper: build a nav item array.
    $make_item = function(string $key, string $text, string $url, bool $isactive): array {
        return ['key' => $key, 'text' => $text, 'url' => $url, 'isactive' => $isactive];
    };

    // Collect every visible top-level admin_category child of admin_get_root().
    // Moodle core decides the canonical sequence (Users → Courses → Grades →
    // Plugins → Appearance → Server → Reports → Development, …).
    $categories = [];
    foreach ($adminroot->children as $section) {
        if (!($section instanceof admin_category) || !$is_visible($section)) {
            continue;
        }
        $key = $section->name ?? '';
        if ($key === '') {
            continue;
        }
        $categories[$key] = $section;
    }

    // --- Recursive active detection --------------------------------------------
    // Given a top-level admin_category, walk its subtree and check whether the
    // requested URL param (`section` or nested `category`) lives inside it.
    $subtree_contains = function(admin_category $category, string $needle_section, string $needle_category) use (&$subtree_contains): bool {
        if ($needle_section === '' && $needle_category === '') {
            return false;
        }
        foreach ($category->children as $child) {
            $childkey = $child->name ?? '';
            if ($needle_section !== '' && $childkey === $needle_section) {
                return true;
            }
            if ($needle_category !== '' && $childkey === $needle_category) {
                return true;
            }
            if ($child instanceof admin_category && $subtree_contains($child, $needle_section, $needle_category)) {
                return true;
            }
        }
        return false;
    };

    // --- Determine which top-level tab is active -------------------------------
    // Priority:
    //   1. urlcategory matches a top-level category directly → that tab.
    //   2. urlcategory or urlsection lives anywhere inside a top-level
    //      category's subtree                               → that tab.
    //   3. nothing matches (admin/index.php, unknown page)  → synthetic 'general'.
    $activetopkey = 'general';

    if ($urlcategory !== '' && isset($categories[$urlcategory])) {
        $activetopkey = $urlcategory;
    } else if ($urlcategory !== '' || $urlsection !== '') {
        foreach ($categories as $ckey => $cnode) {
            if ($subtree_contains($cnode, $urlsection, $urlcategory)) {
                $activetopkey = $ckey;
                break;
            }
        }
    }

    // --- Build the flat Level-1 nav array --------------------------------------
    $admintopnav = [];

    // 1. Synthetic "General" landing tab — always first, points at admin/index.php.
    $admintopnav[] = $make_item(
        'general',
        get_string('general'),
        (new moodle_url('/admin/index.php'))->out(false),
        $activetopkey === 'general'
    );

    // 2. Every visible top-level admin_category, in Moodle core order.
    foreach ($categories as $ckey => $cnode) {
        $admintopnav[] = $make_item(
            $ckey,
            $node_label($cnode),
            (new moodle_url('/admin/category.php', ['category' => $ckey]))->out(false),
            $activetopkey === $ckey
        );
    }

    return [
        'admintopnav'       => $admintopnav,
        'hasadmintopnav'    => !empty($admintopnav),
        'adminsecondnav'    => [],
        'hasadminsecondnav' => false,
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
