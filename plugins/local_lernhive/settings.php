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
 * LernHive settings.
 *
 * @package    local_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    // Create a new category for LernHive.
    $ADMIN->add('localplugins', new admin_category(
        'local_lernhive_category',
        get_string('pluginname', 'local_lernhive')
    ));

    // Link to the dashboard.
    $ADMIN->add('local_lernhive_category', new admin_externalpage(
        'local_lernhive_dashboard',
        get_string('admin_dashboard', 'local_lernhive'),
        new moodle_url('/local/lernhive/admin_dashboard.php'),
        'local/lernhive:managelevel'
    ));

    // Settings page.
    $settings = new admin_settingpage(
        'local_lernhive_settings',
        get_string('settings', 'local_lernhive')
    );

    if ($ADMIN->fulltree) {

        // ── Level Settings ──────────────────────────────────────────

        $settings->add(new admin_setting_heading(
            'local_lernhive/heading_levels',
            get_string('setting_heading_levels', 'local_lernhive'),
            ''
        ));

        // Default level for new teachers.
        $settings->add(new admin_setting_configselect(
            'local_lernhive/default_level',
            get_string('setting_default_level', 'local_lernhive'),
            get_string('setting_default_level_desc', 'local_lernhive'),
            1,
            [
                1 => get_string('level_explorer', 'local_lernhive'),
                2 => get_string('level_creator', 'local_lernhive'),
                3 => get_string('level_pro', 'local_lernhive'),
                4 => get_string('level_expert', 'local_lernhive'),
                5 => get_string('level_master', 'local_lernhive'),
            ]
        ));

        // Show level bar to teachers.
        $settings->add(new admin_setting_configcheckbox(
            'local_lernhive/show_levelbar',
            get_string('setting_show_levelbar', 'local_lernhive'),
            get_string('setting_show_levelbar_desc', 'local_lernhive'),
            1
        ));

        // ── Feature: Course Creation ────────────────────────────────

        $settings->add(new admin_setting_heading(
            'local_lernhive/heading_course_creation',
            get_string('setting_heading_course_creation', 'local_lernhive'),
            ''
        ));

        // Allow teachers to create courses.
        $settings->add(new admin_setting_configcheckbox(
            'local_lernhive/allow_teacher_course_creation',
            get_string('setting_allow_course_creation', 'local_lernhive'),
            get_string('setting_allow_course_creation_desc', 'local_lernhive'),
            0
        ));

        // Parent category for teacher course areas.
        // Build a list of available categories.
        $catlist = [0 => get_string('setting_parent_category_top', 'local_lernhive')];
        if (class_exists('core_course_category')) {
            $categories = \core_course_category::make_categories_list();
            foreach ($categories as $catid => $catname) {
                $catlist[$catid] = $catname;
            }
        }

        $settings->add(new admin_setting_configselect(
            'local_lernhive/teacher_category_parent',
            get_string('setting_parent_category', 'local_lernhive'),
            get_string('setting_parent_category_desc', 'local_lernhive'),
            0,
            $catlist
        ));

        // ── Feature: User Creation ──────────────────────────────────

        $settings->add(new admin_setting_heading(
            'local_lernhive/heading_user_creation',
            get_string('setting_heading_user_creation', 'local_lernhive'),
            ''
        ));

        // Allow teachers to create users.
        $settings->add(new admin_setting_configcheckbox(
            'local_lernhive/allow_teacher_user_creation',
            get_string('setting_allow_user_creation', 'local_lernhive'),
            get_string('setting_allow_user_creation_desc', 'local_lernhive'),
            0
        ));

        // ── Feature: User Browsing ─────────────────────────────────

        $settings->add(new admin_setting_heading(
            'local_lernhive/heading_user_browse',
            get_string('setting_heading_user_browse', 'local_lernhive'),
            ''
        ));

        // Allow teachers to browse the user list.
        $settings->add(new admin_setting_configcheckbox(
            'local_lernhive/allow_teacher_user_browse',
            get_string('setting_allow_user_browse', 'local_lernhive'),
            get_string('setting_allow_user_browse_desc', 'local_lernhive'),
            0
        ));
    }

    $ADMIN->add('local_lernhive_category', $settings);

    // Feature-level override UI (LH-CORE-FR-05).
    $levelconfig = new admin_settingpage(
        'local_lernhive_level_configuration',
        get_string('setting_level_configuration', 'local_lernhive'),
        'local/lernhive:managelevel'
    );

    if ($ADMIN->fulltree) {
        $levelconfig->add(new admin_setting_heading(
            'local_lernhive/heading_level_configuration',
            get_string('setting_heading_level_configuration', 'local_lernhive'),
            get_string('setting_heading_level_configuration_desc', 'local_lernhive')
        ));

        $lastcategory = '';
        $stringmanager = get_string_manager();

        $levellabels = [
            1 => get_string('level_explorer', 'local_lernhive'),
            2 => get_string('level_creator', 'local_lernhive'),
            3 => get_string('level_pro', 'local_lernhive'),
            4 => get_string('level_expert', 'local_lernhive'),
            5 => get_string('level_master', 'local_lernhive'),
        ];

        foreach (\local_lernhive\feature\registry::get_features() as $featureid => $featuredef) {
            $categoryhint = (string) $featuredef->categoryhint;
            if ($categoryhint !== $lastcategory) {
                $levelconfig->add(new admin_setting_heading(
                    'local_lernhive/feature_group_' . $categoryhint,
                    get_string('setting_feature_group', 'local_lernhive', (object) ['category' => $categoryhint]),
                    ''
                ));
                $lastcategory = $categoryhint;
            }

            $label = $featureid;
            if ($stringmanager->string_exists($featuredef->langkey, 'local_lernhive')) {
                $label = get_string($featuredef->langkey, 'local_lernhive');
            }

            $safeid = preg_replace('/[^a-z0-9_]+/i', '_', $featureid);
            $settingname = 'local_lernhive/feature_override_' . $safeid;

            $levelconfig->add(new \local_lernhive\admin\feature_override_setting(
                $settingname,
                $label,
                get_string('setting_feature_override_desc', 'local_lernhive', (object) [
                    'featureid' => $featureid,
                    'defaultlevel' => $levellabels[(int) $featuredef->defaultlevel] ?? (string) $featuredef->defaultlevel,
                    'capability' => $featuredef->requiredcapability,
                ]),
                $featureid
            ));
        }
    }

    $ADMIN->add('local_lernhive_category', $levelconfig);
}
