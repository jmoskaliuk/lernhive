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

/**
 * Maps LernHive levels to Moodle module capabilities.
 *
 * Uses the activity chooser's built-in capability checking:
 * if a user lacks mod/xxx:addinstance, the module won't appear
 * in the activity chooser.
 *
 * @package    local_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capability_mapper {

    /**
     * Defines which modules are available at each level.
     * Each level includes all modules from previous levels.
     *
     * @return array Level => array of module component names.
     */
    public static function get_level_modules(): array {
        return [
            1 => [
                'mod_resource',     // Datei hochladen
                'mod_page',         // Textseite
                'mod_label',        // Textfeld / Label
                'mod_url',          // Link / URL
                'mod_forum',        // Ankündigungsforum (filtered by type)
                'mod_folder',       // Verzeichnis
            ],
            2 => [
                'mod_assign',       // Aufgabe
                // mod_forum already in level 1, but now full forum use
            ],
            3 => [
                'mod_quiz',         // Quiz / Test
                'mod_h5pactivity',  // H5P
                'mod_lesson',       // Lektion
            ],
            4 => [
                'mod_wiki',         // Wiki
                'mod_glossary',     // Glossar
                'mod_data',         // Datenbank
                'mod_workshop',     // Workshop (Peer-Bewertung)
            ],
            5 => [
                'mod_scorm',        // SCORM-Pakete
                'mod_lti',          // LTI (externe Tools)
                'mod_feedback',     // Feedback
                'mod_choice',       // Abstimmung
                'mod_chat',         // Chat
                'mod_survey',       // Umfrage
                'mod_book',         // Buch
                'mod_imscp',        // IMS Content Package
                'mod_subsection',   // Subsections (PROHIBIT < Level 5, so + opens Activity Chooser directly)
            ],
        ];
    }

    /**
     * Defines additional (non-module) capabilities to PROHIBIT at each level.
     * Each level REMOVES restrictions from previous levels (cumulative unlock).
     *
     * These capabilities control Moodle UI elements like navigation tabs,
     * section management, grades visibility, etc.
     *
     * @return array Level => array of capability strings to PROHIBIT.
     *               A capability listed at level N means it is PROHIBITED
     *               for levels < N and ALLOWED for levels >= N.
     */
    public static function get_level_capabilities(): array {
        return [
            // Level 1 (Explorer): these are PROHIBITED.
            // Unlocked at level 2:
            2 => [
                'moodle/grade:view',              // Hides Grades tab from navigation.
                'moodle/grade:viewall',           // Also needed to fully hide Grades.
                'moodle/site:viewreports',        // Hides Reports from navigation.
                'moodle/course:managegroups',      // Hides group management.
            ],
            // Unlocked at level 3:
            3 => [
                'moodle/grade:manage',             // Grade editing.
                'moodle/grade:edit',               // Grade editing.
                'moodle/course:enrolconfig',       // Enrolment method config.
            ],
            // Unlocked at level 4:
            4 => [
                // Dashboard customisation ("/my/" block editing).
                // Keep this hidden for Levels 1-3 to reduce setup complexity.
                'moodle/my:manageblocks',
            ],
            // Unlocked at level 5:
            5 => [
                'moodle/backup:backupcourse',     // Backup.
                'moodle/restore:restorecourse',   // Restore.
                'moodle/course:import',           // Course import.
            ],
            // NOTE: Do NOT prohibit these — they break core editing functionality:
            // - moodle/course:manageactivities — needed for Add activity button + Edit mode
            // - moodle/course:createsection — needed for basic section management
            // - moodle/course:viewhiddensections — needed for course editing
            // Instead, use JS/CSS to hide "Subsection" option in the + menu.
        ];
    }

    /**
     * Get all additional capabilities that should be PROHIBITED for a given level.
     *
     * @param int $level The LernHive level (1-5).
     * @return array Capability strings that should be PROHIBITED at this level.
     */
    public static function get_prohibited_capabilities(int $level): array {
        $levelcaps = self::get_level_capabilities();
        $prohibited = [];

        // Capabilities are PROHIBITED if they unlock at a level ABOVE the current one.
        foreach ($levelcaps as $unlockLevel => $caps) {
            if ($unlockLevel > $level) {
                $prohibited = array_merge($prohibited, $caps);
            }
        }

        return array_unique($prohibited);
    }

    /**
     * Get all additional capabilities that should be ALLOWED (unrestricted) for a given level.
     *
     * @param int $level The LernHive level (1-5).
     * @return array Capability strings that should be unrestricted at this level.
     */
    public static function get_allowed_capabilities(int $level): array {
        $levelcaps = self::get_level_capabilities();
        $allowed = [];

        foreach ($levelcaps as $unlockLevel => $caps) {
            if ($unlockLevel <= $level) {
                $allowed = array_merge($allowed, $caps);
            }
        }

        return array_unique($allowed);
    }

    /**
     * Get all additional capability strings across all levels.
     *
     * @return array All capability strings.
     */
    public static function get_all_capabilities(): array {
        $all = [];
        foreach (self::get_level_capabilities() as $caps) {
            $all = array_merge($all, $caps);
        }
        return array_unique($all);
    }

    /**
     * Get all modules allowed for a given level (cumulative).
     *
     * @param int $level The LernHive level (1-5).
     * @return array Module component names allowed at this level.
     */
    public static function get_allowed_modules(int $level): array {
        $levelmodules = self::get_level_modules();
        $allowed = [];

        for ($i = 1; $i <= $level; $i++) {
            if (isset($levelmodules[$i])) {
                $allowed = array_merge($allowed, $levelmodules[$i]);
            }
        }

        return array_unique($allowed);
    }

    /**
     * Get the addinstance capability for a module.
     *
     * @param string $modcomponent The module component name (e.g., 'mod_quiz').
     * @return string The capability string (e.g., 'mod/quiz:addinstance').
     */
    public static function get_addinstance_cap(string $modcomponent): string {
        $modname = str_replace('mod_', '', $modcomponent);
        return "mod/{$modname}:addinstance";
    }

    /**
     * Get all known module components across all levels.
     *
     * @return array All module component names.
     */
    public static function get_all_modules(): array {
        $all = [];
        foreach (self::get_level_modules() as $modules) {
            $all = array_merge($all, $modules);
        }
        return array_unique($all);
    }

    /**
     * Apply level capabilities for a user.
     *
     * Strategy: We use a dedicated `lernhive_filter` role with CAP_PROHIBIT
     * to block module addinstance capabilities. CAP_PROHIBIT is the ONLY
     * permission that overrides CAP_ALLOW from other roles (like editingteacher).
     *
     * IMPORTANT: Site admins NEVER get this role assigned — they bypass
     * capability checks via Moodle's $doanything mechanism. We remove the
     * role if an admin already has it.
     *
     * @param int $userid The user ID.
     * @param int $level The LernHive level (1-5).
     */
    public static function apply_level(int $userid, int $level): void {
        $context = \context_system::instance();
        $roleid = self::get_or_create_lernhive_role();

        // NEVER assign the filter role to site admins — CAP_PROHIBIT
        // would break their admin capabilities.
        if (is_siteadmin($userid)) {
            // Remove the role if it was previously assigned.
            if (user_has_role_assignment($userid, $roleid, $context->id)) {
                role_unassign($roleid, $userid, $context->id);
            }
            return;
        }

        $allowed = self::get_allowed_modules($level);
        $allmodules = self::get_all_modules();

        // Ensure user has the LernHive role assigned.
        if (!user_has_role_assignment($userid, $roleid, $context->id)) {
            role_assign($roleid, $userid, $context->id);
        }

        // --- 1. Module capabilities (addinstance) ---
        // We use CAP_PROHIBIT to override editingteacher's CAP_ALLOW.
        // CAP_PREVENT does NOT work here because editingteacher grants
        // CAP_ALLOW, which takes precedence over CAP_PREVENT from other roles.
        foreach ($allmodules as $mod) {
            $cap = self::get_addinstance_cap($mod);

            // Check if this capability actually exists in Moodle.
            if (!get_capability_info($cap)) {
                continue;
            }

            if (in_array($mod, $allowed)) {
                // Remove any restriction — let the base role's permission apply.
                unassign_capability($cap, $roleid, $context->id);
            } else {
                // PROHIBIT this capability — the only way to block editingteacher's ALLOW.
                assign_capability($cap, CAP_PROHIBIT, $roleid, $context->id, true);
            }
        }

        // --- 2. Additional capabilities (navigation, grades, sections, etc.) ---
        // Also use CAP_PROHIBIT for these — same reason.
        $prohibitedcaps = self::get_prohibited_capabilities($level);
        $allowedcaps = self::get_allowed_capabilities($level);

        foreach ($prohibitedcaps as $cap) {
            if (!get_capability_info($cap)) {
                continue;
            }
            assign_capability($cap, CAP_PROHIBIT, $roleid, $context->id, true);
        }

        foreach ($allowedcaps as $cap) {
            if (!get_capability_info($cap)) {
                continue;
            }
            // Remove restriction — let base role's permission apply.
            unassign_capability($cap, $roleid, $context->id);
        }

        // Note: assign_capability() and unassign_capability() already call
        // accesslib_clear_role_cache($roleid) internally, so no manual cache
        // purge is needed here. mark_context_dirty() does NOT exist in Moodle 5.x.
    }

    /**
     * Remove all LernHive capability restrictions for a user.
     *
     * @param int $userid The user ID.
     */
    public static function remove_restrictions(int $userid): void {
        $context = \context_system::instance();
        $roleid = self::get_or_create_lernhive_role();

        // Unassign the LernHive role.
        role_unassign($roleid, $userid, $context->id);
    }

    /**
     * Get or create the special LernHive role used for capability overrides.
     *
     * @return int The role ID.
     */
    public static function get_or_create_lernhive_role(): int {
        global $DB;

        $role = $DB->get_record('role', ['shortname' => 'lernhive_filter']);
        if ($role) {
            return (int) $role->id;
        }

        // Create the role.
        $roleid = create_role(
            'LernHive Filter',
            'lernhive_filter',
            'Automatically managed by LernHive plugin to control feature visibility.',
            ''
        );

        // Allow this role to be assigned in system context.
        set_role_contextlevels($roleid, [CONTEXT_SYSTEM]);

        return $roleid;
    }

    /**
     * Get modules that are locked (not yet available) for a given level.
     *
     * @param int $level The current level.
     * @return array Array of ['module' => string, 'unlocks_at' => int]
     */
    public static function get_locked_modules(int $level): array {
        $levelmodules = self::get_level_modules();
        $locked = [];

        for ($i = $level + 1; $i <= level_manager::LEVEL_MAX; $i++) {
            if (isset($levelmodules[$i])) {
                foreach ($levelmodules[$i] as $mod) {
                    $locked[] = [
                        'module' => $mod,
                        'unlocks_at' => $i,
                    ];
                }
            }
        }

        return $locked;
    }
}
