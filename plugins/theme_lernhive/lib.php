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
 * Provides SCSS callbacks for the direct Boost child theme.
 *
 * @package    theme_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Get the pre-SCSS content.
 *
 * Injects SCSS variables BEFORE Boost's main SCSS is compiled.
 * This allows overriding Bootstrap and Boost variables (e.g. $primary, $body-bg).
 *
 * @param theme_config $theme The theme config object.
 * @return string Pre-SCSS content.
 */
function theme_lernhive_get_pre_scss($theme) {
    global $CFG;

    $prescss = '';

    // Inject our design tokens and Bootstrap variable overrides.
    $variablesfile = $CFG->dirroot . '/theme/lernhive/scss/lernhive/_variables.scss';
    if (file_exists($variablesfile)) {
        $prescss .= file_get_contents($variablesfile);
    }

    return $prescss;
}

/**
 * Get the extra SCSS content.
 *
 * Appended AFTER Boost's SCSS is compiled. This is where all
 * LernHive component styles (typography, buttons, cards, etc.) live.
 *
 * @param theme_config $theme The theme config object.
 * @return string Extra SCSS content.
 */
function theme_lernhive_get_extra_scss($theme) {
    global $CFG;

    $extrascss = '';

    // NOTE: Google Fonts Inter is loaded via <link> tag in
    // \theme_lernhive\hook_callbacks::before_standard_head_html().
    // Do NOT use @import url() here — it can cause the SCSS compiler
    // to silently fail on variable definitions that follow it.

    // Re-inject the variables WITHOUT !default — force-set them.
    // In Moodle 5.x, prescsscallback variables may NOT survive into
    // the extrascsscallback compilation context. Stripping !default
    // ensures our values are set unconditionally.
    $variablesfile = $CFG->dirroot . '/theme/lernhive/scss/lernhive/_variables.scss';
    if (file_exists($variablesfile)) {
        $vars = file_get_contents($variablesfile);
        // Strip !default so every variable is force-set in this context.
        $vars = str_replace(' !default', '', $vars);
        $extrascss .= "\n// --- Force-inject variables for extra SCSS ---\n";
        $extrascss .= $vars;
        $extrascss .= "\n";
    }

    // Load all LernHive component SCSS partials directly.
    // We can't use @import in extrascsscallback because the SCSS compiler
    // doesn't resolve paths relative to our theme's scss/ directory.
    $scssdir = $CFG->dirroot . '/theme/lernhive/scss/lernhive';
    $partials = [
        '_typography.scss',
        '_colors.scss',
        '_buttons.scss',
        '_cards.scss',
        '_navigation.scss',
        '_course.scss',
        '_dashboard.scss',
        '_login.scss',
        '_responsive.scss',
    ];

    foreach ($partials as $partial) {
        $filepath = $scssdir . '/' . $partial;
        if (file_exists($filepath)) {
            $extrascss .= "\n// --- " . $partial . " ---\n";
            $extrascss .= file_get_contents($filepath);
        }
    }

    // --- CSS Custom Properties override ---
    // Belt-and-suspenders: override Bootstrap 5's CSS custom properties
    // directly. This guarantees the LernHive design tokens apply even if
    // the SCSS variable overrides in prescsscallback were ignored by Boost.
    $extrascss .= <<<'CSSVARS'

// --- Bootstrap 5 CSS Custom Properties override (LernHive / eLeDia CI) ---
:root {
    // Brand colours (eLeDia).
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

    // eLeDia accent (custom property).
    --lh-accent: #f98012;
    --lh-accent-rgb: 249, 128, 18;

    // Body — white background on all pages.
    --bs-body-bg: #ffffff;
    --bs-body-bg-rgb: 255, 255, 255;
    --bs-body-color: #353535;
    --bs-body-color-rgb: 53, 53, 53;
    --bs-body-font-family: 'Atkinson Hyperlegible Next', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --bs-body-font-size: 1rem;

    // Links.
    --bs-link-color: #194866;
    --bs-link-color-rgb: 25, 72, 102;
    --bs-link-hover-color: #0f2d3f;
    --bs-link-hover-color-rgb: 15, 45, 63;

    // Border radius.
    --bs-border-radius: 8px;
    --bs-border-radius-sm: 6px;
    --bs-border-radius-lg: 12px;

    // Border colour.
    --bs-border-color: #e9e9e9;
}
CSSVARS;

    // Add any custom CSS from the LernHive admin settings.
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

// Font injection moved to \theme_lernhive\hook_callbacks::before_standard_head_html()
// using the Moodle 5.x hook system (core\hook\output\before_standard_head_html_generation).
// The legacy theme_lernhive_before_standard_html_head() function has been removed.
