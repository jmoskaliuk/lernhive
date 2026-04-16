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
 * Tour management class for Lernpfad (Learning Path) feature.
 *
 * @package    local_lernhive_onboarding
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_onboarding;

defined('MOODLE_INTERNAL') || die();

/**
 * Manages tour categories and completion tracking for LernHive guided tours.
 *
 * This class provides methods to:
 * - Retrieve tour categories for a given LernHive level
 * - Get tours mapped to categories
 * - Track completion status of tours and categories
 * - Check if levels are complete
 */
class tour_manager {
    /** @var bool|null */
    private static ?bool $maphasfeatureid = null;
    /** @var array<int, array<int, \stdClass>> */
    private static array $categoriescache = [];
    /** @var array<string, array<int, \stdClass>> */
    private static array $tourcache = [];

    /**
     * Get all tour categories for a given level, ordered by sortorder.
     *
     * @param int $level The LernHive level (1-5)
     * @return array Array of category records with id, shortname, name, description, icon, color, sortorder
     */
    public static function get_categories(int $level): array {
        if (isset(self::$categoriescache[$level])) {
            return self::$categoriescache[$level];
        }

        global $DB;

        $categories = $DB->get_records(
            'local_lhonb_cats',
            ['level' => $level],
            'sortorder ASC'
        );

        // Registry-aware visibility: hide empty categories after per-tour
        // feature filtering for the current level.
        $visible = [];
        foreach ($categories as $id => $category) {
            if (!empty(self::get_category_tours((int) $category->id, $level))) {
                $visible[$id] = $category;
            }
        }

        self::$categoriescache[$level] = $visible;
        return $visible;
    }

    /**
     * Get all tours mapped to a specific category.
     *
     * @param int $categoryid The category ID from local_lhonb_cats
     * @return array Array of tour mapping records with id, tourid, sortorder
     */
    public static function get_category_tours(int $categoryid, ?int $level = null): array {
        $levelkey = ($level === null) ? 'all' : (string) $level;
        $cachekey = $categoryid . ':' . $levelkey;
        if (isset(self::$tourcache[$cachekey])) {
            return self::$tourcache[$cachekey];
        }

        global $DB;

        $records = $DB->get_records(
            'local_lhonb_map',
            ['categoryid' => $categoryid],
            'sortorder ASC'
        );

        if ($level === null || !self::map_has_feature_id_field()) {
            self::$tourcache[$cachekey] = $records;
            return $records;
        }

        $visible = [];
        foreach ($records as $id => $record) {
            if (self::is_mapping_visible_for_level($record, $level)) {
                $visible[$id] = $record;
            }
        }

        self::$tourcache[$cachekey] = $visible;
        return $visible;
    }

    /**
     * Get completion status for a user's tours in a category.
     *
     * Checks Moodle's user_preferences table for tour completion records.
     * On Moodle 5.x this is based on:
     * - \tool_usertours\tour::TOUR_LAST_COMPLETED_BY_USER
     * - \tool_usertours\tour::TOUR_REQUESTED_BY_USER
     *
     * Legacy fallback keys from older LernHive iterations are still honored:
     * tool_usertours_{tourid}_completed / _requested.
     *
     * @param int $categoryid The category ID from local_lhonb_cats
     * @param int $userid The user ID
     * @return array Array with keys:
     *     - 'total' => total number of tours in category
     *     - 'completed' => number of completed tours
     *     - 'percent' => completion percentage (0-100)
     *     - 'done' => boolean, true if all tours completed
     *     - 'tours' => array of tour records with completion status
     */
    public static function get_category_progress(int $categoryid, int $userid, ?int $level = null): array {
        global $DB;

        $tours = self::get_category_tours($categoryid, $level);
        $total = count($tours);
        $completed = 0;
        $tourdata = [];

        foreach ($tours as $tour) {
            $iscompleted = self::is_tour_completed($tour->tourid, $userid);
            if ($iscompleted) {
                $completed++;
            }

            $tourdata[] = (object) [
                'tourid' => $tour->tourid,
                'sortorder' => $tour->sortorder,
                'completed' => $iscompleted,
            ];
        }

        $percent = ($total > 0) ? (int) round(($completed / $total) * 100) : 0;

        return [
            'total' => $total,
            'completed' => $completed,
            'percent' => $percent,
            'done' => ($completed >= $total),
            'tours' => $tourdata,
        ];
    }

    /**
     * Get overall progress for a level.
     *
     * Aggregates completion status for all categories in a level.
     *
     * @param int $level The LernHive level (1-5)
     * @param int $userid The user ID
     * @return array Array with keys:
     *     - 'level' => the level number
     *     - 'total_categories' => total number of categories
     *     - 'completed_categories' => number of completed categories
     *     - 'total_tours' => total number of tours in all categories
     *     - 'completed_tours' => number of completed tours
     *     - 'percent' => level completion percentage (0-100)
     *     - 'done' => boolean, true if all tours in level completed
     *     - 'categories' => array of category records with progress data
     */
    public static function get_level_progress(int $level, int $userid): array {
        $categories = self::get_categories($level);
        $totalcats = count($categories);
        $completedcats = 0;
        $totaltours = 0;
        $completedtours = 0;
        $catdata = [];

        foreach ($categories as $cat) {
            $progress = self::get_category_progress((int) $cat->id, $userid, $level);
            $totaltours += $progress['total'];
            $completedtours += $progress['completed'];

            if ($progress['done']) {
                $completedcats++;
            }

            // Add progress data to category record.
            $cat->progress = $progress;
            $catdata[] = $cat;
        }

        $percent = ($totaltours > 0) ? (int) round(($completedtours / $totaltours) * 100) : 0;

        return [
            'level' => $level,
            'total_categories' => $totalcats,
            'completed_categories' => $completedcats,
            'total_tours' => $totaltours,
            'completed_tours' => $completedtours,
            'percent' => $percent,
            'done' => ($totalcats > 0 && $completedcats >= $totalcats),
            'categories' => $catdata,
        ];
    }

    /**
     * Check if a specific Moodle user tour is completed by a user.
     *
     * Moodle 5.x stores completion in user_preferences under
     * `tool_usertours_tour_completion_time_{tourid}` and treats a tour as
     * complete only when completion time is newer than reset/request time.
     *
     * Legacy fallback keys `tool_usertours_{tourid}_completed` and
     * `tool_usertours_{tourid}_requested` are still read for backward
     * compatibility.
     *
     * @param int $tourid The tour ID from tool_usertours_tours
     * @param int $userid The user ID
     * @return bool True if the tour has been completed by the user
     */
    public static function is_tour_completed(int $tourid, int $userid): bool {
        // Preferred Moodle 5.x keyspace.
        $completionkey = null;
        $requestedkey = null;
        if (class_exists(\tool_usertours\tour::class)
            && defined(\tool_usertours\tour::class . '::TOUR_LAST_COMPLETED_BY_USER')
            && defined(\tool_usertours\tour::class . '::TOUR_REQUESTED_BY_USER')
        ) {
            $completionkey = \tool_usertours\tour::TOUR_LAST_COMPLETED_BY_USER . $tourid;
            $requestedkey = \tool_usertours\tour::TOUR_REQUESTED_BY_USER . $tourid;
        }

        if ($completionkey !== null) {
            $completiontime = get_user_preferences($completionkey, null, $userid);
            if (!empty($completiontime)) {
                $requesttime = get_user_preferences($requestedkey, null, $userid);
                if (!empty($requesttime)) {
                    return (int) $completiontime > (int) $requesttime;
                }
                return true;
            }
        }

        // Legacy fallback keyspace.
        $legacycompletion = get_user_preferences('tool_usertours_' . $tourid . '_completed', null, $userid);
        if (empty($legacycompletion)) {
            return false;
        }

        $legacyrequested = get_user_preferences('tool_usertours_' . $tourid . '_requested', null, $userid);
        if (!empty($legacyrequested) && is_numeric($legacycompletion) && is_numeric($legacyrequested)) {
            return (int) $legacycompletion > (int) $legacyrequested;
        }

        return true;
    }

    /**
     * Check if all tours in a level are completed (= level can be upgraded).
     *
     * A level is considered complete when all categories in that level
     * have all their tours completed.
     *
     * @param int $level The LernHive level (1-5)
     * @param int $userid The user ID
     * @return bool True if all tours in the level are completed
     */
    public static function is_level_complete(int $level, int $userid): bool {
        $progress = self::get_level_progress($level, $userid);
        return $progress['done'];
    }

    /**
     * Get a category by shortname.
     *
     * @param string $shortname The shortname identifier (e.g., 'create_users')
     * @return \stdClass|false The category record, or false if not found
     */
    public static function get_category_by_shortname(string $shortname) {
        global $DB;
        return $DB->get_record('local_lhonb_cats', ['shortname' => $shortname]);
    }

    /**
     * Add a tour to a category.
     *
     * Creates a mapping in local_lhonb_map between a tour and a category.
     *
     * @param int $categoryid The category ID
     * @param int $tourid The Moodle tour ID
     * @param int $sortorder The display order (optional, defaults to end)
     * @param string|null $featureid Optional local_lernhive feature id.
     * @return int The mapping record ID
     */
    public static function add_tour_to_category(
        int $categoryid,
        int $tourid,
        int $sortorder = 0,
        ?string $featureid = null
    ): int {
        global $DB;

        $featureid = self::normalise_feature_id($featureid);
        $canstorefeatureid = ($featureid !== null) && self::map_has_feature_id_field();

        // Check if mapping already exists.
        $existing = $DB->get_record(
            'local_lhonb_map',
            ['categoryid' => $categoryid, 'tourid' => $tourid]
        );

        if ($existing) {
            if ($canstorefeatureid) {
                $current = property_exists($existing, 'feature_id')
                    ? (string) ($existing->feature_id ?? '')
                    : '';
                if ($current !== $featureid) {
                    $existing->feature_id = $featureid;
                    $DB->update_record('local_lhonb_map', $existing);
                    self::reset_cache();
                }
            }
            return $existing->id;
        }

        // If no sortorder specified, append to end.
        if ($sortorder === 0) {
            $maxorder = $DB->get_field(
                'local_lhonb_map',
                'MAX(sortorder)',
                ['categoryid' => $categoryid]
            );
            $sortorder = (int) $maxorder + 1;
        }

        $record = (object) [
            'categoryid' => $categoryid,
            'tourid' => $tourid,
            'sortorder' => $sortorder,
            'timecreated' => time(),
        ];
        if ($canstorefeatureid) {
            $record->feature_id = $featureid;
        }

        $id = (int) $DB->insert_record('local_lhonb_map', $record);
        self::reset_cache();
        return $id;
    }

    /**
     * Remove a tour from a category.
     *
     * @param int $categoryid The category ID
     * @param int $tourid The Moodle tour ID
     * @return bool True if a record was deleted
     */
    public static function remove_tour_from_category(int $categoryid, int $tourid): bool {
        global $DB;
        $exists = $DB->record_exists('local_lhonb_map', [
            'categoryid' => $categoryid,
            'tourid' => $tourid,
        ]);
        if ($exists) {
            $DB->delete_records('local_lhonb_map', ['categoryid' => $categoryid, 'tourid' => $tourid]);
            self::reset_cache();
        }
        return $exists;
    }

    /**
     * Reset in-process tour visibility caches.
     *
     * @return void
     */
    public static function reset_cache(): void {
        self::$categoriescache = [];
        self::$tourcache = [];
        self::$maphasfeatureid = null;
    }

    /**
     * Whether local_lhonb_map currently has the feature_id column.
     *
     * Needed because older upgrade savepoints still call mapping helpers before
     * the FR-01 schema step has run.
     *
     * @return bool
     */
    private static function map_has_feature_id_field(): bool {
        global $DB;

        if (self::$maphasfeatureid !== null) {
            return self::$maphasfeatureid;
        }

        $columns = $DB->get_columns('local_lhonb_map');
        self::$maphasfeatureid = isset($columns['feature_id']);

        return self::$maphasfeatureid;
    }

    /**
     * Evaluate whether a tour mapping is visible at a given level.
     *
     * Null / empty feature ids stay visible for backwards compatibility.
     *
     * @param \stdClass $mapping
     * @param int $level
     * @return bool
     */
    private static function is_mapping_visible_for_level(\stdClass $mapping, int $level): bool {
        $featureid = trim((string) ($mapping->feature_id ?? ''));
        if ($featureid === '') {
            return true;
        }

        if (!class_exists(\local_lernhive\feature\registry::class)) {
            return true;
        }

        $feature = \local_lernhive\feature\registry::get_feature($featureid);
        if ($feature === null) {
            debugging(
                'local_lernhive_onboarding: unknown feature_id in tour mapping: ' . $featureid,
                DEBUG_DEVELOPER
            );
            return true;
        }

        return \local_lernhive\feature\registry::effective_level($featureid) <= $level;
    }

    /**
     * Normalise feature ids from import payloads.
     *
     * @param string|null $featureid
     * @return string|null
     */
    private static function normalise_feature_id(?string $featureid): ?string {
        if ($featureid === null) {
            return null;
        }
        $featureid = trim($featureid);
        if ($featureid === '') {
            return null;
        }
        if (\core_text::strlen($featureid) > 128) {
            debugging(
                'local_lernhive_onboarding: feature_id exceeds 128 chars, truncating: ' . $featureid,
                DEBUG_DEVELOPER
            );
            $featureid = \core_text::substr($featureid, 0, 128);
        }
        return $featureid;
    }
}
