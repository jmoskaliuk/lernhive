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
 * Unit tests for the JSON tour importer.
 *
 * Focus: LH-ONB-START-02 — merge top-level `start_url` into `configdata`
 * as `lh_start_url` while preserving any existing configdata keys.
 *
 * @package    local_lernhive_onboarding
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_onboarding;

use advanced_testcase;
use PHPUnit\Framework\Attributes\CoversClass;

defined('MOODLE_INTERNAL') || die();

#[CoversClass(tour_importer::class)]
final class tour_importer_test extends advanced_testcase {

    /**
     * Importing a tour fixture that declares `start_url` alongside a
     * non-empty `configdata` must:
     *   - persist `lh_start_url` on the tour's configdata,
     *   - leave every pre-existing configdata key untouched
     *     (`filtervalues`, `placement`, …),
     *   - and never strip the unrelated filter data a reviewer may
     *     have added by hand via the tool_usertours admin UI.
     *
     * Uses `tests/fixtures/tour_with_start_url.json`, which carries
     * `filtervalues.role = ['editingteacher']` and `placement = 'top'`
     * so we can assert both are preserved after the merge.
     */
    public function test_import_tour_merges_start_url_into_existing_configdata(): void {
        $this->resetAfterTest();
        global $DB, $CFG;

        // db/install.php already seeds categories during the Moodle
        // PHPUnit init, but re-seed here anyway — seed_categories()
        // is idempotent and this keeps the test self-contained.
        tour_importer::seed_categories();
        $category = tour_manager::get_category_by_shortname('create_users');
        $this->assertNotFalse($category, 'create_users category should be seeded by install.php');

        $fixture = $CFG->dirroot
            . '/local/lernhive_onboarding/tests/fixtures/tour_with_start_url.json';
        $this->assertFileExists($fixture);

        $tourid = tour_importer::import_tour($fixture, (int) $category->id, 99);
        $this->assertGreaterThan(0, $tourid, 'import_tour should return a valid tour id');

        $record = $DB->get_record('tool_usertours_tours', ['id' => $tourid], '*', MUST_EXIST);
        $config = json_decode($record->configdata, true);

        $this->assertIsArray($config, 'configdata must remain a JSON object after the merge');

        // start_url merged in under the canonical key.
        $this->assertArrayHasKey('lh_start_url', $config);
        $this->assertSame('/user/editadvanced.php?id={USERID}', $config['lh_start_url']);

        // Pre-existing filtervalues still there — full subtree intact.
        $this->assertArrayHasKey('filtervalues', $config);
        $this->assertSame(['editingteacher'], $config['filtervalues']['role']);

        // Pre-existing scalar key still there.
        $this->assertArrayHasKey('placement', $config);
        $this->assertSame('top', $config['placement']);
    }

    /**
     * A tour JSON that carries no `start_url` must leave configdata
     * byte-identical — we never want the merge path to invent an
     * empty `lh_start_url` key that would later be mistaken for
     * "deterministic start is configured".
     */
    public function test_import_tour_without_start_url_leaves_configdata_intact(): void {
        $this->resetAfterTest();
        global $DB, $CFG;

        tour_importer::seed_categories();
        $category = tour_manager::get_category_by_shortname('create_users');
        $this->assertNotFalse($category);

        $fixture = $CFG->dirroot
            . '/local/lernhive_onboarding/tests/fixtures/tour_without_start_url.json';
        $this->assertFileExists($fixture);

        $tourid = tour_importer::import_tour($fixture, (int) $category->id, 98);
        $this->assertGreaterThan(0, $tourid);

        $record = $DB->get_record('tool_usertours_tours', ['id' => $tourid], '*', MUST_EXIST);
        $config = json_decode($record->configdata, true);

        $this->assertIsArray($config);
        $this->assertArrayNotHasKey(
            'lh_start_url',
            $config,
            'lh_start_url must not be written when the source JSON has no start_url'
        );
        // Original filter values still present.
        $this->assertArrayHasKey('filtervalues', $config);
        $this->assertSame(['student'], $config['filtervalues']['role']);
    }
}
