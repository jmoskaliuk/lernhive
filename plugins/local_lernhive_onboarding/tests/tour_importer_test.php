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
 * Focus:
 * - LH-ONB-START-02: merge top-level `start_url` into `configdata`
 *   as `lh_start_url` while preserving any existing configdata keys.
 * - LH-ONB-FR-02: persist top-level `lernhive_feature` to
 *   `local_lhonb_map.feature_id`.
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

        // Feature mapping persisted on the category-tour join row.
        $mapping = $DB->get_record(
            'local_lhonb_map',
            ['categoryid' => (int) $category->id, 'tourid' => (int) $tourid],
            '*',
            MUST_EXIST
        );
        $this->assertSame('core.user.create', (string) ($mapping->feature_id ?? ''));
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

        // No feature key in JSON -> mapping stays null/empty.
        $mapping = $DB->get_record(
            'local_lhonb_map',
            ['categoryid' => (int) $category->id, 'tourid' => (int) $tourid],
            '*',
            MUST_EXIST
        );
        $this->assertTrue(
            !isset($mapping->feature_id) || $mapping->feature_id === null || $mapping->feature_id === '',
            'feature_id must stay empty when the source JSON has no lernhive_feature key'
        );
    }

    /**
     * Re-import path: when a tour row already exists, import_tour()
     * must still update the mapping row with `feature_id`.
     *
     * This covers the branch where import_tour() short-circuits on
     * existing `tool_usertours_tours.name` but still calls
     * tour_manager::add_tour_to_category(..., $featureid).
     */
    public function test_import_existing_tour_updates_mapping_feature_id(): void {
        $this->resetAfterTest();
        global $DB, $CFG;

        tour_importer::seed_categories();
        $category = tour_manager::get_category_by_shortname('create_users');
        $this->assertNotFalse($category);

        // Existing tour row with matching fixture name.
        $tourid = $DB->insert_record('tool_usertours_tours', (object) [
            'name' => 'LernHive Test Fixture: Tour mit start_url und bestehendem configdata',
            'description' => '',
            'pathmatch' => '/user/editadvanced.php%',
            'enabled' => 1,
            'sortorder' => 0,
            'configdata' => json_encode(['filtervalues' => ['role' => ['editingteacher']]]),
            'endtourlabel' => '',
            'displaystepnumbers' => 1,
            'showtourwhen' => 1,
        ]);

        // Existing mapping with no feature id yet.
        $DB->insert_record('local_lhonb_map', (object) [
            'categoryid' => (int) $category->id,
            'tourid' => (int) $tourid,
            'sortorder' => 1,
            'timecreated' => time(),
            'feature_id' => null,
        ]);

        $fixture = $CFG->dirroot
            . '/local/lernhive_onboarding/tests/fixtures/tour_with_start_url.json';
        $this->assertFileExists($fixture);

        $result = tour_importer::import_tour($fixture, (int) $category->id, 1);
        $this->assertSame((int) $tourid, $result);

        $mapping = $DB->get_record(
            'local_lhonb_map',
            ['categoryid' => (int) $category->id, 'tourid' => (int) $tourid],
            '*',
            MUST_EXIST
        );
        $this->assertSame('core.user.create', (string) ($mapping->feature_id ?? ''));
    }

    /**
     * `backfill_start_urls()` walks the real `tours/level{N}/` JSON
     * source files and updates existing tour rows in
     * `tool_usertours_tours` so their `configdata` carries the
     * canonical `lh_start_url` key — *without* touching any other
     * configdata entries (`filtervalues`, `placement`, …).
     *
     * This is the upgrade path that keeps existing 0.2.x sites from
     * losing the deterministic start after they upgrade to 0.2.8 —
     * `import_tour()` short-circuits on duplicate name so a plain
     * `import_level(1)` by itself is not enough.
     *
     * The test mimics the real upgrade scenario: a tour row already
     * exists with a pre-0.2.8 configdata (filtervalues only, no
     * `lh_start_url`), and we assert that after the backfill the key
     * is written and the pre-existing role filter is preserved.
     */
    public function test_backfill_start_urls_merges_into_existing_tour_row(): void {
        $this->resetAfterTest();
        global $DB;

        // Pre-0.2.8 state: tour row exists under its canonical name
        // (matches tours/level1/create_users/01_single.json) but its
        // configdata has no `lh_start_url`. This is how every existing
        // site looks on the morning of the 0.2.8 upgrade.
        $tour = (object) [
            'name'               => 'LernHive: Nutzer/in anlegen',
            'description'        => '',
            'pathmatch'          => '/user/editadvanced.php%',
            'enabled'            => 1,
            'sortorder'          => 0,
            'configdata'         => json_encode([
                'filtervalues' => ['role' => ['editingteacher']],
                'placement'    => 'top',
            ]),
            'endtourlabel'       => '',
            'displaystepnumbers' => 1,
            'showtourwhen'       => 1,
        ];
        $tourid = $DB->insert_record('tool_usertours_tours', $tour);

        $updated = tour_importer::backfill_start_urls(1);

        $this->assertGreaterThanOrEqual(1, $updated,
            'backfill_start_urls must report at least one updated tour.');

        $after = $DB->get_record('tool_usertours_tours', ['id' => $tourid], '*', MUST_EXIST);
        $config = json_decode($after->configdata, true);

        $this->assertIsArray($config);
        $this->assertArrayHasKey('lh_start_url', $config,
            'lh_start_url must be written into configdata after backfill.');
        $this->assertSame('/user/editadvanced.php?id=-1', $config['lh_start_url'],
            'backfilled lh_start_url must match the authoritative JSON source.');

        // Pre-existing filter preserved.
        $this->assertArrayHasKey('filtervalues', $config);
        $this->assertSame(['editingteacher'], $config['filtervalues']['role'],
            'backfill must never drop pre-existing filtervalues.');

        // Pre-existing scalar preserved.
        $this->assertArrayHasKey('placement', $config);
        $this->assertSame('top', $config['placement']);
    }

    /**
     * `backfill_start_urls()` must be a no-op on the second call
     * once the tour row already carries the canonical `lh_start_url`.
     * This matters because the upgrade savepoint will re-run on every
     * 0.2.7 → 0.2.8 site, and `reimport_level()` calls backfill
     * unconditionally afterwards — double-run must leave configdata
     * byte-identical instead of churning the row on every upgrade.
     */
    public function test_backfill_start_urls_is_idempotent(): void {
        $this->resetAfterTest();
        global $DB;

        $tour = (object) [
            'name'               => 'LernHive: Nutzer/in anlegen',
            'description'        => '',
            'pathmatch'          => '/user/editadvanced.php%',
            'enabled'            => 1,
            'sortorder'          => 0,
            'configdata'         => json_encode([
                'filtervalues' => ['role' => ['editingteacher']],
            ]),
            'endtourlabel'       => '',
            'displaystepnumbers' => 1,
            'showtourwhen'       => 1,
        ];
        $tourid = $DB->insert_record('tool_usertours_tours', $tour);

        // First backfill — writes the key.
        tour_importer::backfill_start_urls(1);
        $first = $DB->get_field('tool_usertours_tours', 'configdata', ['id' => $tourid]);

        // Second backfill — must not touch the row.
        tour_importer::backfill_start_urls(1);
        $second = $DB->get_field('tool_usertours_tours', 'configdata', ['id' => $tourid]);

        $this->assertSame($first, $second,
            'Second backfill call must leave configdata byte-identical.');
    }

    /**
     * `backfill_start_urls()` must skip tour rows the admin has
     * deleted since the previous import. Resurrecting them would
     * countermand an explicit admin action, which is a policy
     * violation — we leave deleted tours deleted.
     *
     * Verified by running the backfill against an empty DB and
     * asserting nothing is written.
     */
    public function test_backfill_start_urls_skips_missing_rows(): void {
        $this->resetAfterTest();
        global $DB;

        // Empty tool_usertours_tours — no rows to update. Clear any
        // state that install.php may have seeded before we got here.
        $DB->delete_records('tool_usertours_tours', null);

        $updated = tour_importer::backfill_start_urls(1);

        $this->assertSame(0, $updated,
            'backfill must never resurrect tour rows an admin has deleted.');
        $this->assertSame(0, $DB->count_records('tool_usertours_tours'));
    }

    /**
     * `unmap_tour_from_category()` must drop the `local_lhonb_map`
     * join row without touching the `tool_usertours_tours` row. The
     * underlying tour survives (admins may have customised its steps)
     * while the LernHive catalog stops advertising it under that
     * category. Drives the 0.2.8 announcements move
     * (LH-ONB-START-08 infra-move).
     */
    public function test_unmap_tour_from_category_drops_mapping_preserves_tour(): void {
        $this->resetAfterTest();
        global $DB;

        tour_importer::seed_categories();
        $category = tour_manager::get_category_by_shortname('communication');
        $this->assertNotFalse($category);

        // Insert a tour row and attach it to the communication category,
        // as the pre-0.2.8 import path would have done.
        $tourid = $DB->insert_record('tool_usertours_tours', (object) [
            'name'               => 'LernHive: Test Announcements',
            'description'        => '',
            'pathmatch'          => '/mod/forum/post.php%',
            'enabled'            => 1,
            'sortorder'          => 0,
            'configdata'         => json_encode([
                'filtervalues' => ['role' => ['editingteacher']],
            ]),
            'endtourlabel'       => '',
            'displaystepnumbers' => 1,
            'showtourwhen'       => 1,
        ]);
        tour_manager::add_tour_to_category((int) $category->id, (int) $tourid, 1);

        $this->assertTrue(
            $DB->record_exists('local_lhonb_map',
                ['categoryid' => $category->id, 'tourid' => $tourid]),
            'Precondition: mapping row must exist before the unmap call.'
        );

        $removed = tour_importer::unmap_tour_from_category(
            'LernHive: Test Announcements',
            'communication'
        );

        $this->assertTrue($removed, 'unmap should report success when it drops a mapping.');
        $this->assertFalse(
            $DB->record_exists('local_lhonb_map',
                ['categoryid' => $category->id, 'tourid' => $tourid]),
            'Mapping row must be gone after unmap.'
        );
        $this->assertTrue(
            $DB->record_exists('tool_usertours_tours', ['id' => $tourid]),
            'The tool_usertours_tours row must survive — admin edits must not be lost.'
        );
    }

    /**
     * `unmap_tour_from_category()` must no-op (return false) when the
     * tour is not in the DB at all. Fresh 0.2.8 installs never
     * imported the announcements tour under Level 1, so the upgrade
     * step must not fail on them.
     */
    public function test_unmap_tour_from_category_noop_when_tour_missing(): void {
        $this->resetAfterTest();

        tour_importer::seed_categories();

        $removed = tour_importer::unmap_tour_from_category(
            'LernHive: Totally Nonexistent Tour',
            'communication'
        );

        $this->assertFalse($removed);
    }
}
