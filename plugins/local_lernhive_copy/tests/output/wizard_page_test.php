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
 * Unit tests for the copy wizard renderable.
 *
 * @package    local_lernhive_copy
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_copy\output;

use advanced_testcase;
use local_lernhive_copy\source;
use renderer_base;

defined('MOODLE_INTERNAL') || die();

/**
 * @covers \local_lernhive_copy\output\wizard_page
 */
final class wizard_page_test extends advanced_testcase {

    /**
     * Course source + non-empty form HTML sets hasform and simple mode context.
     */
    public function test_export_with_form_sets_hasform(): void {
        $this->resetAfterTest();

        $page = new wizard_page(
            source::from_request(null),
            '<form id="demo"></form>',
            [
                'mode' => 'simple',
                'showmodetoggle' => true,
                'simpleurl' => '/local/lernhive_copy/index.php',
                'experturl' => '/local/lernhive_copy/index.php?mode=expert',
                'returnurl' => '/local/lernhive_contenthub/index.php',
            ]
        );

        $context = $page->export_for_template($this->make_renderer_stub());

        $this->assertFalse($context['istemplate']);
        $this->assertTrue($context['hasform']);
        $this->assertSame('<form id="demo"></form>', $context['formhtml']);
        $this->assertTrue($context['showmodetoggle']);
        $this->assertTrue($context['modeissimple']);
        $this->assertFalse($context['modeisexpert']);
        $this->assertSame('/local/lernhive_copy/index.php', $context['simpleurl']);
        $this->assertSame('/local/lernhive_copy/index.php?mode=expert', $context['experturl']);
    }

    /**
     * Template source without form can expose template list context.
     */
    public function test_export_template_mode_without_form(): void {
        $this->resetAfterTest();

        $page = new wizard_page(
            source::from_request('template'),
            null,
            [
                'mode' => 'expert',
                'showmodetoggle' => false,
                'showtemplatelist' => true,
                'templateempty' => false,
                'templateentries' => [
                    [
                        'id' => 'tpl-a',
                        'title' => 'Template A',
                        'description' => 'First template',
                        'version' => '1.0.0',
                        'updated' => '1 January 2026',
                        'language' => 'EN',
                        'hasaction' => true,
                        'actionurl' => '/local/lernhive_copy/index.php?source=template&templateid=tpl-a',
                        'isactive' => true,
                    ],
                ],
                'templatewarning' => 'Warn',
                'activetemplate' => 'Template A',
                'returnurl' => '/local/lernhive_contenthub/index.php',
            ]
        );

        $context = $page->export_for_template($this->make_renderer_stub());

        $this->assertTrue($context['istemplate']);
        $this->assertFalse($context['hasform']);
        $this->assertFalse($context['showmodetoggle']);
        $this->assertTrue($context['showtemplatelist']);
        $this->assertFalse($context['templateempty']);
        $this->assertCount(1, $context['templateentries']);
        $this->assertSame('tpl-a', $context['templateentries'][0]['id']);
        $this->assertSame('Warn', $context['template_warning']);
        $this->assertSame('Template A', $context['active_template']);
        $this->assertFalse($context['showstub']);
    }

    /**
     * Empty form HTML should not count as hasform.
     */
    public function test_export_empty_form_html_is_treated_as_no_form(): void {
        $this->resetAfterTest();

        $page = new wizard_page(source::from_request(null), '');
        $context = $page->export_for_template($this->make_renderer_stub());

        $this->assertFalse($context['hasform']);
        $this->assertSame('', $context['formhtml']);
        $this->assertTrue($context['showstub']);
    }

    /**
     * Renderer is unused by export_for_template; a mock is sufficient.
     *
     * @return renderer_base
     */
    private function make_renderer_stub(): renderer_base {
        return $this->createMock(renderer_base::class);
    }
}
