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
 * Upgrade steps for local_lernhive_flavour.
 *
 * @package    local_lernhive_flavour
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade function.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_lernhive_flavour_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026041002) {

        // Introduce the flavour application audit trail table. Sites that
        // were running the pre-refactor 0.1.0 stub did not have any table;
        // this adds it cleanly.
        $table = new xmldb_table('local_lernhive_flavour_apps');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('flavour', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, null);
            $table->add_field('previous_flavour', XMLDB_TYPE_CHAR, '32', null, null, null, null);
            $table->add_field('applied_by', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timeapplied', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('settings_before', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('settings_after', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('overrides_detected', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('fk_applied_by', XMLDB_KEY_FOREIGN, ['applied_by'], 'user', ['id']);

            $table->add_index('ix_flavour_time', XMLDB_INDEX_NOTUNIQUE, ['flavour', 'timeapplied']);
            $table->add_index('ix_timeapplied', XMLDB_INDEX_NOTUNIQUE, ['timeapplied']);

            $dbman->create_table($table);
        }

        // Make sure the active_flavour config is populated. Sites that
        // installed the 0.1.0 stub already have it, but defending against
        // a missing value costs nothing.
        if (!get_config('local_lernhive_flavour', 'active_flavour')) {
            set_config('active_flavour', 'school', 'local_lernhive_flavour');
        }

        upgrade_plugin_savepoint(true, 2026041002, 'local', 'lernhive_flavour');
    }

    if ($oldversion < 2026041508) {
        // Code-only rollout:
        // - hook local_lernhive flavour feature presets into apply()
        // - expose get_feature_overrides() on flavour profiles
        upgrade_plugin_savepoint(true, 2026041508, 'local', 'lernhive_flavour');
    }

    return true;
}
