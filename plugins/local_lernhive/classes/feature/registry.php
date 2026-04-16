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

namespace local_lernhive\feature;

defined('MOODLE_INTERNAL') || die();

use coding_exception;

/**
 * Canonical feature registry for the LernHive level system (ADR-01).
 *
 * This class is the single source of truth for "which feature lives on which
 * level". The canonical feature list is hardcoded in this class while effective
 * levels are resolved via {@see override_store} (admin + flavor presets).
 *
 * The class is static-only and cached in-process. Use {@see reset_cache()} in
 * tests after mutating state.
 *
 * @package    local_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class registry {

    /** @var array<string, definition>|null In-process cache of the feature list. */
    private static ?array $features = null;

    /** @var array<string, int|null>|null Cached overrides keyed by feature_id. */
    private static ?array $overridelevels = null;

    /**
     * Registry is static-only; prevent instantiation.
     */
    private function __construct() {
    }

    /**
     * Get every registered feature keyed by feature_id.
     *
     * @return array<string, definition>
     */
    public static function get_features(): array {
        if (self::$features === null) {
            self::$features = self::build_features();
        }
        return self::$features;
    }

    /**
     * Get a single feature definition or null if it is not registered.
     *
     * @param string $featureid Canonical feature ID.
     * @return definition|null
     */
    public static function get_feature(string $featureid): ?definition {
        $features = self::get_features();
        return $features[$featureid] ?? null;
    }

    /**
     * Return the effective level at which a feature currently unlocks.
     *
     * Override resolution order is:
     * 1) explicit admin override
     * 2) flavor preset override
     * 3) registry default
     *
     * A NULL override value means "disabled", which is represented as
     * MAX_LEVEL + 1 so the feature is never unlocked in levels 1..5.
     *
     * @param string $featureid Canonical feature ID.
     * @return int 1..(MAX_LEVEL+1).
     * @throws coding_exception If the feature is not registered.
     */
    public static function effective_level(string $featureid): int {
        $definition = self::get_feature($featureid);
        if ($definition === null) {
            throw new coding_exception("local_lernhive unknown feature '{$featureid}'");
        }
        $overrides = self::get_override_levels();
        if (array_key_exists($featureid, $overrides)) {
            $overridelevel = $overrides[$featureid];
            if ($overridelevel === null) {
                return definition::MAX_LEVEL + 1;
            }
            return $overridelevel;
        }
        return $definition->defaultlevel;
    }

    /**
     * Return every feature that a user at the given level has unlocked.
     *
     * Cumulative: a user on level 3 also has every feature with
     * effective_level <= 3. Callers that want only "freshly unlocked on this
     * level" should filter on `effective_level === $level`.
     *
     * @param int $level 1..5.
     * @return array<string, definition>
     * @throws coding_exception If $level is out of range.
     */
    public static function get_features_for_level(int $level): array {
        if ($level < definition::MIN_LEVEL || $level > definition::MAX_LEVEL) {
            throw new coding_exception("local_lernhive invalid level {$level}");
        }
        $result = [];
        foreach (self::get_features() as $id => $definition) {
            if (self::effective_level($id) <= $level) {
                $result[$id] = $definition;
            }
        }
        return $result;
    }

    /**
     * Reset in-process caches. Intended for unit tests and override writes.
     */
    public static function reset_cache(): void {
        self::$features = null;
        self::$overridelevels = null;
    }

    /**
     * Resolve and cache override levels keyed by feature_id.
     *
     * @return array<string, int|null>
     */
    private static function get_override_levels(): array {
        if (self::$overridelevels === null) {
            self::$overridelevels = override_store::get_effective_levels();
        }
        return self::$overridelevels;
    }

    /**
     * Build the canonical feature list.
     *
     * Mirrors the table in docs/03-dev-doc.md § "Canonical feature IDs (R1)".
     * Authoritative copy — the docs snapshot should eventually shrink to a
     * pointer at this method.
     *
     * @return array<string, definition>
     */
    private static function build_features(): array {
        $defs = [];

        // --- Content modules: Level 1 (Explorer). ---
        $defs[] = new definition(
            'mod_resource.create', 1, 'mod/resource:addinstance',
            'feature_mod_resource_create', 'content_basics'
        );
        $defs[] = new definition(
            'mod_page.create', 1, 'mod/page:addinstance',
            'feature_mod_page_create', 'content_basics'
        );
        $defs[] = new definition(
            'mod_folder.create', 1, 'mod/folder:addinstance',
            'feature_mod_folder_create', 'content_basics'
        );
        $defs[] = new definition(
            'mod_url.create', 1, 'mod/url:addinstance',
            'feature_mod_url_create', 'content_basics'
        );
        $defs[] = new definition(
            'mod_label.create', 1, 'mod/label:addinstance',
            'feature_mod_label_create', 'content_basics'
        );
        $defs[] = new definition(
            'mod_forum.create.announcement', 1, 'mod/forum:addinstance',
            'feature_mod_forum_announcement', 'content_basics'
        );

        // --- Content modules: Level 2 (Creator). ---
        $defs[] = new definition(
            'mod_forum.create.full', 2, 'mod/forum:addinstance',
            'feature_mod_forum_full', 'forum_advanced'
        );
        $defs[] = new definition(
            'mod_assign.create', 2, 'mod/assign:addinstance',
            'feature_mod_assign_create', 'assignments'
        );
        $defs[] = new definition(
            'mod_bigbluebuttonbn.create', 2, 'mod/bigbluebuttonbn:addinstance',
            'feature_mod_bigbluebuttonbn_create', 'bigbluebutton'
        );

        // --- Content modules: Level 3 (Pro). ---
        $defs[] = new definition(
            'mod_quiz.create', 3, 'mod/quiz:addinstance',
            'feature_mod_quiz_create', 'assessment'
        );
        $defs[] = new definition(
            'mod_h5pactivity.create', 3, 'mod/h5pactivity:addinstance',
            'feature_mod_h5pactivity_create', 'assessment'
        );
        $defs[] = new definition(
            'mod_lesson.create', 3, 'mod/lesson:addinstance',
            'feature_mod_lesson_create', 'assessment'
        );

        // --- Content modules: Level 4 (Expert). ---
        $defs[] = new definition(
            'mod_wiki.create', 4, 'mod/wiki:addinstance',
            'feature_mod_wiki_create', 'collaboration'
        );
        $defs[] = new definition(
            'mod_glossary.create', 4, 'mod/glossary:addinstance',
            'feature_mod_glossary_create', 'collaboration'
        );
        $defs[] = new definition(
            'mod_data.create', 4, 'mod/data:addinstance',
            'feature_mod_data_create', 'collaboration'
        );
        $defs[] = new definition(
            'mod_workshop.create', 4, 'mod/workshop:addinstance',
            'feature_mod_workshop_create', 'collaboration'
        );

        // --- Content modules: Level 5 (Master). ---
        $defs[] = new definition(
            'mod_scorm.create', 5, 'mod/scorm:addinstance',
            'feature_mod_scorm_create', 'advanced_content'
        );
        $defs[] = new definition(
            'mod_lti.create', 5, 'mod/lti:addinstance',
            'feature_mod_lti_create', 'advanced_content'
        );
        $defs[] = new definition(
            'mod_feedback.create', 5, 'mod/feedback:addinstance',
            'feature_mod_feedback_create', 'advanced_content'
        );
        $defs[] = new definition(
            'mod_choice.create', 5, 'mod/choice:addinstance',
            'feature_mod_choice_create', 'advanced_content'
        );
        $defs[] = new definition(
            'mod_survey.create', 5, 'mod/survey:addinstance',
            'feature_mod_survey_create', 'advanced_content'
        );
        $defs[] = new definition(
            'mod_book.create', 5, 'mod/book:addinstance',
            'feature_mod_book_create', 'advanced_content'
        );
        $defs[] = new definition(
            'mod_imscp.create', 5, 'mod/imscp:addinstance',
            'feature_mod_imscp_create', 'advanced_content'
        );
        $defs[] = new definition(
            'mod_subsection.create', 5, 'mod/subsection:addinstance',
            'feature_mod_subsection_create', 'advanced_content'
        );

        // --- Course lifecycle. ---
        $defs[] = new definition(
            'core.course.create', 1, 'moodle/course:create',
            'feature_core_course_create', 'course_basics'
        );
        $defs[] = new definition(
            'core.course.settings.format', 1, 'moodle/course:update',
            'feature_core_course_settings_format', 'course_settings'
        );
        $defs[] = new definition(
            'core.course.settings.completion', 1, 'moodle/course:update',
            'feature_core_course_settings_completion', 'course_settings'
        );
        $defs[] = new definition(
            'core.my.manageblocks', 4, 'moodle/my:manageblocks',
            'feature_core_my_manageblocks', 'dashboard'
        );

        // --- Users (Level 1 default, configurable — matters for flavor_schule). ---
        $defs[] = new definition(
            'core.user.create', 1, 'moodle/user:create',
            'feature_core_user_create', 'users', 'schule'
        );
        $defs[] = new definition(
            'core.user.enrol', 1, 'enrol/manual:enrol',
            'feature_core_user_enrol', 'users'
        );
        $defs[] = new definition(
            'core.message.send', 1, 'moodle/site:sendmessage',
            'feature_core_message_send', 'communication'
        );

        // --- Grades. ---
        $defs[] = new definition(
            'core.grade.view', 2, 'moodle/grade:view',
            'feature_core_grade_view', 'grades'
        );
        $defs[] = new definition(
            'core.grade.manage', 4, 'moodle/grade:manage',
            'feature_core_grade_manage', 'grades'
        );
        $defs[] = new definition(
            'core.grade.edit', 4, 'moodle/grade:edit',
            'feature_core_grade_edit', 'grades'
        );

        // --- Reports. ---
        $defs[] = new definition(
            'core.site.viewreports', 4, 'moodle/site:viewreports',
            'feature_core_site_viewreports', 'reports'
        );

        // --- Groups. ---
        $defs[] = new definition(
            'core.course.managegroups', 3, 'moodle/course:managegroups',
            'feature_core_course_managegroups', 'groups'
        );

        // --- Enrolment configuration. ---
        $defs[] = new definition(
            'core.course.enrolconfig', 4, 'moodle/course:enrolconfig',
            'feature_core_course_enrolconfig', 'enrolment'
        );

        // --- Lifecycle: backup / restore / import. ---
        $defs[] = new definition(
            'core.backup.course', 5, 'moodle/backup:backupcourse',
            'feature_core_backup_course', 'lifecycle'
        );
        $defs[] = new definition(
            'core.restore.course', 5, 'moodle/restore:restorecourse',
            'feature_core_restore_course', 'lifecycle'
        );
        $defs[] = new definition(
            'core.course.import', 5, 'moodle/course:import',
            'feature_core_course_import', 'lifecycle'
        );

        // Re-key by feature_id and guard against duplicates.
        $bykey = [];
        foreach ($defs as $def) {
            if (isset($bykey[$def->featureid])) {
                throw new coding_exception(
                    "local_lernhive duplicate feature '{$def->featureid}'"
                );
            }
            $bykey[$def->featureid] = $def;
        }
        return $bykey;
    }
}
