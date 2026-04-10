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
 * School flavour profile.
 *
 * This is the R1 default for classical LMS installations with teachers
 * who own their own courses and manage their learners.
 *
 * @package    local_lernhive_flavour
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_flavour\profile;

use local_lernhive_flavour\flavour_definition;

defined('MOODLE_INTERNAL') || die();

/**
 * School profile — default starting point for schools and classical LMS setups.
 */
class school_profile extends flavour_definition {

    #[\Override]
    public function get_key(): string {
        return 'school';
    }

    #[\Override]
    public function get_label(): string {
        return get_string('flavour_school', 'local_lernhive_flavour');
    }

    #[\Override]
    public function get_description(): string {
        return get_string('flavour_school_desc', 'local_lernhive_flavour');
    }

    #[\Override]
    public function get_icon(): string {
        return '🏫';
    }

    #[\Override]
    public function get_defaults(): array {
        return [
            'local_lernhive' => [
                // Level system: everybody starts as Explorer, the level bar is visible.
                'default_level'                 => 1,
                'show_levelbar'                 => 1,
                // Schools want teachers to own their courses.
                'allow_teacher_course_creation' => 1,
                // Teacher category auto-creation uses the site root by default.
                'teacher_category_parent'       => 0,
                // Teachers should be able to enrol/manage learners they teach.
                'allow_teacher_user_creation'   => 1,
                'allow_teacher_user_browse'     => 1,
            ],
        ];
    }
}
