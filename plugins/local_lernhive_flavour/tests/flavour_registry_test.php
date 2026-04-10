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
 * Unit tests for the flavour registry.
 *
 * @package    local_lernhive_flavour
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_lernhive_flavour\flavour_registry
 */

namespace local_lernhive_flavour;

defined('MOODLE_INTERNAL') || die();

/**
 * @runTestsInSeparateProcesses
 */
final class flavour_registry_test extends \advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        flavour_registry::reset_cache();
    }

    public function test_all_returns_four_known_flavours(): void {
        $all = flavour_registry::all();
        $this->assertCount(4, $all);
        $this->assertArrayHasKey('school', $all);
        $this->assertArrayHasKey('lxp', $all);
        $this->assertArrayHasKey('highered', $all);
        $this->assertArrayHasKey('corporate', $all);
    }

    public function test_school_is_listed_first(): void {
        $keys = flavour_registry::keys();
        $this->assertSame('school', $keys[0]);
    }

    public function test_get_returns_profile_for_known_key(): void {
        $profile = flavour_registry::get('lxp');
        $this->assertNotNull($profile);
        $this->assertSame('lxp', $profile->get_key());
    }

    public function test_get_returns_null_for_unknown_key(): void {
        $this->assertNull(flavour_registry::get('atlantis'));
    }

    public function test_exists_returns_expected_boolean(): void {
        $this->assertTrue(flavour_registry::exists('school'));
        $this->assertTrue(flavour_registry::exists('lxp'));
        $this->assertTrue(flavour_registry::exists('highered'));
        $this->assertTrue(flavour_registry::exists('corporate'));
        $this->assertFalse(flavour_registry::exists(''));
        $this->assertFalse(flavour_registry::exists('nonsense'));
    }

    public function test_default_flavour_is_registered(): void {
        $this->assertTrue(flavour_registry::exists(flavour_registry::DEFAULT_FLAVOUR));
    }

    public function test_stable_profiles_have_stable_maturity(): void {
        $this->assertSame(
            flavour_definition::MATURITY_STABLE,
            flavour_registry::get('school')->get_maturity()
        );
        $this->assertSame(
            flavour_definition::MATURITY_STABLE,
            flavour_registry::get('lxp')->get_maturity()
        );
    }

    public function test_stub_profiles_are_flagged_experimental(): void {
        $this->assertSame(
            flavour_definition::MATURITY_EXPERIMENTAL,
            flavour_registry::get('highered')->get_maturity()
        );
        $this->assertSame(
            flavour_definition::MATURITY_EXPERIMENTAL,
            flavour_registry::get('corporate')->get_maturity()
        );
    }

    public function test_school_defaults_use_correct_local_lernhive_keys(): void {
        $defaults = flavour_registry::get('school')->get_defaults();
        $this->assertArrayHasKey('local_lernhive', $defaults);

        // These are the exact keys defined in local_lernhive/settings.php.
        // The pre-refactor stub got them wrong (allow_course_creation etc.),
        // so we assert the corrected names explicitly here.
        $expected = [
            'default_level',
            'show_levelbar',
            'allow_teacher_course_creation',
            'teacher_category_parent',
            'allow_teacher_user_creation',
            'allow_teacher_user_browse',
        ];
        foreach ($expected as $key) {
            $this->assertArrayHasKey(
                $key,
                $defaults['local_lernhive'],
                "School profile must set local_lernhive/{$key}"
            );
        }
    }

    public function test_lxp_disables_teacher_powers(): void {
        $defaults = flavour_registry::get('lxp')->get_defaults();
        $this->assertSame(0, $defaults['local_lernhive']['allow_teacher_course_creation']);
        $this->assertSame(0, $defaults['local_lernhive']['allow_teacher_user_creation']);
        $this->assertSame(0, $defaults['local_lernhive']['allow_teacher_user_browse']);
        // LXP hides the level bar because its UX is discovery-first, not progression-first.
        $this->assertSame(0, $defaults['local_lernhive']['show_levelbar']);
    }

    public function test_stub_profiles_inherit_school_defaults(): void {
        $school = flavour_registry::get('school')->get_defaults();
        $highered = flavour_registry::get('highered')->get_defaults();
        $corporate = flavour_registry::get('corporate')->get_defaults();
        $this->assertSame($school, $highered);
        $this->assertSame($school, $corporate);
    }

    public function test_every_profile_has_non_empty_managed_keys(): void {
        foreach (flavour_registry::all() as $profile) {
            $this->assertNotEmpty(
                $profile->get_managed_keys(),
                "Profile {$profile->get_key()} declares no managed keys"
            );
        }
    }
}
