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
 * Release 1 launcher action provider.
 *
 * @package    local_lernhive_launcher
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_launcher;

use local_lernhive\course_manager;
use local_lernhive\level_manager;
use local_lernhive_contenthub\card_registry;

defined('MOODLE_INTERNAL') || die();

/**
 * Central action registry and visibility evaluation for the launcher.
 */
class action_provider {
    /**
     * Get visible launcher actions for the current user.
     *
     * @return action[]
     */
    public static function get_visible_actions(): array {
        $actions = array_filter([
            self::build_create_course_action(),
            self::build_contenthub_action(),
            self::build_reports_action(),
            self::build_create_snack_action(),
            self::build_create_community_action(),
        ]);

        usort($actions, static function(action $a, action $b): int {
            return $a->sortorder <=> $b->sortorder;
        });

        return array_values($actions);
    }

    /**
     * Build the Create course action when it is available.
     *
     * @return action|null
     */
    protected static function build_create_course_action(): ?action {
        global $USER;

        if (!class_exists(course_manager::class) || !course_manager::is_enabled()) {
            return null;
        }

        if (!class_exists(level_manager::class) || !level_manager::get_level_record((int)$USER->id)) {
            return null;
        }

        return new action(
            'create_course',
            get_string('actioncreatecourse', 'local_lernhive_launcher'),
            get_string('actioncreatecoursedesc', 'local_lernhive_launcher'),
            'book-open',
            course_manager::get_create_course_url((int)$USER->id),
            10
        );
    }

    /**
     * Build the ContentHub action when the owning plugin exists, the
     * current user is allowed to enter it, and at least one downstream
     * content path is actually reachable.
     *
     * Visibility follows the rule documented in docs/00-master.md and
     * docs/04-tasks.md (LH-LAUNCHER-02): hide when `local_lernhive_contenthub`
     * is missing, when the user lacks `local/lernhive_contenthub:view`,
     * or when `card_registry::has_available_cards()` reports no
     * AVAILABLE card — a ContentHub entry that would land on an
     * all-unavailable hub page is a dead end and must stay hidden.
     *
     * The level_manager gate intentionally does not apply here: the
     * ContentHub access capability is cloned from `moodle/course:create`
     * semantics, so course creators and teachers see it regardless of
     * whether a LernHive level record has been written for them yet.
     *
     * @return action|null
     */
    protected static function build_contenthub_action(): ?action {
        // 1. Owner plugin must be installed.
        $url = self::resolve_local_plugin_url('lernhive_contenthub');
        if (!$url) {
            return null;
        }

        // 2. Capability check — mirrors ContentHub's own index.php gate
        //    in system context. The downstream plugin enforces this for
        //    security; the launcher enforces it for visibility.
        $systemcontext = \core\context\system::instance();
        if (!has_capability('local/lernhive_contenthub:view', $systemcontext)) {
            return null;
        }

        // 3. At least one downstream content path must resolve to an
        //    AVAILABLE card. The launcher delegates this decision to
        //    ContentHub itself via the public helper so the rule stays
        //    in one place. `method_exists()` keeps us safe against an
        //    older ContentHub version that predates the helper.
        if (!class_exists(card_registry::class)
            || !method_exists(card_registry::class, 'has_available_cards')
            || !card_registry::has_available_cards()
        ) {
            return null;
        }

        return new action(
            'contenthub',
            get_string('actioncontenthub', 'local_lernhive_launcher'),
            get_string('actioncontenthubdesc', 'local_lernhive_launcher'),
            'layout-grid',
            $url,
            20
        );
    }

    /**
     * Build the Reports action when the reporting plugin and permission exist.
     *
     * @return action|null
     */
    protected static function build_reports_action(): ?action {
        $url = self::resolve_local_plugin_url('lernhive_reporting');
        if (!$url) {
            return null;
        }

        $systemcontext = \core\context\system::instance();
        if (!has_capability('local/lernhive_reporting:view', $systemcontext)) {
            return null;
        }

        return new action(
            'reports',
            get_string('actionreports', 'local_lernhive_launcher'),
            get_string('actionreportsdesc', 'local_lernhive_launcher'),
            'chart-bar',
            $url,
            30
        );
    }

    /**
     * Build the Snack shortcut when a target exists.
     *
     * @return action|null
     */
    protected static function build_create_snack_action(): ?action {
        $url = self::resolve_local_plugin_url('lernhive_discovery', 'createsnack.php');
        if (!$url) {
            return null;
        }

        return new action(
            'create_snack',
            get_string('actioncreatesnack', 'local_lernhive_launcher'),
            get_string('actioncreatesnackdesc', 'local_lernhive_launcher'),
            'circle-play',
            $url,
            40
        );
    }

    /**
     * Build the Community shortcut when a target exists.
     *
     * @return action|null
     */
    protected static function build_create_community_action(): ?action {
        $url = self::resolve_local_plugin_url('lernhive_discovery', 'createcommunity.php');
        if (!$url) {
            return null;
        }

        return new action(
            'create_community',
            get_string('actioncreatecommunity', 'local_lernhive_launcher'),
            get_string('actioncreatecommunitydesc', 'local_lernhive_launcher'),
            'users',
            $url,
            50
        );
    }

    /**
     * Resolve a target URL only when the owning local plugin file exists.
     *
     * @param string $pluginname Local plugin shortname without the `local_` prefix.
     * @param string $relativepath Relative file path inside the plugin.
     * @return \moodle_url|null
     */
    protected static function resolve_local_plugin_url(string $pluginname, string $relativepath = 'index.php'): ?\moodle_url {
        $plugindir = \core_component::get_plugin_directory('local', $pluginname);
        if (!$plugindir) {
            return null;
        }

        $fullpath = $plugindir . '/' . $relativepath;
        if (!is_readable($fullpath)) {
            return null;
        }

        return new \moodle_url('/local/' . $pluginname . '/' . $relativepath);
    }
}
