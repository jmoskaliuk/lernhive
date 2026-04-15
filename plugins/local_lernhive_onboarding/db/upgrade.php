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
 * LernHive Onboarding — upgrade steps.
 *
 * @package    local_lernhive_onboarding
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_local_lernhive_onboarding_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    // Fix user tours configdata: convert stdClass role filters to plain arrays.
    // Moodle 5.x requires role filter to be a plain array ["editingteacher"],
    // not an object {"values":["editingteacher"],"default":""}.
    if ($oldversion < 2026040302) {
        // Get all tour IDs that we manage (mapped in our onboarding map table).
        $maprecords = $DB->get_records('local_lhonb_map');
        $tourids = array_unique(array_column((array) $maprecords, 'tourid'));

        if (!empty($tourids)) {
            list($insql, $params) = $DB->get_in_or_equal($tourids, SQL_PARAMS_NAMED);
            $tours = $DB->get_records_select('tool_usertours_tours', "id {$insql}", $params);

            foreach ($tours as $tour) {
                $changed = false;
                $configdata = json_decode($tour->configdata, true);
                if (!is_array($configdata)) {
                    continue;
                }

                // Fix top-level role filter (old format).
                if (isset($configdata['role']) && is_object(json_decode(json_encode($configdata['role'])))) {
                    // It's an associative array with "values"/"default" — extract just the values.
                    if (isset($configdata['role']['values'])) {
                        $configdata['role'] = array_values($configdata['role']['values']);
                    } else {
                        $configdata['role'] = ['editingteacher'];
                    }
                    $changed = true;
                }

                // Also fix inside filtervalues wrapper.
                if (isset($configdata['filtervalues']['role'])) {
                    $roleval = $configdata['filtervalues']['role'];
                    if (is_array($roleval) && isset($roleval['values'])) {
                        $configdata['filtervalues']['role'] = array_values($roleval['values']);
                        $changed = true;
                    } elseif (is_object($roleval)) {
                        $arr = (array) $roleval;
                        if (isset($arr['values'])) {
                            $configdata['filtervalues']['role'] = array_values($arr['values']);
                        } else {
                            $configdata['filtervalues']['role'] = ['editingteacher'];
                        }
                        $changed = true;
                    }
                }

                if ($changed) {
                    $tour->configdata = json_encode($configdata);
                    $DB->update_record('tool_usertours_tours', $tour);
                }
            }
        }

        // Also fix ALL user tours (not just ours) to prevent site-wide breakage.
        $alltours = $DB->get_records('tool_usertours_tours');
        foreach ($alltours as $tour) {
            $configdata = json_decode($tour->configdata, true);
            if (!is_array($configdata)) {
                continue;
            }

            $changed = false;

            // Check top-level role.
            if (isset($configdata['role']) && !is_int(key($configdata['role']))) {
                if (isset($configdata['role']['values'])) {
                    $configdata['role'] = array_values($configdata['role']['values']);
                    $changed = true;
                }
            }

            // Check filtervalues.role.
            if (isset($configdata['filtervalues']['role']) && !is_int(key((array)$configdata['filtervalues']['role']))) {
                $rv = (array) $configdata['filtervalues']['role'];
                if (isset($rv['values'])) {
                    $configdata['filtervalues']['role'] = array_values($rv['values']);
                    $changed = true;
                }
            }

            if ($changed) {
                $tour->configdata = json_encode($configdata);
                $DB->update_record('tool_usertours_tours', $tour);
            }
        }

        upgrade_plugin_savepoint(true, 2026040302, 'local', 'lernhive_onboarding');
    }

    // 0.2.0 — Dedicated lernhive_trainer role + dashboard banner.
    if ($oldversion < 2026041200) {
        // Moodle's upgrade runner calls update_capabilities() for a plugin
        // AFTER db/upgrade.php has finished. That means a capability newly
        // added to db/access.php in this release (here:
        // `local/lernhive_onboarding:receivelearningpath`) is not yet in
        // mdl_capabilities when we call trainer_role::ensure() below, and
        // assign_capability() throws
        //   "Capability '…:receivelearningpath' was not found!"
        //
        // Same root cause as the install.php fix — see commit 4130693.
        // Calling update_capabilities() explicitly here is idempotent and
        // cheap, and matches the pattern in db/install.php so fresh
        // installs and upgrades both reach trainer_role::ensure() with
        // the capability row present.
        update_capabilities('local_lernhive_onboarding');

        // Idempotent: creates the role if missing, re-asserts context + cap.
        \local_lernhive_onboarding\trainer_role::ensure();

        upgrade_plugin_savepoint(true, 2026041200, 'local', 'lernhive_onboarding');
    }

    // 0.2.7 — LH-ONB-START-06: provision the "Onboarding Sandbox" course
    // so `{DEMOCOURSEID}` in tour start_urls resolves to a real, hidden,
    // non-production course instead of falling through to 0.
    //
    // NB: 0.2.5 and 0.2.6 were UX-only releases that bumped version.php
    // without adding any DB work, so sites already upgraded to 2026041206
    // still need this savepoint to run. That is why this step is gated
    // on < 2026041207, not < 2026041206.
    //
    // Idempotent: sandbox_course::ensure() short-circuits if the stored
    // course ID is still valid. On upgrades from 0.2.x (where no sandbox
    // existed) this creates the course exactly once.
    if ($oldversion < 2026041207) {
        \local_lernhive_onboarding\sandbox_course::ensure();
        upgrade_plugin_savepoint(true, 2026041207, 'local', 'lernhive_onboarding');
    }

    // 0.2.8 — LH-ONB-START-05: deterministic start_url backfill on
    // every Level-1 tour. The JSON files ship with the new
    // `start_url` key so fresh installs are covered by
    // tour_importer::import_level(1). For existing sites we need to
    // re-import the Level-1 tours with the new flag, otherwise old
    // DB rows keep their empty `lh_start_url` configdata slot and the
    // starttour_flow falls back to the pathmatch strip forever.
    //
    // Plus: seed the `trainercoursecategoryid` admin setting with a
    // sensible default so the `{TRAINERCOURSECATEGORYID}` placeholder
    // resolves to *something* even if the admin never opens the
    // plugin settings page after upgrade.
    if ($oldversion < 2026041208) {
        \local_lernhive_onboarding\sandbox_course::seed_trainer_category_default();
        \local_lernhive_onboarding\tour_importer::reimport_level(1);

        // The "announcements" tour moved from Level 1 → Level 2
        // (tracked as LH-ONB-START-08). On existing sites the tour row
        // still exists in `tool_usertours_tours` from the previous
        // Level-1 import — we keep the row (admins may have customised
        // the steps) but unmap it from the Level-1 `communication`
        // category so the LernHive Onboarding catalog stops advertising
        // it under Level 1. It will still play on any `/mod/forum/post.php`
        // page because its `pathmatch` is unchanged. LH-ONB-START-08 will
        // re-register the tour under the new Level-2 category pack once
        // that infra lands (together with the sandbox announcements
        // forum and `{SANDBOXANNOUNCEMENTSFORUMID}` placeholder).
        \local_lernhive_onboarding\tour_importer::unmap_tour_from_category(
            'LernHive: Ankündigungen',
            'communication'
        );

        upgrade_plugin_savepoint(true, 2026041208, 'local', 'lernhive_onboarding');
    }

    // 0.3.0-dev (FR-01) - add feature_id to local_lhonb_map so tours can be
    // linked to local_lernhive feature-registry IDs without relying on
    // directory-level heuristics.
    if ($oldversion < 2026041500) {
        $table = new xmldb_table('local_lhonb_map');

        $featureid = new xmldb_field(
            'feature_id',
            XMLDB_TYPE_CHAR,
            '128',
            null,
            null,
            null,
            null,
            'tourid'
        );
        if (!$dbman->field_exists($table, $featureid)) {
            $dbman->add_field($table, $featureid);
        }

        $featureidx = new xmldb_index('ix_featureid', XMLDB_INDEX_NOTUNIQUE, ['feature_id']);
        if (!$dbman->index_exists($table, $featureidx)) {
            $dbman->add_index($table, $featureidx);
        }

        upgrade_plugin_savepoint(true, 2026041500, 'local', 'lernhive_onboarding');
    }

    return true;
}
