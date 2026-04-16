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
 * @covers \local_lernhive_library\catalog_manifest_parser
 * @covers \local_lernhive_library\manifest_catalog_source
 * @covers \local_lernhive_library\remote_catalog_source
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
     * Catalog can be fed from any source implementation.
     */
    public function test_catalog_uses_explicit_source_override(): void {
        $this->resetAfterTest();

        $source = new class implements catalog_source {
            /**
             * @return catalog_entry[]
             */
            public function load_entries(): array {
                return [
                    new catalog_entry(
                        id: 'from-source',
                        title: 'Source entry',
                        description: 'desc',
                        version: '1.0.0',
                        updated: 1700000000,
                        language: 'en',
                    ),
                ];
            }
        };

        $catalog = new catalog(null, null, $source);

        $this->assertCount(1, $catalog->all());
        $this->assertSame('from-source', $catalog->all()[0]->id);
    }

    /**
     * Explicit manifest overrides should bypass remote source selection.
     */
    public function test_catalog_prefers_explicit_manifest_override_over_feed_config(): void {
        $this->resetAfterTest();

        set_config('catalog_feed_url', 'https://invalid.example.invalid/feed.json', 'local_lernhive_library');
        $manifest = json_encode([
            [
                'id' => 'manifest-wins',
                'title' => 'Manifest wins',
                'description' => 'desc',
                'version' => '1.0.0',
                'updated' => 1700000000,
                'language' => 'en',
            ],
        ]);

        $catalog = new catalog(null, $manifest);

        $this->assertCount(1, $catalog->all());
        $this->assertSame('manifest-wins', $catalog->all()[0]->id);
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
                'sourcecourseid' => 42,
            ],
        ]);

        $catalog = new catalog(null, $manifest);

        $this->assertFalse($catalog->is_empty());
        $this->assertCount(1, $catalog->all());
        $this->assertSame('course-101', $catalog->all()[0]->id);
        $this->assertSame(42, $catalog->all()[0]->sourcecourseid);
        $this->assertTrue($catalog->all()[0]->has_source_course());
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
     * Parser keeps a valid row even if sourcecourseid is invalid.
     */
    public function test_catalog_ignores_invalid_sourcecourseid_value(): void {
        $this->resetAfterTest();

        $manifest = json_encode([
            [
                'id' => 'ok-row',
                'title' => 'Valid row',
                'description' => 'desc',
                'version' => '1.0.0',
                'updated' => 1700000000,
                'language' => 'en',
                'sourcecourseid' => -10,
            ],
        ]);

        $catalog = new catalog(null, $manifest);

        $this->assertCount(1, $catalog->all());
        $this->assertSame('ok-row', $catalog->all()[0]->id);
        $this->assertNull($catalog->all()[0]->sourcecourseid);
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
                'sourcecourseid' => -10,
            ],
        ]);

        $catalog = new catalog(null, $manifest);

        $this->assertCount(1, $catalog->all());
        $this->assertSame('ok-row', $catalog->all()[0]->id);
        $this->assertNull($catalog->all()[0]->sourcecourseid);
    }

    /**
     * Remote source can decode managed feed payloads.
     */
    public function test_remote_catalog_source_loads_entries_from_fetcher(): void {
        $this->resetAfterTest();

        $remote = new remote_catalog_source(
            feedurl: 'https://catalog.example/feed.json',
            apitoken: 'secret',
            fetcher: static function(string $url, string $token): ?string {
                if ($url !== 'https://catalog.example/feed.json') {
                    return null;
                }
                if ($token !== 'secret') {
                    return null;
                }
                return json_encode([
                    [
                        'id' => 'remote-course',
                        'title' => 'Remote course',
                        'description' => 'desc',
                        'version' => '3.0.0',
                        'updated' => 1700000200,
                        'language' => 'en',
                    ],
                ]);
            },
        );

        $entries = $remote->load_entries();

        $this->assertCount(1, $entries);
        $this->assertSame('remote-course', $entries[0]->id);
    }

    /**
     * Remote source remains empty when no URL is configured.
     */
    public function test_remote_catalog_source_returns_empty_without_url(): void {
        $this->resetAfterTest();

        $remote = new remote_catalog_source(
            feedurl: '',
            fetcher: static fn(string $url, string $token): ?string => '[]',
        );

        $this->assertSame([], $remote->load_entries());
    }

    /**
     * Remote source fails closed when fetching fails.
     */
    public function test_remote_catalog_source_fails_closed_on_fetch_error(): void {
        $this->resetAfterTest();

        $remote = new remote_catalog_source(
            feedurl: 'https://catalog.example/feed.json',
            fetcher: static fn(string $url, string $token): ?string => null,
        );

        $this->assertSame([], $remote->load_entries());
    }

    /**
     * Remote source reads URL/token from plugin config when omitted.
     */
    public function test_remote_catalog_source_uses_config_when_not_overridden(): void {
        $this->resetAfterTest();

        set_config('catalog_feed_url', 'https://catalog.example/feed.json', 'local_lernhive_library');
        set_config('catalog_feed_token', 'from-config', 'local_lernhive_library');

        $seenurl = '';
        $seentoken = '';
        $remote = new remote_catalog_source(
            fetcher: static function(string $url, string $token) use (&$seenurl, &$seentoken): ?string {
                $seenurl = $url;
                $seentoken = $token;
                return '[]';
            },
        );

        $remote->load_entries();

        $this->assertSame('https://catalog.example/feed.json', $seenurl);
        $this->assertSame('from-config', $seentoken);
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
     * `find_by_id()` returns matching entries and null for unknown ids.
     */
    public function test_find_by_id_resolves_entries(): void {
        $this->resetAfterTest();

        $first = $this->make_entry('a', 'Alpha');
        $catalog = new catalog([$first]);

        $this->assertSame($first, $catalog->find_by_id('a'));
        $this->assertNull($catalog->find_by_id('missing'));
        $this->assertNull($catalog->find_by_id(''));
    }

    /**
     * Copy template mode: selected template id resolves to a usable
     * source-course mapping when catalog data contains sourcecourseid.
     */
    public function test_copy_template_lookup_resolves_source_course_mapping(): void {
        $this->resetAfterTest();

        $catalog = new catalog(null, json_encode([
            [
                'id' => 'template-a',
                'title' => 'Template A',
                'description' => 'Template with source mapping',
                'version' => '1.0.0',
                'updated' => 1700000000,
                'language' => 'en',
                'sourcecourseid' => 123,
            ],
        ]));

        $selected = $catalog->find_by_id('template-a');

        $this->assertInstanceOf(catalog_entry::class, $selected);
        $this->assertTrue($selected->has_source_course());
        $this->assertSame(123, $selected->sourcecourseid);
    }

    /**
     * Copy template mode: selected template without sourcecourseid must
     * remain non-actionable (findable entry, but no source mapping).
     */
    public function test_copy_template_lookup_rejects_entry_without_source_course_mapping(): void {
        $this->resetAfterTest();

        $catalog = new catalog(null, json_encode([
            [
                'id' => 'template-b',
                'title' => 'Template B',
                'description' => 'Template without source mapping',
                'version' => '1.0.0',
                'updated' => 1700000000,
                'language' => 'en',
            ],
        ]));

        $selected = $catalog->find_by_id('template-b');

        $this->assertInstanceOf(catalog_entry::class, $selected);
        $this->assertFalse($selected->has_source_course());
        $this->assertNull($selected->sourcecourseid);
    }

    /**
     * Copy template mode: unknown template id must fail closed.
     */
    public function test_copy_template_lookup_returns_null_for_unknown_template_id(): void {
        $this->resetAfterTest();

        $catalog = new catalog(null, json_encode([
            [
                'id' => 'template-a',
                'title' => 'Template A',
                'description' => 'Template with source mapping',
                'version' => '1.0.0',
                'updated' => 1700000000,
                'language' => 'en',
                'sourcecourseid' => 123,
            ],
        ]));

        $this->assertNull($catalog->find_by_id('template-missing'));
    }

    /**
     * Copy template mode should keep working for remote-feed sourced data.
     */
    public function test_copy_template_lookup_works_with_remote_feed_source(): void {
        $this->resetAfterTest();

        $remote = new remote_catalog_source(
            feedurl: 'https://catalog.example/feed.json',
            fetcher: static fn(string $url, string $token): ?string => json_encode([
                [
                    'id' => 'template-remote',
                    'title' => 'Remote template',
                    'description' => 'Remote mapped template',
                    'version' => '4.0.0',
                    'updated' => 1700000300,
                    'language' => 'en',
                    'sourcecourseid' => 999,
                ],
            ]),
        );

        $catalog = new catalog(null, null, $remote);
        $selected = $catalog->find_by_id('template-remote');

        $this->assertInstanceOf(catalog_entry::class, $selected);
        $this->assertTrue($selected->has_source_course());
        $this->assertSame(999, $selected->sourcecourseid);
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
        $this->assertArrayHasKey('hassourcecourse', $context);
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
