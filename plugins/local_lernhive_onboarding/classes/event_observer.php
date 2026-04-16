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

namespace local_lernhive_onboarding;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observers for local_lernhive_onboarding.
 *
 * @package    local_lernhive_onboarding
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class event_observer {

    /**
     * Reset onboarding tour visibility cache when feature overrides change.
     *
     * @param \local_lernhive\event\feature_override_changed $event
     * @return void
     */
    public static function feature_override_changed(\local_lernhive\event\feature_override_changed $event): void {
        unset($event);
        tour_manager::reset_cache();
    }
}
