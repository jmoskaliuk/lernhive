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
 * LernHive event observers.
 *
 * Registers observers for Moodle events to track user activities for the challenge system.
 * This is optional and can be extended in the future for semi-automatic level-up suggestions.
 *
 * @package    local_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    // Apply LernHive level restrictions on login.
    [
        'eventname' => '\core\event\user_loggedin',
        'callback' => '\local_lernhive\event_observer::user_loggedin',
        'internal' => true,
    ],
    // Observer for when a course module is created (e.g., new activity added).
    [
        'eventname' => '\core\event\course_module_created',
        'callback' => '\local_lernhive\event_observer::course_module_created',
        'internal' => true,
    ],
    // Observer for when a course module is updated.
    [
        'eventname' => '\core\event\course_module_updated',
        'callback' => '\local_lernhive\event_observer::course_module_updated',
        'internal' => true,
    ],
];
