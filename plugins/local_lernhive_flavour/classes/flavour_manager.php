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
 * Flavour manager for LernHive Flavour plugin.
 *
 * @package    local_lernhive_flavour
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_flavour;

defined('MOODLE_INTERNAL') || die();

/**
 * Class flavour_manager
 * Manages flavour selection and default configuration.
 */
class flavour_manager {

    /**
     * List of available flavours.
     */
    const FLAVOURS = ['school', 'lxp'];

    /**
     * Get the currently active flavour.
     *
     * @return string The active flavour key (defaults to 'school').
     */
    public static function get_active_flavour(): string {
        return get_config('local_lernhive_flavour', 'active_flavour') ?: 'school';
    }

    /**
     * Set the active flavour and apply its defaults.
     *
     * @param string $flavour The flavour key to set.
     * @throws \invalid_parameter_exception If the flavour is invalid.
     */
    public static function set_flavour(string $flavour): void {
        if (!self::is_valid_flavour($flavour)) {
            throw new \invalid_parameter_exception("Invalid flavour: $flavour");
        }
        set_config('active_flavour', $flavour, 'local_lernhive_flavour');
        self::apply_flavour_defaults($flavour);
    }

    /**
     * Apply default configuration for a given flavour.
     *
     * @param string $flavour The flavour key.
     */
    public static function apply_flavour_defaults(string $flavour): void {
        // Common defaults for all flavours.
        set_config('show_levelbar', 1, 'local_lernhive');

        if ($flavour === 'school') {
            set_config('default_level', 1, 'local_lernhive');
            set_config('allow_course_creation', 1, 'local_lernhive');
            set_config('allow_user_creation', 1, 'local_lernhive');
        } else if ($flavour === 'lxp') {
            set_config('default_level', 1, 'local_lernhive');
            set_config('allow_course_creation', 0, 'local_lernhive');
            set_config('allow_user_creation', 0, 'local_lernhive');
        }
    }

    /**
     * Get the definition of a flavour (metadata, icon, label, description).
     *
     * @param string $flavour The flavour key.
     * @return array The flavour definition.
     */
    public static function get_flavour_definition(string $flavour): array {
        $definitions = [
            'school' => [
                'key'   => 'school',
                'icon'  => '🏫',
                'label' => get_string('flavour_school', 'local_lernhive_flavour'),
                'desc'  => get_string('flavour_school_desc', 'local_lernhive_flavour'),
            ],
            'lxp' => [
                'key'   => 'lxp',
                'icon'  => '🚀',
                'label' => get_string('flavour_lxp', 'local_lernhive_flavour'),
                'desc'  => get_string('flavour_lxp_desc', 'local_lernhive_flavour'),
            ],
        ];
        return $definitions[$flavour] ?? [];
    }

    /**
     * Check if a flavour is valid.
     *
     * @param string $flavour The flavour key to validate.
     * @return bool True if valid, false otherwise.
     */
    public static function is_valid_flavour(string $flavour): bool {
        return in_array($flavour, self::FLAVOURS, true);
    }
}
