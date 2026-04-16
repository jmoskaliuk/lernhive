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
 * Per-request state for deterministic "start this exact tour" launches.
 *
 * @package    local_lernhive_onboarding
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_onboarding;

defined('MOODLE_INTERNAL') || die();

/**
 * Holds the forced tour ID for the current HTTP request.
 *
 * This state is intentionally request-local. Persistence between requests
 * happens through `$SESSION` and is consumed by hook_callbacks.
 */
final class forced_tour_state {
    /** @var int|null Request-local forced tour ID. */
    private static ?int $forcedtourid = null;

    /**
     * Set the forced tour ID for this request.
     *
     * @param int $tourid
     * @return void
     */
    public static function set_forced_tour_id(int $tourid): void {
        self::$forcedtourid = ($tourid > 0) ? $tourid : null;
    }

    /**
     * Get the forced tour ID for this request.
     *
     * @return int|null
     */
    public static function get_forced_tour_id(): ?int {
        return self::$forcedtourid;
    }
}

