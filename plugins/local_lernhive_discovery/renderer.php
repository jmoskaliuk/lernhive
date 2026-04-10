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
 * Renderer for LernHive Discovery.
 *
 * @package    local_lernhive_discovery
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Plugin renderer.
 */
class local_lernhive_discovery_renderer extends plugin_renderer_base {
    /**
     * Render the Explore page.
     *
     * @param \local_lernhive_discovery\output\explore $explore
     * @return string
     */
    public function render_explore(\local_lernhive_discovery\output\explore $explore): string {
        $context = $explore->export_for_template($this);

        if (theme_config::load('lernhive')->name === 'lernhive') {
            return $this->render_from_template('theme_lernhive/explore_shell', $context);
        }

        return $this->render_from_template('local_lernhive_discovery/explore', $context);
    }
}

