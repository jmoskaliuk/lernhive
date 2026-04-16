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
 * Unit tests for reporting export links in renderable contexts.
 *
 * @package    local_lernhive_reporting
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_reporting\output;

use advanced_testcase;
use renderer_base;

defined('MOODLE_INTERNAL') || die();

/**
 * @covers \local_lernhive_reporting\output\users_page
 * @covers \local_lernhive_reporting\output\popular_page
 * @covers \local_lernhive_reporting\output\completion_page
 */
final class export_links_test extends advanced_testcase {

    /**
     * Users drilldown export link keeps CSV route + sesskey + selected course.
     */
    public function test_users_page_export_link_contains_expected_parameters(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course(['fullname' => 'Users Export Course']);
        $context = (new users_page((int)$course->id))->export_for_template($this->make_renderer_stub());
        $params = $this->parse_url_params($context['exporturl']);

        $this->assertTrue($context['hasselectedcourse']);
        $this->assertSame('csv', $params['export'] ?? null);
        $this->assertSame(sesskey(), $params['sesskey'] ?? null);
        $this->assertSame((string)$course->id, (string)($params['courseid'] ?? ''));
    }

    /**
     * Popular drilldown export link exposes CSV route + sesskey.
     */
    public function test_popular_page_export_link_contains_expected_parameters(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->getDataGenerator()->create_course(['fullname' => 'Popular Export Course']);
        $context = (new popular_page())->export_for_template($this->make_renderer_stub());
        $params = $this->parse_url_params($context['exporturl']);

        $this->assertSame('csv', $params['export'] ?? null);
        $this->assertSame(sesskey(), $params['sesskey'] ?? null);
    }

    /**
     * Completion drilldown export link exposes CSV route + sesskey.
     */
    public function test_completion_page_export_link_contains_expected_parameters(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course(['fullname' => 'Completion Export Course']);
        $context = (new completion_page((int)$course->id))->export_for_template($this->make_renderer_stub());
        $params = $this->parse_url_params($context['exporturl']);

        $this->assertTrue($context['hasselectedcourse']);
        $this->assertSame('csv', $params['export'] ?? null);
        $this->assertSame(sesskey(), $params['sesskey'] ?? null);
    }

    /**
     * Renderer is unused by export_for_template; a mock is enough.
     *
     * @return renderer_base
     */
    private function make_renderer_stub(): renderer_base {
        return $this->createMock(renderer_base::class);
    }

    /**
     * Parse URL query params into an array.
     *
     * @param string $url
     * @return array<string, mixed>
     */
    private function parse_url_params(string $url): array {
        $query = (string)parse_url($url, PHP_URL_QUERY);
        $params = [];
        parse_str($query, $params);
        return $params;
    }
}
