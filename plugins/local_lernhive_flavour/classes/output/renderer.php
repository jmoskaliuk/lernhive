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
 * Plugin renderer for local_lernhive_flavour.
 *
 * @package    local_lernhive_flavour
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_flavour\output;

use plugin_renderer_base;

defined('MOODLE_INTERNAL') || die();

/**
 * Flavour plugin renderer.
 */
class renderer extends plugin_renderer_base {

    /**
     * Render the main flavour picker page.
     *
     * @param flavour_picker $picker
     * @return string
     */
    public function render_flavour_picker(flavour_picker $picker): string {
        return $this->render_from_template(
            'local_lernhive_flavour/flavour_picker',
            $picker->export_for_template($this)
        );
    }

    /**
     * Render the diff confirm dialog.
     *
     * @param flavour_diff $diff
     * @return string
     */
    public function render_flavour_diff(flavour_diff $diff): string {
        return $this->render_from_template(
            'local_lernhive_flavour/flavour_diff',
            $diff->export_for_template($this)
        );
    }
}
