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
