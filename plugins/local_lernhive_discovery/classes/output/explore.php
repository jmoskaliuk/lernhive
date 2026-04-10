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
 * Renderable Explore output model.
 *
 * @package    local_lernhive_discovery
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_discovery\output;

use moodle_url;
use renderer_base;
use renderable;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderable Explore view model.
 */
class explore implements renderable, templatable {
    /**
     * Export template context.
     *
     * @param renderer_base $output
     * @return array<string, mixed>
     */
    public function export_for_template(renderer_base $output): array {
        return [
            'eyebrow' => get_string('exploreeyebrow', 'local_lernhive_discovery'),
            'title' => get_string('pluginname', 'local_lernhive_discovery'),
            'summary' => get_string('exploresummary', 'local_lernhive_discovery'),
            'notetitle' => get_string('explorenotetitle', 'local_lernhive_discovery'),
            'note' => get_string('explorenote', 'local_lernhive_discovery'),
            'chips' => [
                get_string('chipcommunity', 'local_lernhive_discovery'),
                get_string('chipfollowed', 'local_lernhive_discovery'),
                get_string('chipsnacks', 'local_lernhive_discovery'),
                get_string('chiprecent', 'local_lernhive_discovery'),
            ],
            'sections' => [
                [
                    'sectionid' => 'explore-community',
                    'title' => get_string('sectioncommunitytitle', 'local_lernhive_discovery'),
                    'description' => get_string('sectioncommunitydesc', 'local_lernhive_discovery'),
                    'cards' => [
                        $this->build_card(
                            get_string('typecommunity', 'local_lernhive_discovery'),
                            get_string('cardcommunitytitle', 'local_lernhive_discovery'),
                            get_string('cardcommunitysummary', 'local_lernhive_discovery'),
                            get_string('cardcommunitymeta', 'local_lernhive_discovery'),
                            true,
                            true
                        ),
                    ],
                ],
                [
                    'sectionid' => 'explore-followed',
                    'title' => get_string('sectionfollowedtitle', 'local_lernhive_discovery'),
                    'description' => get_string('sectionfolloweddesc', 'local_lernhive_discovery'),
                    'cards' => [
                        $this->build_card(
                            get_string('typecourse', 'local_lernhive_discovery'),
                            get_string('cardcoursetitle', 'local_lernhive_discovery'),
                            get_string('cardcoursesummary', 'local_lernhive_discovery'),
                            get_string('cardcoursemeta', 'local_lernhive_discovery'),
                            true,
                            false
                        ),
                    ],
                ],
                [
                    'sectionid' => 'explore-snacks',
                    'title' => get_string('sectionsnackstitle', 'local_lernhive_discovery'),
                    'description' => get_string('sectionsnacksdesc', 'local_lernhive_discovery'),
                    'cards' => [
                        $this->build_card(
                            get_string('typesnack', 'local_lernhive_discovery'),
                            get_string('cardsnacktitle', 'local_lernhive_discovery'),
                            get_string('cardsnacksummary', 'local_lernhive_discovery'),
                            get_string('cardsnackmeta', 'local_lernhive_discovery'),
                            false,
                            true
                        ),
                    ],
                ],
                [
                    'sectionid' => 'explore-more',
                    'title' => get_string('sectionmoretitle', 'local_lernhive_discovery'),
                    'description' => get_string('sectionmoredesc', 'local_lernhive_discovery'),
                    'cards' => [
                        $this->build_card(
                            get_string('typecourse', 'local_lernhive_discovery'),
                            get_string('cardmoretitle', 'local_lernhive_discovery'),
                            get_string('cardmoresummary', 'local_lernhive_discovery'),
                            get_string('cardmoremeta', 'local_lernhive_discovery'),
                            false,
                            false
                        ),
                    ],
                ],
            ],
        ];
    }

    /**
     * Build one Explore card.
     *
     * @param string $type
     * @param string $title
     * @param string $summary
     * @param string $meta
     * @param bool $follow
     * @param bool $bookmark
     * @return array<string, mixed>
     */
    protected function build_card(
        string $type,
        string $title,
        string $summary,
        string $meta,
        bool $follow,
        bool $bookmark
    ): array {
        return [
            'type' => $type,
            'title' => $title,
            'summary' => $summary,
            'meta' => $meta,
            'follow' => $follow,
            'bookmark' => $bookmark,
            'primaryactionlabel' => get_string('cardaction', 'local_lernhive_discovery'),
            'primaryactionurl' => (new moodle_url('/local/lernhive_discovery/index.php'))->out(false),
        ];
    }
}

