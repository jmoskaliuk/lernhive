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
 * Unit tests for the deterministic tour-start flow.
 *
 * Sesskey enforcement is NOT covered here — it is a `require_sesskey()`
 * guard in `starttour.php` itself, tested by Behat
 * (`tour_start_from_catalog.feature`, LH-ONB-START-07) where an invalid
 * sesskey is asserted to raise `moodle_exception`. Driving that from
 * PHPUnit would require simulating the full page-script request
 * lifecycle, which buys us nothing over trusting Moodle core's
 * contract for `require_sesskey()`.
 *
 * @package    local_lernhive_onboarding
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_onboarding;

use advanced_testcase;
use PHPUnit\Framework\Attributes\CoversClass;

defined('MOODLE_INTERNAL') || die();

#[CoversClass(starttour_flow::class)]
final class starttour_flow_test extends advanced_testcase {

    /**
     * Fresh start: tour has a `lh_start_url` in configdata, the user
     * has never run it before. Flow must prime `_requested=1`, return
     * the fully-resolved URL, and not leave any stale _completed /
     * _lastStep state behind (they never existed — just verifying
     * the unset is harmless).
     */
    public function test_fresh_start_primes_requested_and_returns_resolved_url(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();

        $tour = $this->create_tour([
            'filtervalues' => ['role' => ['editingteacher']],
            'lh_start_url' => '/user/editadvanced.php?id={USERID}',
        ]);

        $url = starttour_flow::prepare_redirect_url((int) $tour->id, (int) $user->id);

        $this->assertSame('/user/editadvanced.php', $url->get_path());
        $this->assertSame((string) $user->id, $url->get_param('id'));

        $this->assertEquals(
            1,
            get_user_preferences('tool_usertours_' . $tour->id . '_requested', 0, $user->id),
            'Fresh start must set the _requested replay flag to 1'
        );
        $this->assertNull(
            get_user_preferences('tool_usertours_' . $tour->id . '_completed', null, $user->id),
            '_completed must be absent on a fresh start'
        );
        $this->assertNull(
            get_user_preferences('tool_usertours_' . $tour->id . '_lastStep', null, $user->id),
            '_lastStep must be absent on a fresh start'
        );
    }

    /**
     * Replay after completion: the user finished the tour yesterday
     * (both `_completed` and `_lastStep` are set), they click "Start"
     * again in the catalog. Flow must clear both stale markers AND
     * set `_requested=1`, otherwise the tool_usertours player would
     * either skip the tour outright (seeing `_completed`) or resume
     * mid-tour (seeing `_lastStep`).
     *
     * Also exercises `{DEMOCOURSEID}` resolution end-to-end.
     */
    public function test_replay_after_completion_clears_completed_and_laststep(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();

        $tour = $this->create_tour([
            'lh_start_url' => '/course/view.php?id={DEMOCOURSEID}',
        ]);

        // User finished the tour previously.
        set_user_preference('tool_usertours_' . $tour->id . '_completed', time(), $user->id);
        set_user_preference('tool_usertours_' . $tour->id . '_lastStep', 3, $user->id);

        set_config('democourseid', 42, 'local_lernhive_onboarding');

        $url = starttour_flow::prepare_redirect_url((int) $tour->id, (int) $user->id);

        $this->assertSame('/course/view.php', $url->get_path());
        $this->assertSame('42', $url->get_param('id'));

        $this->assertEquals(
            1,
            get_user_preferences('tool_usertours_' . $tour->id . '_requested', 0, $user->id)
        );
        $this->assertNull(
            get_user_preferences('tool_usertours_' . $tour->id . '_completed', null, $user->id),
            '_completed must be cleared so the tour plays again'
        );
        $this->assertNull(
            get_user_preferences('tool_usertours_' . $tour->id . '_lastStep', null, $user->id),
            '_lastStep must be cleared so the replay starts from step 1'
        );
    }

    /**
     * Fallback path: tour has a non-empty configdata but **no**
     * `lh_start_url` key (the shape of every un-migrated Level-1 tour
     * before LH-ONB-START-05 backfills them). Flow must fall back to
     * the 0.2.x pathmatch strip and still prime `_requested=1`.
     *
     * This test is the contract that lets us land the resolver +
     * starttour_flow rewrite without breaking the 12 existing tour
     * JSONs. It will start failing (and that's fine) when we delete
     * the fallback in 0.4.0.
     */
    public function test_fallback_when_no_lh_start_url_in_configdata(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();

        $tour = $this->create_tour(
            ['filtervalues' => ['role' => ['editingteacher']]],
            '/course/view.php%'
        );

        $url = starttour_flow::prepare_redirect_url((int) $tour->id, (int) $user->id);

        // Fallback strips the `%` and returns `/course/view.php` literal.
        $this->assertSame('/course/view.php', $url->get_path());
        // Replay flag is set regardless of which path produced the URL.
        $this->assertEquals(
            1,
            get_user_preferences('tool_usertours_' . $tour->id . '_requested', 0, $user->id)
        );
    }

    /**
     * A second fallback corner case: configdata is null / empty string.
     * Must not explode; must fall back the same way.
     */
    public function test_fallback_when_configdata_is_empty(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();

        $tour = $this->create_tour_raw('/user/editadvanced.php%', '');

        $url = starttour_flow::prepare_redirect_url((int) $tour->id, (int) $user->id);

        $this->assertSame('/user/editadvanced.php', $url->get_path());
    }

    /**
     * An unknown tour ID surfaces as `moodle_exception('invalidrecord')`
     * — matches the 0.2.x behaviour so existing callers (mostly the
     * catalog UI) don't need to change their error handling.
     */
    public function test_missing_tour_throws_moodle_exception(): void {
        $this->resetAfterTest();
        $this->expectException(\moodle_exception::class);
        starttour_flow::prepare_redirect_url(99999999, 1);
    }

    // ------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------

    /**
     * Insert a minimal `tool_usertours_tours` row with the given
     * configdata array (json-encoded on the way in) and return the
     * created record.
     *
     * @param array  $configdata  Associative array to be encoded as the
     *                            tour's `configdata` JSON string.
     * @param string $pathmatch   Optional pathmatch; defaults to a
     *                            stable user-edit path so the fallback
     *                            can still produce a sensible URL.
     * @return \stdClass
     */
    private function create_tour(array $configdata, string $pathmatch = '/user/editadvanced.php%'): \stdClass {
        return $this->create_tour_raw($pathmatch, json_encode($configdata));
    }

    /**
     * Same as {@see create_tour()} but takes the configdata as a raw
     * string so tests can pass empty / malformed values.
     */
    private function create_tour_raw(string $pathmatch, string $configdata): \stdClass {
        global $DB;

        $tour = (object) [
            'name' => 'LernHive Test Tour ' . uniqid('', true),
            'description' => 'Test fixture for starttour_flow_test',
            'pathmatch' => $pathmatch,
            'enabled' => 1,
            'sortorder' => 0,
            'configdata' => $configdata,
            'endtourlabel' => '',
            'displaystepnumbers' => 1,
            'showtourwhen' => 1,
        ];
        $tour->id = $DB->insert_record('tool_usertours_tours', $tour);
        return $tour;
    }
}
