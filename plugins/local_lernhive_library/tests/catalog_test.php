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
 * Unit tests for the Library catalog provider and entry contract.
 *
 * These tests lock in manifest parsing behaviour, seeded-entry
 * behaviour, and template contract assumptions used by the page
 * renderable and template.
 *
 * @package    local_lernhive_library
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_library;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

/**
 * @covers \local_lernhive_library\catalog
 * @covers \local_lernhive_library\catalog_entry
 */
final class catalog_test extends advanced_testcase {

    /**
     * A default-constructed catalog is empty when no manifest is configured.
     */
    public function test_default_catalog_is_empty(): void {
        $this->resetAfterTest();

        $catalog = new catalog();

        $this->assertTrue($catalog->is_empty());
        $this->assertSame([], $catalog->all());
    }

    /**
     * Seeded entries pass through in insertion order so the page
     * can rely on the catalog source for ordering decisions.
     */
    public function test_seeded_catalog_preserves_order(): void {
        $this->resetAfterTest();

        $first = $this->make_entry('a', 'Alpha');
        $second = $this->make_entry('b', 'Bravo');
        $third = $this->make_entry('c', 'Charlie');

        $catalog = new catalog([$first, $second, $third]);

        $this->assertFalse($catalog->is_empty());
        $this->assertCount(3, $catalog->all());
        $this->assertSame(['a', 'b', 'c'], array_map(
            static fn(catalog_entry $e) => $e->id,
            $catalog->all(),
        ));
    }

    /**
     * Catalog may load entries from a JSON manifest (R2 mode).
     */
    public function test_catalog_loads_entries_from_manifest_array(): void {
        $this->resetAfterTest();

        $manifest = json_encode([
            [
                'id' => 'course-101',
                'title' => 'Course 101',
                'description' => 'Managed entry',
                'version' => '1.2.3',
                'updated' => 1700000000,
                'language' => 'en',
            ],
        ]);

        $catalog = new catalog(null, $manifest);

        $this->assertFalse($catalog->is_empty());
        $this->assertCount(1, $catalog->all());
        $this->assertSame('course-101', $catalog->all()[0]->id);
    }

    /**
     * Object manifests with an `entries` key are accepted as well.
     */
    public function test_catalog_loads_entries_from_manifest_entries_key(): void {
        $this->resetAfterTest();

        $manifest = json_encode([
            'entries' => [
                [
                    'id' => 'course-202',
                    'title' => 'Course 202',
                    'description' => 'Managed entry',
                    'version' => '2.0.0',
                    'updated' => '2026-01-01 12:00:00 UTC',
                    'language' => 'de',
                ],
            ],
        ]);

        $catalog = new catalog(null, $manifest);

        $this->assertFalse($catalog->is_empty());
        $this->assertCount(1, $catalog->all());
        $this->assertSame('course-202', $catalog->all()[0]->id);
    }

    /**
     * Default constructor reads manifest JSON from plugin config.
     */
    public function test_catalog_loads_manifest_from_plugin_config(): void {
        $this->resetAfterTest();

        $manifest = json_encode([
            [
                'id' => 'config-course',
                'title' => 'Config course',
                'description' => 'Managed entry from config',
                'version' => '1.0.0',
                'updated' => 1700000000,
                'language' => 'en',
            ],
        ]);
        set_config('catalog_manifest_json', $manifest, 'local_lernhive_library');

        $catalog = new catalog();

        $this->assertFalse($catalog->is_empty());
        $this->assertCount(1, $catalog->all());
        $this->assertSame('config-course', $catalog->all()[0]->id);
    }

    /**
     * Invalid JSON manifests fail closed and return an empty catalog.
     */
    public function test_catalog_ignores_invalid_manifest_json(): void {
        $this->resetAfterTest();

        $catalog = new catalog(null, '{invalid json');

        $this->assertTrue($catalog->is_empty());
        $this->assertSame([], $catalog->all());
    }

    /**
     * Invalid rows are ignored while valid rows remain available.
     */
    public function test_catalog_skips_invalid_manifest_rows(): void {
        $this->resetAfterTest();

        $manifest = json_encode([
            [
                'id' => 'ok-row',
                'title' => 'Valid row',
                'description' => 'desc',
                'version' => '1.0.0',
                'updated' => '1700000000',
                'language' => 'en',
            ],
            [
                'id' => '',
                'title' => 'Invalid row',
                'description' => 'desc',
                'version' => '1.0.0',
                'updated' => 1700000000,
                'language' => 'en',
            ],
        ]);

        $catalog = new catalog(null, $manifest);

        $this->assertCount(1, $catalog->all());
        $this->assertSame('ok-row', $catalog->all()[0]->id);
    }

    /**
     * Seed lists must contain catalog_entry objects only.
     */
    public function test_catalog_rejects_invalid_seed_entry_types(): void {
        $this->resetAfterTest();

        $this->expectException(\coding_exception::class);
        new catalog([new \stdClass()]);
    }

    /**
     * The template context exposes exactly the keys the mustache
     * template consumes — if this changes, update the template too.
     */
    public function test_entry_template_context_has_expected_keys(): void {
        $this->resetAfterTest();

        $entry = $this->make_entry('demo', 'Demo course');
        $context = $entry->to_template_context();

        $this->assertArrayHasKey('id', $context);
        $this->assertArrayHasKey('title', $context);
        $this->assertArrayHasKey('description', $context);
        $this->assertArrayHasKey('version', $context);
        $this->assertArrayHasKey('updated', $context);
        $this->assertArrayHasKey('language', $context);
    }

    /**
     * Language codes are normalised to upper-case for display, but
     * the source is still free to supply lower-case ISO codes.
     */
    public function test_entry_uppercases_language_for_display(): void {
        $this->resetAfterTest();

        $entry = new catalog_entry(
            id: 'demo',
            title: 'Demo',
            description: 'desc',
            version: '1.0.0',
            updated: 1700000000,
            language: ' de ',
        );

        $context = $entry->to_template_context();
        $this->assertSame('DE', $context['language']);
    }

    /**
     * The updated timestamp is passed through Moodle's userdate so
     * admins see a localised date instead of a unix epoch.
     */
    public function test_entry_formats_updated_as_human_date(): void {
        $this->resetAfterTest();

        $entry = $this->make_entry('demo', 'Demo');
        $context = $entry->to_template_context();

        // We don't assert the exact locale format — just that it's a
        // non-empty string and not the raw integer.
        $this->assertIsString($context['updated']);
        $this->assertNotSame('1700000000', $context['updated']);
        $this->assertNotEmpty($context['updated']);
    }

    /**
     * @dataProvider invalid_entry_contract_provider
     * @param string $id
     * @param string $title
     * @param string $version
     * @param int $updated
     * @param string $language
     * @param string $expectedmessagepart
     */
    public function test_entry_rejects_invalid_contract_data(
        string $id,
        string $title,
        string $version,
        int $updated,
        string $language,
        string $expectedmessagepart,
    ): void {
        $this->resetAfterTest();

        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage($expectedmessagepart);

        new catalog_entry(
            id: $id,
            title: $title,
            description: 'desc',
            version: $version,
            updated: $updated,
            language: $language,
        );
    }

    /**
     * Helper: build a catalog entry with sensible defaults.
     */
    private function make_entry(string $id, string $title): catalog_entry {
        return new catalog_entry(
            id: $id,
            title: $title,
            description: 'Short description for ' . $title,
            version: '1.0.0',
            updated: 1700000000,
            language: 'en',
        );
    }

    /**
     * @return array
     */
    public static function invalid_entry_contract_provider(): array {
        return [
            'blank id' => ['', 'Demo', '1.0.0', 1700000000, 'en', '"id"'],
            'blank title' => ['demo', ' ', '1.0.0', 1700000000, 'en', '"title"'],
            'blank version' => ['demo', 'Demo', '', 1700000000, 'en', '"version"'],
            'blank language' => ['demo', 'Demo', '1.0.0', 1700000000, ' ', '"language"'],
            'negative updated' => ['demo', 'Demo', '1.0.0', -1, 'en', '"updated"'],
        ];
    }
}
