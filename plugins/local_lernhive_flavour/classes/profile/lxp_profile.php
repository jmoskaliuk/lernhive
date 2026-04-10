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
 * LXP flavour profile.
 *
 * Tuned for Learning Experience Platform style installations: Explore
 * replaces the dashboard, teachers don't create users, and the level
 * bar is hidden because the LXP UX is discovery-driven rather than
 * progression-gated.
 *
 * @package    local_lernhive_flavour
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_flavour\profile;

use local_lernhive_flavour\flavour_definition;

defined('MOODLE_INTERNAL') || die();

/**
 * LXP profile — Explore-first experience with restricted teacher powers.
 */
class lxp_profile extends flavour_definition {

    #[\Override]
    public function get_key(): string {
        return 'lxp';
    }

    #[\Override]
    public function get_label(): string {
        return get_string('flavour_lxp', 'local_lernhive_flavour');
    }

    #[\Override]
    public function get_description(): string {
        return get_string('flavour_lxp_desc', 'local_lernhive_flavour');
    }

    #[\Override]
    public function get_icon(): string {
        return '🚀';
    }

    #[\Override]
    public function get_defaults(): array {
        return [
            'local_lernhive' => [
                // Levels still exist structurally, but the progression bar
                // is hidden because the LXP UX emphasises discovery over progression.
                'default_level'                 => 1,
                'show_levelbar'                 => 0,
                // Course creation is centralised in LXP scenarios — teachers
                // create Snacks through the Snack wizard, not full courses.
                'allow_teacher_course_creation' => 0,
                'teacher_category_parent'       => 0,
                // User management is a platform admin concern in LXP installs.
                'allow_teacher_user_creation'   => 0,
                'allow_teacher_user_browse'     => 0,
            ],
            // NOTE for R2: additional keys from local_lernhive_discovery,
            // local_lernhive_follow and local_lernhive_notifications will be
            // added here as those plugins ship. Do not pre-declare keys that
            // do not yet exist in a real plugin — flavour_manager will refuse
            // to set config for unknown components.
        ];
    }
}
