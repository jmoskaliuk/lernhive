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
 * Event fired whenever a flavour is applied through flavour_manager.
 *
 * Consumers in R2 (particularly local_lernhive_configuration) listen
 * for this to build a configuration history view.
 *
 * @package    local_lernhive_flavour
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_flavour\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Flavour applied event.
 */
class flavour_applied extends \core\event\base {

    #[\Override]
    protected function init() {
        // "c" because we write a new audit row each time. The config writes
        // themselves are a side-effect we describe in get_description().
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'local_lernhive_flavour_apps';
    }

    /**
     * Localised event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('event_flavour_applied', 'local_lernhive_flavour');
    }

    /**
     * Human-readable description for the events log.
     *
     * @return string
     */
    #[\Override]
    public function get_description(): string {
        $flavour = $this->other['flavour'] ?? '?';
        $previous = $this->other['previous'] ?? '(none)';
        $overrides = !empty($this->other['overrides_detected']) ? ' (overriding prior settings)' : '';
        return "The user with id '{$this->userid}' applied LernHive flavour '{$flavour}' " .
               "(previously '{$previous}'){$overrides}.";
    }

    /**
     * URL that describes this event.
     *
     * @return \moodle_url
     */
    #[\Override]
    public function get_url(): \moodle_url {
        return new \moodle_url('/local/lernhive_flavour/admin_flavour.php');
    }

    /**
     * No 'other' data mapping — the flavour/previous keys are plain
     * strings, no DB ids inside.
     *
     * @return bool
     */
    public static function get_other_mapping() {
        return false;
    }
}
