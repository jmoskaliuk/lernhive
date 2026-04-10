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
 * LernHive upgrade steps.
 *
 * @package    local_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_local_lernhive_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026040318) {
        // Create local_lernhive_teacher_cats table (teacher → personal course category).
        $table = new xmldb_table('local_lernhive_teacher_cats');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('categoryid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('fk_userid', XMLDB_KEY_FOREIGN_UNIQUE, ['userid'], 'user', ['id']);
            $table->add_key('fk_categoryid', XMLDB_KEY_FOREIGN, ['categoryid'], 'course_categories', ['id']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026040318, 'local', 'lernhive');
    }

    if ($oldversion < 2026040322) {
        // Force capability re-registration. The initial install may have
        // failed (e.g. missing tour_cats table in old install.php), leaving
        // the plugin marked as installed but without its capabilities in the DB.
        update_capabilities('local_lernhive');
        upgrade_plugin_savepoint(true, 2026040322, 'local', 'lernhive');
    }

    if ($oldversion < 2026040323) {
        // Fix: Replace CAP_PROHIBIT with CAP_PREVENT for all LernHive role overrides.
        // CAP_PROHIBIT blocks even site admins, making the activity chooser empty.
        // We need to clear all old PROHIBIT entries so apply_level() can set PREVENT.
        $role = $DB->get_record('role', ['shortname' => 'lernhive_filter']);
        if ($role) {
            $context = \context_system::instance();
            // Remove ALL capability overrides for the lernhive_filter role.
            // They will be re-applied correctly (with CAP_PREVENT) on next login.
            $DB->delete_records('role_capabilities', [
                'roleid' => $role->id,
                'contextid' => $context->id,
            ]);
            // Clear the role cache so changes take effect.
            accesslib_clear_role_cache($role->id);
        }
        upgrade_plugin_savepoint(true, 2026040323, 'local', 'lernhive');
    }

    if ($oldversion < 2026040325) {
        // Switch from CAP_PREVENT back to CAP_PROHIBIT for the lernhive_filter role.
        // CAP_PREVENT does NOT override editingteacher's CAP_ALLOW, so it had no
        // effect. CAP_PROHIBIT is needed to actually block capabilities.
        // Admin users are protected by NOT assigning them the role (see apply_level).
        $role = $DB->get_record('role', ['shortname' => 'lernhive_filter']);
        if ($role) {
            $context = \context_system::instance();

            // Clear all old entries — they'll be re-applied with PROHIBIT on login.
            $DB->delete_records('role_capabilities', [
                'roleid' => $role->id,
                'contextid' => $context->id,
            ]);

            // Remove the role from site admins (they must never have it).
            $admins = get_admins();
            foreach ($admins as $admin) {
                role_unassign($role->id, $admin->id, $context->id);
            }

            accesslib_clear_role_cache($role->id);
        }

        // Also remove the lernhive_usercreator role from admins (cleanup).
        $ucrole = $DB->get_record('role', ['shortname' => 'lernhive_usercreator']);
        if ($ucrole) {
            $context = \context_system::instance();
            $admins = get_admins();
            foreach ($admins as $admin) {
                role_unassign($ucrole->id, $admin->id, $context->id);
            }
        }

        upgrade_plugin_savepoint(true, 2026040325, 'local', 'lernhive');
    }

    if ($oldversion < 2026040326) {
        // Clean up legacy "schulhive_filter" role from the old SchulHive plugin.
        // This role had CAP_PROHIBIT on moodle/course:manageactivities and
        // moodle/course:viewhiddensections, which completely blocked the
        // activity chooser for any user who still had it assigned.
        $oldrole = $DB->get_record('role', ['shortname' => 'schulhive_filter']);
        if ($oldrole) {
            $context = \context_system::instance();

            // Remove role assignment from ALL users.
            role_unassign_all(['roleid' => $oldrole->id]);

            // Remove all capability overrides.
            $DB->delete_records('role_capabilities', ['roleid' => $oldrole->id]);

            // Delete the role itself.
            delete_role($oldrole->id);
        }

        upgrade_plugin_savepoint(true, 2026040326, 'local', 'lernhive');
    }

    if ($oldversion < 2026040343) {
        // Register new browseusers capability.
        update_capabilities('local_lernhive');
        upgrade_plugin_savepoint(true, 2026040343, 'local', 'lernhive');
    }

    return true;
}
