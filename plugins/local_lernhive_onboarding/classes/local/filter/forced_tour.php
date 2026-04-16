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
 * One-request server-side tour filter for deterministic starts.
 *
 * @package    local_lernhive_onboarding
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_onboarding\local\filter;

use context;
use local_lernhive_onboarding\forced_tour_state;
use tool_usertours\local\filter\base;
use tool_usertours\tour;

defined('MOODLE_INTERNAL') || die();

/**
 * Limits matching tours to one concrete tour ID when forced-start is active.
 */
class forced_tour extends base {
    /**
     * The internal filter key (not user-configurable).
     *
     * @return string
     */
    public static function get_filter_name() {
        return 'lh_forced_tour';
    }

    /**
     * Match exactly one forced tour for the current request.
     *
     * @param tour $tour
     * @param context $context
     * @return bool
     */
    public static function filter_matches(tour $tour, context $context) {
        $forcedtourid = forced_tour_state::get_forced_tour_id();
        if ($forcedtourid === null) {
            // No forced launch this request -> keep default behaviour.
            return true;
        }
        return ((int) $tour->get_id() === $forcedtourid);
    }
}

