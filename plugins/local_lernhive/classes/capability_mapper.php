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

namespace local_lernhive;

defined('MOODLE_INTERNAL') || die();

use local_lernhive\feature\definition;
use local_lernhive\feature\registry;

/**
 * Maps LernHive levels to Moodle capabilities.
 *
 * After LH-CORE-FR-04 this class is a pure consumer of the feature registry.
 * The legacy level map APIs are kept as compatibility shims for one cycle.
 *
 * @package    local_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capability_mapper {

    /**
     * Legacy shim: module unlock map grouped by level.
     *
     * @return array<int, array<int, string>> Level => module components.
     */
    public static function get_level_modules(): array {
        $bylevel = [];

        foreach (self::get_module_unlock_levels() as $module => $unlocklevel) {
            if ($unlocklevel < definition::MIN_LEVEL || $unlocklevel > definition::MAX_LEVEL) {
                continue;
            }
            $bylevel[$unlocklevel][] = $module;
        }

        ksort($bylevel);
        foreach ($bylevel as &$modules) {
            sort($modules);
        }
        unset($modules);

        return $bylevel;
    }

    /**
     * Legacy shim: non-module capability unlock map grouped by level.
     *
     * @return array<int, array<int, string>> Level => capabilities.
     */
    public static function get_level_capabilities(): array {
        $bylevel = [];

        foreach (self::get_capability_unlock_levels() as $capability => $unlocklevel) {
            if (self::capability_to_module_component($capability) !== null) {
                continue;
            }
            if ($unlocklevel < definition::MIN_LEVEL || $unlocklevel > definition::MAX_LEVEL) {
                continue;
            }
            $bylevel[$unlocklevel][] = $capability;
        }

        ksort($bylevel);
        foreach ($bylevel as &$caps) {
            sort($caps);
        }
        unset($caps);

        return $bylevel;
    }

    /**
     * Get all non-module capabilities that should be prohibited at this level.
     *
     * @param int $level LernHive level (1-5).
     * @return array<int, string>
     */
    public static function get_prohibited_capabilities(int $level): array {
        $prohibited = [];
        foreach (self::get_level_capabilities() as $unlocklevel => $caps) {
            if ($unlocklevel > $level) {
                $prohibited = array_merge($prohibited, $caps);
            }
        }
        return array_values(array_unique($prohibited));
    }

    /**
     * Get all non-module capabilities that are allowed at this level.
     *
     * @param int $level LernHive level (1-5).
     * @return array<int, string>
     */
    public static function get_allowed_capabilities(int $level): array {
        $allowed = [];
        foreach (self::get_level_capabilities() as $unlocklevel => $caps) {
            if ($unlocklevel <= $level) {
                $allowed = array_merge($allowed, $caps);
            }
        }
        return array_values(array_unique($allowed));
    }

    /**
     * Get all known non-module capabilities.
     *
     * @return array<int, string>
     */
    public static function get_all_capabilities(): array {
        $all = [];
        foreach (self::get_level_capabilities() as $caps) {
            $all = array_merge($all, $caps);
        }
        return array_values(array_unique($all));
    }

    /**
     * Get all modules allowed for a given level (cumulative).
     *
     * @param int $level LernHive level (1-5).
     * @return array<int, string> Module components.
     */
    public static function get_allowed_modules(int $level): array {
        $allowed = [];
        foreach (self::get_level_modules() as $unlocklevel => $modules) {
            if ($unlocklevel <= $level) {
                $allowed = array_merge($allowed, $modules);
            }
        }
        return array_values(array_unique($allowed));
    }

    /**
     * Build addinstance capability from module component.
     *
     * @param string $modcomponent Module component like mod_quiz.
     * @return string Capability string.
     */
    public static function get_addinstance_cap(string $modcomponent): string {
        $modname = str_replace('mod_', '', $modcomponent);
        return "mod/{$modname}:addinstance";
    }

    /**
     * Get all known module components.
     *
     * @return array<int, string>
     */
    public static function get_all_modules(): array {
        $all = [];
        foreach (self::get_level_modules() as $modules) {
            $all = array_merge($all, $modules);
        }
        return array_values(array_unique($all));
    }

    /**
     * Apply level capabilities for a user.
     *
     * Strategy: user gets dedicated `lernhive_filter` role with CAP_PROHIBIT
     * for all capabilities whose unlock-level is above the user's level.
     *
     * @param int $userid User id.
     * @param int $level LernHive level (1-5).
     */
    public static function apply_level(int $userid, int $level): void {
        global $DB;

        $context = \context_system::instance();
        $roleid = self::get_or_create_lernhive_role();

        if (is_siteadmin($userid)) {
            if (user_has_role_assignment($userid, $roleid, $context->id)) {
                role_unassign($roleid, $userid, $context->id);
            }
            return;
        }

        if (!user_has_role_assignment($userid, $roleid, $context->id)) {
            role_assign($roleid, $userid, $context->id);
        }

        $unlocklevels = self::get_capability_unlock_levels();

        // Clean up stale prohibitions from legacy maps so we stay single-source
        // with registry-driven capabilities.
        $existing = $DB->get_records(
            'role_capabilities',
            ['roleid' => $roleid, 'contextid' => $context->id],
            '',
            'id, capability, permission'
        );
        foreach ($existing as $record) {
            if ((int) $record->permission !== CAP_PROHIBIT) {
                continue;
            }
            if (!array_key_exists($record->capability, $unlocklevels)) {
                unassign_capability($record->capability, $roleid, $context->id);
            }
        }

        foreach ($unlocklevels as $capability => $unlocklevel) {
            if (!get_capability_info($capability)) {
                continue;
            }

            if ($unlocklevel <= $level) {
                unassign_capability($capability, $roleid, $context->id);
            } else {
                assign_capability($capability, CAP_PROHIBIT, $roleid, $context->id, true);
            }
        }
    }

    /**
     * Remove all LernHive restrictions for a user.
     *
     * @param int $userid User id.
     */
    public static function remove_restrictions(int $userid): void {
        $context = \context_system::instance();
        $roleid = self::get_or_create_lernhive_role();
        role_unassign($roleid, $userid, $context->id);
    }

    /**
     * Get or create the special LernHive role used for capability overrides.
     *
     * @return int Role id.
     */
    public static function get_or_create_lernhive_role(): int {
        global $DB;

        $role = $DB->get_record('role', ['shortname' => 'lernhive_filter']);
        if ($role) {
            return (int) $role->id;
        }

        $roleid = create_role(
            'LernHive Filter',
            'lernhive_filter',
            'Automatically managed by LernHive plugin to control feature visibility.',
            ''
        );

        set_role_contextlevels($roleid, [CONTEXT_SYSTEM]);
        return $roleid;
    }

    /**
     * Get modules that are locked (not yet available) for a given level.
     *
     * @param int $level Current level.
     * @return array<int, array{module: string, unlocks_at: int}>
     */
    public static function get_locked_modules(int $level): array {
        $locked = [];
        $levelmodules = self::get_level_modules();

        for ($i = $level + 1; $i <= level_manager::LEVEL_MAX; $i++) {
            if (!isset($levelmodules[$i])) {
                continue;
            }
            foreach ($levelmodules[$i] as $module) {
                $locked[] = [
                    'module' => $module,
                    'unlocks_at' => $i,
                ];
            }
        }

        return $locked;
    }

    /**
     * Resolve capability -> unlock level from the feature registry.
     *
     * If multiple features share one capability, the earliest unlock level wins.
     *
     * @return array<string, int> Capability => unlock level.
     */
    private static function get_capability_unlock_levels(): array {
        $map = [];

        foreach (registry::get_features() as $featureid => $definition) {
            $capability = $definition->requiredcapability;
            $unlocklevel = registry::effective_level($featureid);
            if (!isset($map[$capability]) || $unlocklevel < $map[$capability]) {
                $map[$capability] = $unlocklevel;
            }
        }

        return $map;
    }

    /**
     * Resolve module component -> unlock level from registry capabilities.
     *
     * @return array<string, int> Module component => unlock level.
     */
    private static function get_module_unlock_levels(): array {
        $map = [];

        foreach (self::get_capability_unlock_levels() as $capability => $unlocklevel) {
            $module = self::capability_to_module_component($capability);
            if ($module === null) {
                continue;
            }
            if (!isset($map[$module]) || $unlocklevel < $map[$module]) {
                $map[$module] = $unlocklevel;
            }
        }

        return $map;
    }

    /**
     * Convert addinstance capability to module component.
     *
     * @param string $capability Capability string.
     * @return string|null Module component or null for non-module capabilities.
     */
    private static function capability_to_module_component(string $capability): ?string {
        if (!preg_match('#^mod/([a-z0-9_]+):addinstance$#', $capability, $matches)) {
            return null;
        }
        return 'mod_' . $matches[1];
    }
}
