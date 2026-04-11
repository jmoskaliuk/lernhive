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
 * The page has two rendering modes:
 *
 *  1. Course source: the ContentHub "Copy" card lands here. If a
 *     pre-rendered copy form is passed in, the template shows the form;
 *     otherwise it falls back to the mode-tile stub (useful if the
 *     form could not be built, e.g. during install checks).
 *  2. Template source: the ContentHub "Template" card lands here. This
 *     always shows the stub — template copy is scheduled for a later
 *     slice and has different semantics (see `docs/04-tasks.md`).
 */
class wizard_page implements renderable, templatable {

    /**
     * @param source      $source   The content source the wizard is serving.
     * @param string|null $formhtml Pre-rendered form HTML for the simple
     *                              copy flow, or null to show the stub.
     */
    public function __construct(
        private readonly source $source,
        private readonly ?string $formhtml = null,
    ) {
    }

    /**
     * Build the mustache context.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $suffix = $this->source->string_suffix();
        $hasform = $this->formhtml !== null && $this->formhtml !== '';

        return [
            'istemplate'       => $this->source->is_template(),
            'hasform'          => $hasform,
            'formhtml'         => $this->formhtml ?? '',
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
