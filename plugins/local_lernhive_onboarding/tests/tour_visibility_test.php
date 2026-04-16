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

namespace local_lernhive_onboarding;

use advanced_testcase;
use local_lernhive\feature\override_store;
use local_lernhive\feature\registry;
use PHPUnit\Framework\Attributes\CoversClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Registry-aware onboarding visibility tests.
 *
 * @package    local_lernhive_onboarding
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(tour_manager::class)]
final class tour_visibility_test extends advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        tour_manager::reset_cache();
        registry::reset_cache();
    }

    protected function tearDown(): void {
        tour_manager::reset_cache();
        registry::reset_cache();
        parent::tearDown();
    }

    public function test_null_feature_id_mapping_stays_visible(): void {
        $this->resetAfterTest(true);

        $categoryid = $this->create_category(1);
        $this->create_mapping($categoryid, 900001, null);

        $tours = tour_manager::get_category_tours($categoryid, 1);
        $this->assertCount(1, $tours);
    }

    public function test_feature_mapping_is_filtered_by_registry_level(): void {
        $this->resetAfterTest(true);

        $categoryid = $this->create_category(1);
        $this->create_mapping($categoryid, 900002, 'mod_assign.create');

        $level1 = tour_manager::get_category_tours($categoryid, 1);
        $level2 = tour_manager::get_category_tours($categoryid, 2);

        $this->assertCount(0, $level1);
        $this->assertCount(1, $level2);
    }

    public function test_feature_override_event_invalidates_tour_cache(): void {
        $this->resetAfterTest(true);

        $categoryid = $this->create_category(1);
        $this->create_mapping($categoryid, 900003, 'mod_assign.create');

        // Default is level 2 => hidden for level 1, and result gets cached.
        $this->assertCount(0, tour_manager::get_category_tours($categoryid, 1));

        // This triggers local_lernhive\event\feature_override_changed, which
        // local_lernhive_onboarding observes to reset in-process caches.
        override_store::set_admin_override('mod_assign.create', 1);

        $this->assertCount(1, tour_manager::get_category_tours($categoryid, 1));
    }

    /**
     * Create one test category.
     *
     * @param int $level
     * @return int
     */
    private function create_category(int $level): int {
        global $DB;

        $suffix = (string) mt_rand(100000, 999999);
        return (int) $DB->insert_record('local_lhonb_cats', (object) [
            'shortname' => 'vis_' . $suffix,
            'name' => 'tourcat_vis_' . $suffix,
            'description' => '',
            'icon' => 'circle',
            'color' => '#2563eb',
            'level' => $level,
            'sortorder' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
    }

    /**
     * Create one category-tour mapping.
     *
     * @param int $categoryid
     * @param int $tourid
     * @param string|null $featureid
     * @return void
     */
    private function create_mapping(int $categoryid, int $tourid, ?string $featureid): void {
        global $DB;

        $record = (object) [
            'categoryid' => $categoryid,
            'tourid' => $tourid,
            'sortorder' => 1,
            'timecreated' => time(),
        ];
        if ($featureid !== null) {
            $record->feature_id = $featureid;
        }

        $DB->insert_record('local_lhonb_map', $record);
        tour_manager::reset_cache();
    }
}
