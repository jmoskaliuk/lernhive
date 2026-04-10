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
 * Renderable ContentHub output model.
 *
 * @package    local_lernhive_contenthub
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_contenthub\output;

use renderer_base;
use renderable;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderable ContentHub view model.
 */
class contenthub implements renderable, templatable {
    /**
     * Export template context.
     *
     * @param renderer_base $output
     * @return array<string, mixed>
     */
    public function export_for_template(renderer_base $output): array {
        return [
            'tiles' => [
                [
                    'tag' => get_string('tileseparate', 'local_lernhive_contenthub'),
                    'tagclass' => 'is-copy',
                    'title' => get_string('tilecopytitle', 'local_lernhive_contenthub'),
                    'summary' => get_string('tilecopysummary', 'local_lernhive_contenthub'),
                    'primaryactionlabel' => self::resolve_label('lernhive_copy', get_string('tilecopyaction', 'local_lernhive_contenthub')),
                    'primaryactionurl' => self::resolve_local_plugin_url('lernhive_copy'),
                ],
                [
                    'tag' => get_string('tiletemplatetag', 'local_lernhive_contenthub'),
                    'tagclass' => 'is-template',
                    'title' => get_string('tiletemplatetitle', 'local_lernhive_contenthub'),
                    'summary' => get_string('tiletemplatesummary', 'local_lernhive_contenthub'),
                    'primaryactionlabel' => null,
                ],
                [
                    'tag' => get_string('tilelibrarytag', 'local_lernhive_contenthub'),
                    'tagclass' => 'is-library',
                    'title' => get_string('tilelibrarytitle', 'local_lernhive_contenthub'),
                    'summary' => get_string('tilelibrarysummary', 'local_lernhive_contenthub'),
                    'note' => get_string('tilelibrarynote', 'local_lernhive_contenthub'),
                    'primaryactionlabel' => self::resolve_label('lernhive_library', get_string('tilelibraryaction', 'local_lernhive_contenthub')),
                    'primaryactionurl' => self::resolve_local_plugin_url('lernhive_library'),
                ],
            ],
        ];
    }

    /**
     * Resolve a local plugin URL only when a readable entry file exists.
     *
     * @param string $pluginname
     * @param string $relativepath
     * @return string|null
     */
    protected static function resolve_local_plugin_url(string $pluginname, string $relativepath = 'index.php'): ?string {
        $plugindir = \core_component::get_plugin_directory('local', $pluginname);
        if (!$plugindir) {
            return null;
        }

        $fullpath = $plugindir . '/' . $relativepath;
        if (!is_readable($fullpath)) {
            return null;
        }

        return (new \moodle_url('/local/' . $pluginname . '/' . $relativepath))->out(false);
    }

    /**
     * Keep CTA labels only when a target plugin route exists.
     *
     * @param string $pluginname
     * @param string $label
     * @return string|null
     */
    protected static function resolve_label(string $pluginname, string $label): ?string {
        return self::resolve_local_plugin_url($pluginname) ? $label : null;
    }
}
