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
 * Higher Education flavour profile (experimental stub).
 *
 * Inherits its defaults from the School profile 1:1 for now — the
 * concrete Higher Ed specifics are still an open product decision
 * (see product/07-next-steps-and-decisions.md). Flagged as experimental
 * so the admin UI shows a warning badge and the apply action confirms
 * before proceeding.
 *
 * @package    local_lernhive_flavour
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_flavour\profile;

use local_lernhive_flavour\flavour_definition;

defined('MOODLE_INTERNAL') || die();

/**
 * Higher Education profile — stub, inherits School defaults.
 */
class highered_profile extends school_profile {

    #[\Override]
    public function get_key(): string {
        return 'highered';
    }

    #[\Override]
    public function get_label(): string {
        return get_string('flavour_highered', 'local_lernhive_flavour');
    }

    #[\Override]
    public function get_description(): string {
        return get_string('flavour_highered_desc', 'local_lernhive_flavour');
    }

    #[\Override]
    public function get_icon(): string {
        return '🎓';
    }

    #[\Override]
    public function get_maturity(): string {
        return flavour_definition::MATURITY_EXPERIMENTAL;
    }

    // get_defaults() is inherited from school_profile: same starting point
    // until Higher Ed specifics are defined in the product docs.
}
