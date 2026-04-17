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
 * Unit tests for the tour start-URL placeholder resolver.
 *
 * @package    local_lernhive_onboarding
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_onboarding;

use advanced_testcase;
use PHPUnit\Framework\Attributes\CoversClass;

defined('MOODLE_INTERNAL') || die();

#[CoversClass(start_url_resolver::class)]
final class start_url_resolver_test extends advanced_testcase {

    /**
     * {USERID} is substituted with the userid passed to resolve().
     */
    public function test_userid_placeholder_is_substituted(): void {
        $this->resetAfterTest();

        $url = start_url_resolver::resolve('/user/editadvanced.php?id={USERID}', 42);

        // Compare against an equivalently-constructed moodle_url so the
        // assertion is wwwroot-subpath agnostic — on a CI runner whose
        // $CFG->wwwroot has a subpath (e.g. http://host/moodle),
        // get_path() includes that subpath on both sides of the compare.
        $expectedpath = (new \moodle_url('/user/editadvanced.php'))->get_path();
        $this->assertSame($expectedpath, $url->get_path());
        $this->assertSame('42', $url->get_param('id'));
    }

    /**
     * {SYSCONTEXTID} is replaced with the live system context ID.
     */
    public function test_syscontextid_placeholder_is_substituted(): void {
        $this->resetAfterTest();

        $expected = (string) \context_system::instance()->id;
        $url = start_url_resolver::resolve(
            '/admin/roles/assign.php?contextid={SYSCONTEXTID}',
            1
        );

        $this->assertSame($expected, $url->get_param('contextid'));
    }

    /**
     * {SITEID} resolves to the Frontpage course ID.
     */
    public function test_siteid_placeholder_is_substituted(): void {
        $this->resetAfterTest();

        $url = start_url_resolver::resolve('/course/view.php?id={SITEID}', 1);

        $this->assertSame((string) SITEID, $url->get_param('id'));
    }

    /**
     * {DEMOCOURSEID} pulls from `local_lernhive_onboarding.democourseid`
     * when the Onboarding Sandbox course has been provisioned.
     */
    public function test_democourseid_placeholder_pulls_from_plugin_config(): void {
        $this->resetAfterTest();

        set_config('democourseid', 777, 'local_lernhive_onboarding');

        $url = start_url_resolver::resolve('/course/view.php?id={DEMOCOURSEID}', 1);

        $this->assertSame('777', $url->get_param('id'));
    }

    /**
     * When no sandbox course is configured yet the resolver must still
     * produce a URL — callers are expected to notice the `id=0` and
     * surface a debug notice rather than silently redirect to a broken
     * path. Tested explicitly because a fresh install has no config row.
     */
    public function test_democourseid_defaults_to_zero_when_unset(): void {
        $this->resetAfterTest();

        $url = start_url_resolver::resolve('/course/view.php?id={DEMOCOURSEID}', 1);

        $this->assertSame('0', $url->get_param('id'));
    }

    /**
     * `{TRAINERCOURSECATEGORYID}` pulls from the plugin config key
     * `local_lernhive_onboarding.trainercoursecategoryid`, which is the
     * admin-configurable target category for the "trainer creates a new
     * course" tour (see LH-ONB-START-05).
     */
    public function test_trainercoursecategoryid_placeholder_pulls_from_plugin_config(): void {
        $this->resetAfterTest();

        set_config('trainercoursecategoryid', 42, 'local_lernhive_onboarding');

        $url = start_url_resolver::resolve(
            '/course/edit.php?category={TRAINERCOURSECATEGORYID}',
            1
        );

        $this->assertSame('42', $url->get_param('category'));
    }

    /**
     * `{TRAINERCOURSECATEGORYID}` falls back to `1` (Moodle's default
     * Miscellaneous category) when the admin has never opened the
     * settings page AND the install-time default seeding has not run
     * yet. This is the belt-and-braces floor so the "create course"
     * tour always resolves to *some* real category page.
     */
    public function test_trainercoursecategoryid_defaults_to_one_when_unset(): void {
        $this->resetAfterTest();

        // Explicitly wipe any value that bootstrap seeding may have
        // written — we want to exercise the `?: 1` fallback inside
        // resolve() and nothing else.
        unset_config('trainercoursecategoryid', 'local_lernhive_onboarding');

        $url = start_url_resolver::resolve(
            '/course/edit.php?category={TRAINERCOURSECATEGORYID}',
            1
        );

        $this->assertSame('1', $url->get_param('category'));
    }

    /**
     * An empty template is a coding error — callers must fall back to
     * the pathmatch strip BEFORE invoking the resolver.
     */
    public function test_empty_template_throws_coding_exception(): void {
        $this->expectException(\coding_exception::class);
        start_url_resolver::resolve('', 1);
    }

    /**
     * Whitespace-only templates count as empty.
     */
    public function test_whitespace_only_template_throws_coding_exception(): void {
        $this->expectException(\coding_exception::class);
        start_url_resolver::resolve("   \t\n", 1);
    }

    /**
     * Unknown placeholders are kept literal. This is deliberate:
     * a tour authored against a later plugin version that introduces
     * a new placeholder must still load cleanly on an older plugin,
     * producing a 404 (loud failure) rather than a silent wrong
     * redirect. Known placeholders in the same string are still
     * substituted.
     */
    public function test_unknown_placeholder_stays_literal(): void {
        $this->resetAfterTest();

        $url = start_url_resolver::resolve(
            '/some/path.php?future={FUTUREID}&user={USERID}',
            99
        );

        // Known placeholder still works.
        $this->assertSame('99', $url->get_param('user'));
        // Unknown placeholder is preserved verbatim.
        $this->assertSame('{FUTUREID}', $url->get_param('future'));
    }
}
