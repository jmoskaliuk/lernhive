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
        // Idempotent: creates the role if missing, re-asserts context + cap.
        \local_lernhive_onboarding\trainer_role::ensure();

        upgrade_plugin_savepoint(true, 2026041200, 'local', 'lernhive_onboarding');
    }

    return true;
}
