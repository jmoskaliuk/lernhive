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
 * Renderable for the ContentHub entry page.
 *
 * @package    local_lernhive_contenthub
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_contenthub\output;

use local_lernhive_contenthub\card_registry;
use renderable;
use renderer_base;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 * Hub page renderable.
 *
 * Builds the template context for templates/hub_page.mustache.
 * There is no user input on this page: cards are a pure projection
 * of installed sibling plugins + static lang strings.
 */
class hub_page implements renderable, templatable {

    /**
     * Build the mustache context.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $cards = [];
        $available_count = 0;
        foreach (card_registry::get_cards() as $card) {
            $ctx = $card->to_template_context();
            $cards[] = $ctx;
            if ($ctx['available']) {
                $available_count++;
            }
        }

        return [
            // Plugin Shell Zone A.
            'backurl'         => (new \moodle_url('/my/'))->out(false),
            'tagline'         => get_string('shell_tagline', 'local_lernhive_contenthub'),
            'subtitle'        => get_string('shell_subtitle', 'local_lernhive_contenthub'),
            'available_count' => $available_count,
            // Cards.
            'cards'           => $cards,
            // Legacy keys kept for backward compatibility.
            'heading'         => get_string('page_heading', 'local_lernhive_contenthub'),
            'intro'           => get_string('page_intro', 'local_lernhive_contenthub'),
        ];
    }
}
