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
 * Corporate Academy flavour profile (experimental stub).
 *
 * Inherits from School for now. Corporate Academy specifics —
 * particularly around compliance training, mandatory enrolment patterns
 * and audience-driven rollout — are still an open product decision
 * (see product/07-next-steps-and-decisions.md).
 *
 * @package    local_lernhive_flavour
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_flavour\profile;

use local_lernhive_flavour\flavour_definition;

defined('MOODLE_INTERNAL') || die();

/**
 * Corporate Academy profile — stub, inherits School defaults.
 */
class corporate_profile extends school_profile {

    #[\Override]
    public function get_key(): string {
        return 'corporate';
    }

    #[\Override]
    public function get_label(): string {
        return get_string('flavour_corporate', 'local_lernhive_flavour');
    }

    #[\Override]
    public function get_description(): string {
        return get_string('flavour_corporate_desc', 'local_lernhive_flavour');
    }

    #[\Override]
    public function get_icon(): string {
        return '🏢';
    }

    #[\Override]
    public function get_maturity(): string {
        return flavour_definition::MATURITY_EXPERIMENTAL;
    }
}
