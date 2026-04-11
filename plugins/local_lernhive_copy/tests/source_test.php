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
 * Unit tests for the source normalisation value object.
 *
 * @package    local_lernhive_copy
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_copy;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

/**
 * @covers \local_lernhive_copy\source
 */
final class source_test extends advanced_testcase {

    /**
     * "template" → template source with the right suffix.
     */
    public function test_template_source_is_recognised(): void {
        $this->resetAfterTest();

        $source = source::from_request('template');
        $this->assertTrue($source->is_template());
        $this->assertSame(source::TYPE_TEMPLATE, $source->type);
        $this->assertSame('template', $source->string_suffix());
    }

    /**
     * null → default course source.
     */
    public function test_null_source_defaults_to_course(): void {
        $this->resetAfterTest();

        $source = source::from_request(null);
        $this->assertFalse($source->is_template());
        $this->assertSame(source::TYPE_COURSE, $source->type);
        $this->assertSame('course', $source->string_suffix());
    }

    /**
     * Unknown values fall back to the course source — this is the
     * safety guard against stray or tampered query parameters.
     *
     * @dataProvider unknown_sources
     */
    public function test_unknown_sources_fall_back_to_course(string $raw): void {
        $this->resetAfterTest();

        $source = source::from_request($raw);
        $this->assertSame(source::TYPE_COURSE, $source->type);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unknown_sources(): array {
        return [
            'empty'         => [''],
            'random word'   => ['foo'],
            'sql injection' => ["template' OR 1=1"],
            'case mismatch' => ['Template'],
        ];
    }
}
