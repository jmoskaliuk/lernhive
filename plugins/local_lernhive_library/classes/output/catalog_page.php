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
 * Renderable for the Library catalog page.
 *
 * @package    local_lernhive_library
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_library\output;

use local_lernhive_library\catalog;
use renderable;
use renderer_base;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 * Catalog page — lists available library courses.
 */
class catalog_page implements renderable, templatable {

    /**
     * @param catalog $catalog The catalog source to render.
     */
    public function __construct(private readonly catalog $catalog) {
    }

    /**
     * Build the mustache context.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $entries = [];
        foreach ($this->catalog->all() as $entry) {
            $entries[] = $entry->to_template_context();
        }

        return [
            'heading' => get_string('catalog_heading', 'local_lernhive_library'),
            'intro'   => get_string('catalog_intro', 'local_lernhive_library'),
            'empty'   => empty($entries),
            'emptymsg' => get_string('catalog_empty', 'local_lernhive_library'),
            'entries' => $entries,
            'labels'  => [
                'version'  => get_string('label_version', 'local_lernhive_library'),
                'updated'  => get_string('label_updated', 'local_lernhive_library'),
                'language' => get_string('label_language', 'local_lernhive_library'),
                'import'   => get_string('btn_import', 'local_lernhive_library'),
            ],
        ];
    }
}
