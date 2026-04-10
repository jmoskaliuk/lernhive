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

namespace theme_lernhive;

use core\hook\output\before_standard_head_html_generation;

defined('MOODLE_INTERNAL') || die();

/**
 * Hook callbacks for the LernHive theme.
 *
 * @package    theme_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {

    /**
 * Inject Open Sans via HTML <link> tags into the page head.
     *
     * Replaces the legacy theme_lernhive_before_standard_html_head() callback
     * with the Moodle 5.x hook system.
     *
     * @param before_standard_head_html_generation $hook
     */
    public static function before_standard_head_html(
        before_standard_head_html_generation $hook
    ): void {
        $html = '<link rel="preconnect" href="https://fonts.googleapis.com">'
              . '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
              . '<link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,400;0,600;0,700;1,400;1,600&display=swap" rel="stylesheet">';

        $hook->add_html($html);
    }
}
