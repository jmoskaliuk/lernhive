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
 * Capability definitions for ContentHub.
 *
 * ContentHub is an entry UI. Access is granted to anyone who can
 * create courses: the cards themselves delegate to sibling plugins
 * and those plugins enforce their own capabilities.
 *
 * @package    local_lernhive_contenthub
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/lernhive_contenthub:view' => [
        'riskbitmask'  => 0,
        'captype'      => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [
            'editingteacher' => CAP_ALLOW,
            'coursecreator'  => CAP_ALLOW,
            'manager'        => CAP_ALLOW,
        ],
        // Reuse Moodle core capability semantics: whoever may create
        // a course on the site should be able to reach ContentHub.
        'clonepermissionsfrom' => 'moodle/course:create',
    ],
];
