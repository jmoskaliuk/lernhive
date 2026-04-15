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
 * Unit tests for the catalog_page renderable.
 *
 * @package    local_lernhive_library
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_library\output;

use advanced_testcase;
use local_lernhive_library\catalog;
use local_lernhive_library\catalog_entry;
use renderer_base;

defined('MOODLE_INTERNAL') || die();

/**
 * @covers \local_lernhive_library\output\catalog_page
 */
final class catalog_page_test extends advanced_testcase {

    /**
     * Empty catalog exports an empty-state context.
     */
    public function test_export_for_template_empty_catalog(): void {
        $this->resetAfterTest();

        $page = new catalog_page(new catalog());
        $context = $page->export_for_template($this->make_renderer_stub());

        $this->assertTrue($context['empty']);
        $this->assertSame([], $context['entries']);
        $this->assertSame(get_string('catalog_heading', 'local_lernhive_library'), $context['heading']);
        $this->assertSame(get_string('catalog_intro', 'local_lernhive_library'), $context['intro']);
        $this->assertSame(get_string('catalog_empty', 'local_lernhive_library'), $context['emptymsg']);
        $this->assertSame(
            [
                'version' => get_string('label_version', 'local_lernhive_library'),
                'updated' => get_string('label_updated', 'local_lernhive_library'),
                'language' => get_string('label_language', 'local_lernhive_library'),
                'import' => get_string('btn_import', 'local_lernhive_library'),
            ],
            $context['labels'],
        );
    }

    /**
     * Seeded entries are exported in source order for mustache rendering.
     */
    public function test_export_for_template_with_seeded_entries(): void {
        $this->resetAfterTest();

        $catalog = new catalog([
            new catalog_entry(
                id: 'course-a',
                title: 'Course A',
                description: 'Alpha entry',
                version: '1.1.0',
                updated: 1700000000,
                language: 'de',
            ),
            new catalog_entry(
                id: 'course-b',
                title: 'Course B',
                description: 'Bravo entry',
                version: '2.0.0',
                updated: 1700000100,
                language: 'en',
            ),
        ]);

        $page = new catalog_page($catalog);
        $context = $page->export_for_template($this->make_renderer_stub());

        $this->assertFalse($context['empty']);
        $this->assertCount(2, $context['entries']);
        $this->assertSame('course-a', $context['entries'][0]['id']);
        $this->assertSame('Course A', $context['entries'][0]['title']);
        $this->assertSame('DE', $context['entries'][0]['language']);
        $this->assertSame('course-b', $context['entries'][1]['id']);
        $this->assertSame('Course B', $context['entries'][1]['title']);
        $this->assertSame('EN', $context['entries'][1]['language']);
    }

    /**
     * Empty-state flag follows exported entries, even if a custom catalog
     * implementation reports a divergent is_empty() value.
     */
    public function test_export_for_template_derives_empty_flag_from_entries(): void {
        $this->resetAfterTest();

        $catalog = new class extends catalog {
            /**
             * @return catalog_entry[]
             */
            public function all(): array {
                return [
                    new catalog_entry(
                        id: 'course-x',
                        title: 'Course X',
                        description: 'Custom source',
                        version: '1.0.0',
                        updated: 1700000000,
                        language: 'en',
                    ),
                ];
            }

            /**
             * Intentionally divergent to prove catalog_page relies on entries.
             *
             * @return bool
             */
            public function is_empty(): bool {
                return true;
            }
        };

        $context = (new catalog_page($catalog))->export_for_template($this->make_renderer_stub());

        $this->assertFalse($context['empty']);
        $this->assertCount(1, $context['entries']);
        $this->assertSame('course-x', $context['entries'][0]['id']);
    }

    /**
     * Renderer is unused by export_for_template; a PHPUnit mock is enough.
     *
     * @return renderer_base
     */
    private function make_renderer_stub(): renderer_base {
        return $this->createMock(renderer_base::class);
    }
}
