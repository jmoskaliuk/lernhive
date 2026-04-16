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
 * Admin settings page for the LernHive Onboarding plugin.
 *
 * Currently exposes a single setting — the target category for
 * "a trainer creates a new course" tours — but the settings category
 * is intentionally registered even for a one-setting page so that
 * follow-up tickets (BigBlueButton soft-dependency, auto-assignment
 * of the trainer role, chain-mode toggles) can land new controls
 * without having to rewire the admin tree.
 *
 * @package    local_lernhive_onboarding
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Build the category choice list for the admin settings dropdown.
 *
 * Returns a flat `[id => displayname]` map of every visible course
 * category on the site, in the same order Moodle itself presents them
 * on the category management page. Hidden categories are skipped so
 * admins cannot accidentally route trainers at a category that their
 * audience cannot see.
 *
 * Lives in settings.php (not a class) so it only loads when the admin
 * tree is being rendered — which is the only time we need it.
 *
 * admin/search.php may load plugin settings more than once while building
 * categories/tabs, so this helper must be guarded against redefinition.
 *
 * @return array<int, string>
 */
if (!function_exists('local_lernhive_onboarding_get_course_category_choices')) {
    function local_lernhive_onboarding_get_course_category_choices(): array {
        global $DB;

        $rows = $DB->get_records_select(
            'course_categories',
            'visible = 1',
            [],
            'path ASC, sortorder ASC, id ASC',
            'id, name, path'
        );

        $choices = [];
        foreach ($rows as $row) {
            // Indent by nesting depth so admins see the hierarchy.
            // path looks like `/1/5/12` — the number of segments minus
            // one is the depth.
            $depth = max(0, substr_count($row->path, '/') - 1);
            $choices[(int) $row->id] = str_repeat('— ', $depth) . format_string($row->name);
        }

        return $choices;
    }
}

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_lernhive_onboarding',
        new lang_string('pluginname', 'local_lernhive_onboarding')
    );

    // "Trainer course category" — resolved into the `{TRAINERCOURSECATEGORYID}`
    // placeholder by `start_url_resolver::resolve()`. Admins pick which
    // category the "create a new course" tour should land novice trainers
    // in, because on multi-tenant installs the default "Miscellaneous"
    // category is often hidden from trainers for policy reasons.
    $settings->add(new admin_setting_configselect(
        'local_lernhive_onboarding/trainercoursecategoryid',
        new lang_string('setting_trainercoursecategoryid', 'local_lernhive_onboarding'),
        new lang_string('setting_trainercoursecategoryid_desc', 'local_lernhive_onboarding'),
        1,
        local_lernhive_onboarding_get_course_category_choices()
    ));

    $ADMIN->add('localplugins', $settings);
}
