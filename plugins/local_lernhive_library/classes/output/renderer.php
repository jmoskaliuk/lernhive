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
 * Plugin renderer for local_lernhive_library.
 *
 * @package    local_lernhive_library
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_library\output;

use plugin_renderer_base;

defined('MOODLE_INTERNAL') || die();

/**
 * Library renderer.
 */
class renderer extends plugin_renderer_base {

    /**
     * Render the catalog page.
     *
     * @param catalog_page $page
     * @return string
     */
    public function render_catalog_page(catalog_page $page): string {
        return $this->render_from_template(
            'local_lernhive_library/catalog_page',
            $page->export_for_template($this)
        );
    }
}
