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

namespace local_lernhive\feature;

use advanced_testcase;
use coding_exception;
use PHPUnit\Framework\Attributes\CoversClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for the feature definition value object.
 *
 * @package    local_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(definition::class)]
final class definition_test extends advanced_testcase {

    public function test_valid_feature_definition_exposes_all_fields(): void {
        $def = new definition(
            'mod_assign.create',
            2,
            'mod/assign:addinstance',
            'feature_mod_assign_create',
            'assignments',
            'lxp',
        );

        $this->assertSame('mod_assign.create', $def->featureid);
        $this->assertSame(2, $def->defaultlevel);
        $this->assertSame('mod/assign:addinstance', $def->requiredcapability);
        $this->assertSame('feature_mod_assign_create', $def->langkey);
        $this->assertSame('assignments', $def->categoryhint);
        $this->assertSame('lxp', $def->flavorhint);
    }

    public function test_flavor_hint_is_optional(): void {
        $def = new definition(
            'core.course.create',
            1,
            'moodle/course:create',
            'feature_core_course_create',
            'course_basics',
        );
        $this->assertNull($def->flavorhint);
    }

    public function test_empty_feature_id_is_rejected(): void {
        $this->expectException(coding_exception::class);
        new definition('', 1, 'mod/page:addinstance', 'feature_mod_page_create', 'content_basics');
    }

    public function test_level_below_minimum_is_rejected(): void {
        $this->expectException(coding_exception::class);
        new definition('foo.bar', 0, 'mod/page:addinstance', 'feature_foo_bar', 'content_basics');
    }

    public function test_level_above_maximum_is_rejected(): void {
        $this->expectException(coding_exception::class);
        new definition('foo.bar', 6, 'mod/page:addinstance', 'feature_foo_bar', 'content_basics');
    }

    public function test_empty_capability_is_rejected(): void {
        $this->expectException(coding_exception::class);
        new definition('foo.bar', 1, '', 'feature_foo_bar', 'content_basics');
    }

    public function test_empty_lang_key_is_rejected(): void {
        $this->expectException(coding_exception::class);
        new definition('foo.bar', 1, 'mod/page:addinstance', '', 'content_basics');
    }

    public function test_empty_category_hint_is_rejected(): void {
        $this->expectException(coding_exception::class);
        new definition('foo.bar', 1, 'mod/page:addinstance', 'feature_foo_bar', '');
    }

    public function test_min_and_max_level_constants(): void {
        $this->assertSame(1, definition::MIN_LEVEL);
        $this->assertSame(5, definition::MAX_LEVEL);
    }
}
