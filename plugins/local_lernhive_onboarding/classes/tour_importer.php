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
     *     "pathmatch": "/user/editadvanced.php%",
     *     "enabled": 1,
     *     "sortorder": 0,
     *     "configdata": "{}",
     *     "steps": [
     *         {
     *             "title": "Welcome",
     *             "content": "This tour shows...",
     *             "targettype": 0,
     *             "targetvalue": "",
     *             "placement": 0,
     *             "sortorder": 0
     *         }
     *     ]
     * }
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

        // Check if tour with this name already exists (avoid duplicates on re-import).
        $existing = $DB->get_record('tool_usertours_tours', ['name' => $data['name']]);
        if ($existing) {
            // Map to category if not yet mapped.
            tour_manager::add_tour_to_category($categoryid, $existing->id, $sortorder);
            return (int) $existing->id;
        }

        // Insert tour into tool_usertours_tours.
        $tour = new \stdClass();
        $tour->name = $data['name'];
        $tour->description = $data['description'] ?? '';
        $tour->pathmatch = $data['pathmatch'] ?? '%';
        $tour->enabled = (int) ($data['enabled'] ?? 1);
        $tour->sortorder = (int) ($data['sortorder'] ?? 0);
        $tour->configdata = $data['configdata'] ?? '{}';
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
                $step->targettype = (int) ($stepdata['targettype'] ?? 0);
                $step->targetvalue = $stepdata['targetvalue'] ?? '';
                $step->sortorder = (int) ($stepdata['sortorder'] ?? $i);
                $step->configdata = $stepdata['configdata'] ?? '{}';

                $DB->insert_record('tool_usertours_steps', $step);
            }
        }

        // Map tour to category.
        tour_manager::add_tour_to_category($categoryid, $tourid, $sortorder);

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
            $category = tour_manager::get_category_by_shortname($shortname);

            if (!$category) {
                debugging("LernHive Onboarding tour_importer: no category for shortname '{$shortname}'", DEBUG_DEVELOPER);
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
}
