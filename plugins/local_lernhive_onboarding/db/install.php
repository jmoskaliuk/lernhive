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
 * LernHive Onboarding plugin installation hook.
 *
 * @package    local_lernhive_onboarding
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * LernHive Onboarding plugin installation function.
 *
 * Seeds default tour categories, imports Level 1 tours and provisions the
 * dedicated `lernhive_trainer` role used by the dashboard banner gate.
 *
 * Note on capability registration: Moodle's upgrade runner calls
 * update_capabilities() for a plugin AFTER its db/install.php has
 * finished. That is fine for the typical install hook, but
 * trainer_role::ensure() calls assign_capability() on our own
 * `local/lernhive_onboarding:receivelearningpath`, which is defined
 * in db/access.php and therefore not yet present in the
 * mdl_capabilities table when install.php runs. On upgrade the row
 * already exists so it goes unnoticed — on a fresh install
 * (e.g. phpunit init) assign_capability() raises a coding_exception
 * "Capability ... was not found!".
 *
 * Fix: explicitly invoke update_capabilities() for this plugin first,
 * which reads our db/access.php and inserts the row. The call is
 * idempotent — it is a no-op if Moodle runs it again a few frames
 * later during the normal upgrade flow.
 */
function xmldb_local_lernhive_onboarding_install() {
    update_capabilities('local_lernhive_onboarding');

    \local_lernhive_onboarding\tour_importer::seed_categories();
    \local_lernhive_onboarding\tour_importer::import_level(1);
    \local_lernhive_onboarding\tour_importer::import_level(2);
    \local_lernhive_onboarding\trainer_role::ensure();

    // Provision the "Onboarding Sandbox" course that {DEMOCOURSEID}
    // resolves to. Idempotent — on fresh install this creates the
    // course, on reinstall (config wiped, course retained) it rewires
    // the plugin config key. See classes/sandbox_course.php for why
    // we keep the course around even on uninstall.
    \local_lernhive_onboarding\sandbox_course::ensure();

    // Pre-fill the admin-configurable "trainer course category" setting
    // with a sensible default (first visible top-level category). Admins
    // can re-point it later via Site administration → Plugins → Local
    // plugins → LernHive Onboarding.
    \local_lernhive_onboarding\sandbox_course::seed_trainer_category_default();
}
