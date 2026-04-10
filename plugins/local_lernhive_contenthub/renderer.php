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
 * Renderer for LernHive ContentHub.
 *
 * @package    local_lernhive_contenthub
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Plugin renderer.
 */
class local_lernhive_contenthub_renderer extends plugin_renderer_base {
    /**
     * Render the ContentHub page.
     *
     * @param \local_lernhive_contenthub\output\contenthub $contenthub
     * @return string
     */
    public function render_contenthub(\local_lernhive_contenthub\output\contenthub $contenthub): string {
        $context = $contenthub->export_for_template($this);

        if (theme_config::load('lernhive')->name === 'lernhive') {
            return $this->render_from_template('theme_lernhive/contenthub_shell', $context);
        }

        return $this->render_from_template('local_lernhive_contenthub/contenthub', $context);
    }
}

