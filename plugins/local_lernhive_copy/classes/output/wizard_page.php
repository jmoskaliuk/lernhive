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
 *     pre-rendered copy form is passed in, the template shows the form.
 *  2. Template source: the ContentHub "Template" card lands here. This
 *     renders a template picker backed by local_lernhive_library.
 */
class wizard_page implements renderable, templatable {

    /**
     * @param source      $source   The content source the wizard is serving.
     * @param string|null $formhtml Pre-rendered form HTML for the simple
     *                              or expert copy flow, or null.
     * @param array       $viewstate Additional context values prepared by
     *                               index.php (mode links, template list, etc.).
     */
    public function __construct(
        private readonly source $source,
        private readonly ?string $formhtml = null,
        private readonly array $viewstate = [],
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
        $mode = $this->normalise_mode((string) ($this->viewstate['mode'] ?? 'simple'));
        $showstub = !$hasform && empty($this->viewstate['showtemplatelist']);

        return [
            'istemplate'       => $this->source->is_template(),
            'hasform'          => $hasform,
            'formhtml'         => $this->formhtml ?? '',
            'modeissimple'     => $mode === 'simple',
            'modeisexpert'     => $mode === 'expert',
            'showmodetoggle'   => (bool) ($this->viewstate['showmodetoggle'] ?? false),
            'simpleurl'        => (string) ($this->viewstate['simpleurl'] ?? ''),
            'experturl'        => (string) ($this->viewstate['experturl'] ?? ''),
            'hasreturnurl'     => !empty($this->viewstate['returnurl']),
            'returnurl'        => (string) ($this->viewstate['returnurl'] ?? ''),
            'showtemplatelist' => (bool) ($this->viewstate['showtemplatelist'] ?? false),
            'templateempty'    => (bool) ($this->viewstate['templateempty'] ?? false),
            'templateentries'  => (array) ($this->viewstate['templateentries'] ?? []),
            'active_template'  => (string) ($this->viewstate['activetemplate'] ?? ''),
            'heading'          => get_string('page_title_' . $suffix, 'local_lernhive_copy'),
            'intro'            => get_string('page_intro_' . $suffix, 'local_lernhive_copy'),
            'mode_simple'      => get_string('mode_simple', 'local_lernhive_copy'),
            'mode_simple_desc' => get_string('mode_simple_desc', 'local_lernhive_copy'),
            'mode_expert'      => get_string('mode_expert', 'local_lernhive_copy'),
            'mode_expert_desc' => get_string('mode_expert_desc', 'local_lernhive_copy'),
            'cta_simple'       => get_string('mode_cta_simple', 'local_lernhive_copy'),
            'cta_expert'       => get_string('mode_cta_expert', 'local_lernhive_copy'),
            'returnlabel'      => get_string('return_to_contenthub', 'local_lernhive_copy'),
            'active_template_label' => get_string('active_template', 'local_lernhive_copy'),
            'template_heading' => get_string('template_catalog_heading', 'local_lernhive_copy'),
            'template_empty'   => get_string('template_catalog_empty', 'local_lernhive_copy'),
            'template_meta_version' => get_string('template_meta_version', 'local_lernhive_copy'),
            'template_meta_updated' => get_string('template_meta_updated', 'local_lernhive_copy'),
            'template_meta_language' => get_string('template_meta_language', 'local_lernhive_copy'),
            'template_select'  => get_string('template_select', 'local_lernhive_copy'),
            'template_unavailable' => get_string('template_unavailable', 'local_lernhive_copy'),
            'template_unavailable_hint' => get_string('template_unavailable_hint', 'local_lernhive_copy'),
            'template_warning' => (string) ($this->viewstate['templatewarning'] ?? ''),
            'showstub'         => $showstub,
            'notice'           => (string) (
                $this->viewstate['noticeoverride'] ?? get_string('not_implemented', 'local_lernhive_copy')
            ),
        ];
    }

    /**
     * @param string $mode
     * @return string
     */
    private function normalise_mode(string $mode): string {
        return $mode === 'expert' ? 'expert' : 'simple';
    }
}
