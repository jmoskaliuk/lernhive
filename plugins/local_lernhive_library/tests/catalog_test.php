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
 * Unit tests for the R1 library catalog stub.
 *
 * These tests lock in the contract the eventual managed catalog
 * backend must satisfy: empty state by default, ordered pass-through
 * of injected entries, and a template context shape the mustache
 * template can consume without guessing keys.
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
     * A default-constructed catalog is empty — this is the R1 guarantee.
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
            language: 'de',
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
}
