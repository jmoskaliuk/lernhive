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
 * Post-install hook for local_lernhive_flavour.
 *
 * Sets 'school' as the initial active flavour so that freshly installed
 * LernHive sites have a sane default and the flavour_manager never
 * has to fall back on a hardcoded string.
 *
 * @package    local_lernhive_flavour
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Install hook.
 */
function xmldb_local_lernhive_flavour_install() {
    // Store the initial default flavour. We do NOT auto-apply its settings
    // here because local_lernhive may not have installed its settings yet
    // when this install hook runs. The admin is routed to the flavour
    // picker via local_lernhive_flavour_setup on first login.
    if (!get_config('local_lernhive_flavour', 'active_flavour')) {
        set_config('active_flavour', 'school', 'local_lernhive_flavour');
    }
}
