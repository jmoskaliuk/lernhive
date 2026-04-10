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
 * Renderable for the flavour picker admin page.
 *
 * @package    local_lernhive_flavour
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_flavour\output;

use local_lernhive_flavour\flavour_manager;
use local_lernhive_flavour\flavour_registry;
use renderable;
use templatable;
use renderer_base;

defined('MOODLE_INTERNAL') || die();

/**
 * Picker renderable — one card per registered flavour.
 */
class flavour_picker implements renderable, templatable {

    /**
     * Build the template context.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $active = flavour_manager::get_active();
        $profiles = flavour_registry::all();

        $cards = [];
        foreach ($profiles as $key => $profile) {
            $isactive = ($key === $active);
            $hasoverrides = flavour_manager::has_pending_overrides($key);
            $cards[] = [
                'key'          => $key,
                'label'        => $profile->get_label(),
                'description'  => $profile->get_description(),
                'icon'         => $profile->get_icon(),
                'isactive'     => $isactive,
                'experimental' => $profile->get_maturity() === \local_lernhive_flavour\flavour_definition::MATURITY_EXPERIMENTAL,
                'overrides'    => $hasoverrides && !$isactive,
                'sesskey'      => sesskey(),
            ];
        }

        $activeprofile = flavour_registry::get($active);
        return [
            'intro'       => get_string('page_intro', 'local_lernhive_flavour'),
            'heading'     => get_string('page_title', 'local_lernhive_flavour'),
            'cards'       => $cards,
            'activelabel' => $activeprofile ? $activeprofile->get_label() : $active,
            'activekey'   => $active,
        ];
    }
}
