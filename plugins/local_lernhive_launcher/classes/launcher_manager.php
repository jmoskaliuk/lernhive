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
 * Launcher context helper for theme and page integrations.
 *
 * @package    local_lernhive_launcher
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_launcher;

use local_lernhive_launcher\output\launcher;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper for building launcher template context outside the plugin page.
 */
class launcher_manager {
    /**
     * Build launcher context for a hosting theme shell.
     *
     * @param bool $isdock
     * @return array<string, mixed>
     */
    public static function get_theme_context(bool $isdock = false): array {
        global $PAGE;

        $renderable = new launcher(action_provider::get_visible_actions());
        $renderer = $PAGE->get_renderer('local_lernhive_launcher');
        $context = $renderable->export_for_template($renderer);
        $context['launcherisbase'] = !$isdock;
        $context['launcherisdock'] = $isdock;

        return $context;
    }
}
