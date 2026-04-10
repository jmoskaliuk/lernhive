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
 * Admin settings for local_lernhive_flavour.
 *
 * The flavour picker itself lives at admin_flavour.php — we only
 * register it here as an external admin page. We intentionally do NOT
 * expose a plain "active_flavour" configselect here because switching
 * flavours requires the diff confirm flow, which the picker implements.
 *
 * @package    local_lernhive_flavour
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    $ADMIN->add('localplugins', new admin_category(
        'local_lernhive_flavour_category',
        get_string('pluginname', 'local_lernhive_flavour')
    ));

    $ADMIN->add('local_lernhive_flavour_category', new admin_externalpage(
        'local_lernhive_flavour_setup',
        get_string('page_title', 'local_lernhive_flavour'),
        new moodle_url('/local/lernhive_flavour/admin_flavour.php'),
        'local/lernhive_flavour:manage'
    ));
}
