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
 * Renderable for the copy wizard entry page.
 *
 * @package    local_lernhive_copy
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_copy\output;

use local_lernhive_copy\source;
use renderable;
use renderer_base;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 * Wizard entry page.
 *
 * R1 scope: render two mode tiles (Simple / Expert) plus an explicit
 * "not yet implemented" notice. The actual wizard that wires this
 * page to Moodle's backup/restore API is tracked in
 * `docs/04-tasks.md` of the copy plugin.
 */
class wizard_page implements renderable, templatable {

    /**
     * @param source $source The content source the wizard is serving.
     */
    public function __construct(private readonly source $source) {
    }

    /**
     * Build the mustache context.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $suffix = $this->source->string_suffix();
        return [
            'istemplate'       => $this->source->is_template(),
            'heading'          => get_string('page_title_' . $suffix, 'local_lernhive_copy'),
            'intro'            => get_string('page_intro_' . $suffix, 'local_lernhive_copy'),
            'mode_simple'      => get_string('mode_simple', 'local_lernhive_copy'),
            'mode_simple_desc' => get_string('mode_simple_desc', 'local_lernhive_copy'),
            'mode_expert'      => get_string('mode_expert', 'local_lernhive_copy'),
            'mode_expert_desc' => get_string('mode_expert_desc', 'local_lernhive_copy'),
            'cta_simple'       => get_string('mode_cta_simple', 'local_lernhive_copy'),
            'cta_expert'       => get_string('mode_cta_expert', 'local_lernhive_copy'),
            'notice'           => get_string('not_implemented', 'local_lernhive_copy'),
        ];
    }
}
