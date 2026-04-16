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
 * Tour importer for LernHive Onboarding — imports JSON tour definitions into Moodle.
 *
 * @package    local_lernhive_onboarding
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_onboarding;

defined('MOODLE_INTERNAL') || die();

/**
 * Imports user tour JSON files into Moodle's tool_usertours tables
 * and maps them to LernHive tour categories.
 */
class tour_importer {

    /**
     * Import a single tour from a JSON file and map it to a category.
     *
     * JSON format:
     * {
     *     "name": "Create a single user",
     *     "description": "Step-by-step guide...",
     *     "lernhive_feature": "core.user.create",
     *     "pathmatch": "/user/editadvanced.php%",
     *     "start_url": "/user/editadvanced.php?id={USERID}",
     *     "enabled": 1,
     *     "sortorder": 0,
     *     "configdata": "{\"filtervalues\":{\"role\":[\"editingteacher\"]}}",
     *     "steps": [
     *         {
     *             "title": "Welcome",
     *             "content": "This tour shows...",
     *             "targettype": 2,
     *             "targetvalue": "",
     *             "placement": 0,
     *             "sortorder": 0
     *         }
     *     ]
     * }
     *
     * If a top-level `start_url` is present it is merged into the tour's
     * `configdata` JSON under the key `lh_start_url`, without touching any
     * pre-existing keys (e.g. `filtervalues`, `placement`). This is the
     * storage side of LH-ONB-START-02: the `start_url_resolver` reads it
     * back at tour-launch time to produce the deterministic redirect.
     *
     * If the top-level `lernhive_feature` key is present it is persisted on
     * `local_lhonb_map.feature_id` (FR-02). Missing/empty key keeps the
     * mapping nullable for backward compatibility.
     *
     * @param string $filepath Full path to the JSON file.
     * @param int $categoryid The LernHive tour category ID.
     * @param int $sortorder Sort order within the category.
     * @return int The created tour ID, or 0 on failure.
     */
    public static function import_tour(string $filepath, int $categoryid, int $sortorder = 0): int {
        global $DB;

        if (!file_exists($filepath)) {
            debugging("LernHive Onboarding tour_importer: file not found: {$filepath}", DEBUG_DEVELOPER);
            return 0;
        }

        $json = file_get_contents($filepath);
        $data = json_decode($json, true);

        if (empty($data) || empty($data['name'])) {
            debugging("LernHive Onboarding tour_importer: invalid JSON in {$filepath}", DEBUG_DEVELOPER);
            return 0;
        }
        $featureid = self::extract_feature_id($data);

        // Check if tour with this name already exists (avoid duplicates on re-import).
        $existing = $DB->get_record('tool_usertours_tours', ['name' => $data['name']]);
        if ($existing) {
            // Map to category if not yet mapped.
            tour_manager::add_tour_to_category($categoryid, $existing->id, $sortorder, $featureid);
            return (int) $existing->id;
        }

        // Insert tour into tool_usertours_tours.
        $tour = new \stdClass();
        $tour->name = $data['name'];
        $tour->description = $data['description'] ?? '';
        $tour->pathmatch = $data['pathmatch'] ?? '%';
        $tour->enabled = (int) ($data['enabled'] ?? 1);
        $tour->sortorder = (int) ($data['sortorder'] ?? 0);
        $tour->configdata = self::merge_start_url_into_configdata(
            $data['configdata'] ?? '{}',
            isset($data['start_url']) ? (string) $data['start_url'] : ''
        );
        $tour->endtourlabel = $data['endtourlabel'] ?? '';
        $tour->displaystepnumbers = (int) ($data['displaystepnumbers'] ?? 1);
        $tour->showtourwhen = (int) ($data['showtourwhen'] ?? 1); // 1 = on each visit until completed.

        $tourid = $DB->insert_record('tool_usertours_tours', $tour);

        // Insert steps.
        if (!empty($data['steps']) && is_array($data['steps'])) {
            foreach ($data['steps'] as $i => $stepdata) {
                $step = new \stdClass();
                $step->tourid = $tourid;
                $step->title = $stepdata['title'] ?? '';
                $step->content = $stepdata['content'] ?? '';
                $step->targettype = self::normalise_step_targettype($stepdata);
                $step->targetvalue = $stepdata['targetvalue'] ?? '';
                $step->sortorder = (int) ($stepdata['sortorder'] ?? $i);
                $step->configdata = $stepdata['configdata'] ?? '{}';

                $DB->insert_record('tool_usertours_steps', $step);
            }
        }

        // Map tour to category.
        tour_manager::add_tour_to_category($categoryid, $tourid, $sortorder, $featureid);

        return $tourid;
    }

    /**
     * Import all tours from a directory for a specific level.
     *
     * Expects structure:
     *   tours/level1/create_users/01_single.json
     *   tours/level1/create_users/02_csv.json
     *   tours/level1/enrol_users/01_manual.json
     *   ...
     *
     * Each subdirectory name must match a tour category shortname.
     *
     * @param int $level The LernHive level to import tours for.
     * @return int Number of tours imported.
     */
    public static function import_level(int $level): int {
        global $CFG;

        $tourdir = $CFG->dirroot . '/local/lernhive_onboarding/tours/level' . $level;
        if (!is_dir($tourdir)) {
            return 0;
        }

        $count = 0;
        $subdirs = glob($tourdir . '/*', GLOB_ONLYDIR);

        foreach ($subdirs as $subdir) {
            $shortname = basename($subdir);
            $categoryshortname = self::resolve_category_shortname_for_level($shortname, $level);
            $category = tour_manager::get_category_by_shortname($categoryshortname);

            if (!$category) {
                debugging(
                    "LernHive Onboarding tour_importer: no category for shortname '{$categoryshortname}'",
                    DEBUG_DEVELOPER
                );
                continue;
            }

            $files = glob($subdir . '/*.json');
            sort($files); // Alphabetical = sort order by filename prefix.

            foreach ($files as $i => $file) {
                $tourid = self::import_tour($file, $category->id, $i + 1);
                if ($tourid > 0) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Backfill the `lh_start_url` key into every existing tour whose
     * JSON source file lives under `tours/level{$level}/`.
     *
     * `import_tour()` deliberately short-circuits when a tour with the
     * same `name` already exists (to make repeated installs idempotent
     * and to protect admin edits on the tour steps). That short-circuit
     * is why a plain `import_level(1)` does **not** carry the new
     * `start_url` key into the DB on upgrade — we need an explicit
     * backfill pass.
     *
     * The backfill only touches the `configdata` JSON blob's
     * `lh_start_url` sub-key:
     * - preserves every other key (`filtervalues`, `placement`,
     *   `orphan`, `lh_prereq_tour_id`, …);
     * - no-op when the source JSON carries no `start_url` (e.g. the
     *   announcements tour that is scheduled to move to Level 2 in
     *   LH-ONB-START-08);
     * - no-op when the tour row no longer exists in the DB (previously
     *   deleted by an admin — we must not resurrect it).
     *
     * @param int $level LernHive level whose JSON files drive the backfill.
     * @return int Number of tours whose `lh_start_url` was set or updated.
     */
    public static function backfill_start_urls(int $level): int {
        global $CFG, $DB;

        $tourdir = $CFG->dirroot . '/local/lernhive_onboarding/tours/level' . $level;
        if (!is_dir($tourdir)) {
            return 0;
        }

        $files = glob($tourdir . '/*/*.json');
        if (empty($files)) {
            return 0;
        }
        sort($files);

        $updated = 0;

        foreach ($files as $file) {
            $json = @file_get_contents($file);
            if ($json === false) {
                continue;
            }
            $data = json_decode($json, true);
            if (!is_array($data) || empty($data['name'])) {
                continue;
            }
            $starturl = isset($data['start_url']) ? (string) $data['start_url'] : '';
            if ($starturl === '') {
                continue;
            }

            $tour = $DB->get_record('tool_usertours_tours', ['name' => $data['name']]);
            if (!$tour) {
                // Admin deleted this tour — respect that and skip.
                continue;
            }

            $merged = self::merge_start_url_into_configdata(
                (string) ($tour->configdata ?? '{}'),
                $starturl
            );

            if ($merged === (string) $tour->configdata) {
                // Already backfilled on a previous run.
                continue;
            }

            $tour->configdata = $merged;
            $DB->update_record('tool_usertours_tours', $tour);
            $updated++;
        }

        return $updated;
    }

    /**
     * Unmap a previously-imported tour from its LernHive category
     * without deleting the underlying `tool_usertours_tours` row.
     *
     * Used by upgrade steps that move a tour between levels. Deleting
     * the tool_usertours row would also wipe admin customisations to
     * the tour steps — that is explicitly unacceptable. We leave the
     * row in place so the tour still plays via its `pathmatch` on the
     * target page, and only drop the catalog mapping so it stops
     * appearing in the LernHive Onboarding tour list.
     *
     * Looks up the tour by its `name` field. No-op if the tour is not
     * in the DB (fresh installs on 0.2.8+ never imported it under the
     * old Level-1 category) or not mapped to the given category.
     *
     * @param string $tourname    Human-readable tour name, matching the
     *                            value stored in `tool_usertours_tours.name`.
     * @param string $categoryshortname  LernHive category shortname the
     *                                   tour should be unmapped from.
     * @return bool True if a mapping was removed, false otherwise.
     */
    public static function unmap_tour_from_category(string $tourname, string $categoryshortname): bool {
        global $DB;

        $tour = $DB->get_record('tool_usertours_tours', ['name' => $tourname]);
        if (!$tour) {
            return false;
        }

        $category = tour_manager::get_category_by_shortname($categoryshortname);
        if (!$category) {
            return false;
        }

        return tour_manager::remove_tour_from_category((int) $category->id, (int) $tour->id);
    }

    /**
     * Move one tour mapping from one category to another.
     *
     * Keeps the underlying tour row intact and only rewires category mappings.
     *
     * @param string $tourname
     * @param string $fromshortname
     * @param string $toshortname
     * @param int $sortorder Sort order in target category (0 = append).
     * @return bool True when the mapping changed.
     */
    public static function remap_tour_to_category(
        string $tourname,
        string $fromshortname,
        string $toshortname,
        int $sortorder = 0
    ): bool {
        global $DB;

        $tour = $DB->get_record('tool_usertours_tours', ['name' => $tourname]);
        if (!$tour) {
            return false;
        }

        $from = tour_manager::get_category_by_shortname($fromshortname);
        $to = tour_manager::get_category_by_shortname($toshortname);
        if (!$to) {
            return false;
        }

        $changed = false;
        if ($from) {
            $changed = tour_manager::remove_tour_from_category((int) $from->id, (int) $tour->id) || $changed;
        }

        $existsintarget = $DB->record_exists('local_lhonb_map', [
            'categoryid' => (int) $to->id,
            'tourid' => (int) $tour->id,
        ]);

        tour_manager::add_tour_to_category((int) $to->id, (int) $tour->id, $sortorder);
        return $changed || !$existsintarget;
    }

    /**
     * Re-import + backfill shortcut for a single level.
     *
     * Runs a regular `import_level()` to pick up any brand-new tours
     * added to the JSON set since the previous release, then calls
     * `backfill_start_urls()` to ensure every existing tour row
     * carries the authoritative `lh_start_url`.
     *
     * Used from `db/upgrade.php` savepoints — deliberately kept as a
     * thin wrapper so test code can drive the two phases independently.
     *
     * @param int $level LernHive level to re-import + backfill.
     * @return int Number of tours imported or updated.
     */
    public static function reimport_level(int $level): int {
        $imported = self::import_level($level);
        $backfilled = self::backfill_start_urls($level);
        $pathmatches = self::backfill_pathmatches($level);
        return $imported + $backfilled + $pathmatches;
    }

    /**
     * Backfill pathmatch from canonical tour JSON definitions.
     *
     * Existing sites may carry older pathmatch values because `import_tour()`
     * intentionally skips existing rows by name. If start URLs were updated
     * later but pathmatch stayed old, Moodle cannot match the tour on the
     * redirected page. This pass aligns `tool_usertours_tours.pathmatch` with
     * the JSON source of truth for each known tour name.
     *
     * @param int $level LernHive level whose JSON files drive the backfill.
     * @return int Number of updated tour rows.
     */
    public static function backfill_pathmatches(int $level): int {
        global $CFG, $DB;

        $tourdir = $CFG->dirroot . '/local/lernhive_onboarding/tours/level' . $level;
        if (!is_dir($tourdir)) {
            return 0;
        }

        $files = glob($tourdir . '/*/*.json');
        if (empty($files)) {
            return 0;
        }
        sort($files);

        $updated = 0;
        foreach ($files as $file) {
            $json = @file_get_contents($file);
            if ($json === false) {
                continue;
            }
            $data = json_decode($json, true);
            if (!is_array($data) || empty($data['name']) || empty($data['pathmatch'])) {
                continue;
            }

            $tour = $DB->get_record('tool_usertours_tours', ['name' => $data['name']]);
            if (!$tour) {
                continue;
            }

            $canonical = (string) $data['pathmatch'];
            if ((string) $tour->pathmatch === $canonical) {
                continue;
            }

            $tour->pathmatch = $canonical;
            $DB->update_record('tool_usertours_tours', $tour);
            $updated++;
        }

        return $updated;
    }

    /**
     * Seed the 6 default tour categories for Level 1 (Explorer).
     *
     * Safe to call multiple times — skips existing categories.
     *
     * @return void
     */
    public static function seed_categories(): void {
        global $DB;

        $now = time();
        $cats = [
            ['shortname' => 'create_users',      'icon' => 'user-plus',      'color' => '#2563eb', 'level' => 1, 'sortorder' => 1],
            ['shortname' => 'enrol_users',       'icon' => 'users',          'color' => '#16a34a', 'level' => 1, 'sortorder' => 2],
            ['shortname' => 'create_courses',    'icon' => 'book-plus',      'color' => '#7c3aed', 'level' => 1, 'sortorder' => 3],
            ['shortname' => 'course_settings',   'icon' => 'settings',       'color' => '#d97706', 'level' => 1, 'sortorder' => 4],
            ['shortname' => 'create_activities', 'icon' => 'plus-square',    'color' => '#0d9488', 'level' => 1, 'sortorder' => 5],
            ['shortname' => 'communication',     'icon' => 'message-circle', 'color' => '#dc2626', 'level' => 1, 'sortorder' => 6],
            ['shortname' => 'assignments',       'icon' => 'clipboard-check', 'color' => '#2563eb', 'level' => 2, 'sortorder' => 1],
            ['shortname' => 'forum_advanced',    'icon' => 'messages-square', 'color' => '#0d9488', 'level' => 2, 'sortorder' => 2],
            ['shortname' => 'bigbluebutton',     'icon' => 'video',           'color' => '#7c3aed', 'level' => 2, 'sortorder' => 3],
            ['shortname' => 'communication_level2', 'icon' => 'megaphone',    'color' => '#dc2626', 'level' => 2, 'sortorder' => 4],
        ];

        foreach ($cats as $cat) {
            // Skip if already exists.
            if ($DB->record_exists('local_lhonb_cats', ['shortname' => $cat['shortname']])) {
                continue;
            }

            $record = (object) $cat;
            $record->name = 'tourcat_' . $cat['shortname']; // Placeholder for lang string key.
            $record->description = '';
            $record->timecreated = $now;
            $record->timemodified = $now;

            $DB->insert_record('local_lhonb_cats', $record);
        }
    }

    /**
     * Merge a tour's `start_url` into its `configdata` JSON string as
     * the key `lh_start_url`, preserving every pre-existing key.
     *
     * `tool_usertours_tours.configdata` is a free-form JSON blob that
     * already carries core keys like `filtervalues`, `placement`,
     * `orphan`, and (in 0.3.0+) our own chain metadata
     * `lh_prereq_tour_id`. We must never overwrite those.
     *
     * Behaviour:
     *  - If `$starturl` is the empty string the input `configdata`
     *    is returned unchanged. This is the no-op path for Level-1
     *    tours that have not yet been migrated to carry a `start_url`
     *    (see the 0.2.x pathmatch-strip fallback in `starttour.php`).
     *  - If `$configdata` is not a valid JSON object it is coerced to
     *    an empty object before the merge, so a malformed fixture
     *    cannot crash the import path.
     *
     * Extracted as a private helper so the PHPUnit test for
     * LH-ONB-START-02 can drive it through `import_tour()` (not
     * called directly — the public contract is `import_tour`).
     *
     * @param string $configdata Raw JSON string from tour JSON.
     * @param string $starturl   The top-level `start_url` value, or ''
     *                           when the tour JSON does not declare one.
     * @return string The JSON-encoded merged configdata.
     */
    private static function merge_start_url_into_configdata(string $configdata, string $starturl): string {
        if ($starturl === '') {
            return $configdata;
        }

        $decoded = json_decode($configdata, true);
        if (!is_array($decoded)) {
            // Malformed or null — start from an empty object so the
            // `lh_start_url` key can still be written without losing
            // data we never had.
            $decoded = [];
        }

        $decoded['lh_start_url'] = $starturl;

        return json_encode($decoded);
    }

    /**
     * Read and normalise the optional tour JSON key `lernhive_feature`.
     *
     * @param array $data Decoded tour JSON.
     * @return string|null
     */
    private static function extract_feature_id(array $data): ?string {
        if (!array_key_exists('lernhive_feature', $data)) {
            return null;
        }
        $featureid = trim((string) $data['lernhive_feature']);
        if ($featureid === '') {
            return null;
        }
        return $featureid;
    }

    /**
     * Normalise step target type values from JSON import payloads.
     *
     * Canonical Moodle 4.5+/5.x mapping:
     * - 0 = CSS selector
     * - 1 = block
     * - 2 = unattached (middle of the page)
     *
     * Legacy LernHive fixtures used a swapped selector/unattached mapping.
     * We detect that by combining target type and target value:
     * - `targettype=2` with a non-empty selector value => selector (0)
     * - `targettype=0` with an empty target value => unattached (2)
     *
     * @param array $stepdata One decoded JSON step payload.
     * @return int Normalised Moodle target type.
     */
    private static function normalise_step_targettype(array $stepdata): int {
        $targettype = (int) ($stepdata['targettype'] ?? 2);
        $targetvalue = trim((string) ($stepdata['targetvalue'] ?? ''));

        if ($targettype === 2 && $targetvalue !== '') {
            return 0;
        }
        if ($targettype === 0 && $targetvalue === '') {
            return 2;
        }

        return $targettype;
    }

    /**
     * Map authoring directory names to runtime category shortnames.
     *
     * @param string $shortname Directory shortname.
     * @param int $level
     * @return string
     */
    private static function resolve_category_shortname_for_level(string $shortname, int $level): string {
        if ($level === 2 && $shortname === 'communication') {
            return 'communication_level2';
        }
        return $shortname;
    }
}
