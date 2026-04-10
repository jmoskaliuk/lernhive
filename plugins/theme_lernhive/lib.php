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

/**
 * Returns the concatenated SCSS source for the theme.
 *
 * @param theme_config $theme
 * @return string
 */
function theme_lernhive_get_main_scss_content($theme): string {
    global $CFG;

    $parts = [];
    $pre = $CFG->dirroot . '/theme/lernhive/scss/pre.scss';
    $post = $CFG->dirroot . '/theme/lernhive/scss/post.scss';

    if (is_readable($pre)) {
        $parts[] = file_get_contents($pre);
    }
    if (is_readable($post)) {
        $parts[] = file_get_contents($post);
    }

    return implode("\n\n", $parts);
}

/**
 * Injects SCSS variables before the main stylesheet.
 *
 * @param theme_config $theme
 * @return string
 */
function theme_lernhive_get_pre_scss($theme): string {
    return <<<'SCSS'
$lernhive-white: #ffffff;
$lernhive-ink: #353535;
$lernhive-orange: #f98012;
$lernhive-blue: #194866;
$lernhive-blue-light: #65a1b3;
$lernhive-cool-gray: #f3f5f8;
$lernhive-line: #e9e9e9;
$lernhive-orange-soft: #ffecdb;
$lernhive-blue-soft: #a9cbd5;
SCSS;
}

/**
 * Injects theme utility SCSS after the main stylesheet.
 *
 * @param theme_config $theme
 * @return string
 */
function theme_lernhive_get_extra_scss($theme): string {
    return <<<'SCSS'
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
}
