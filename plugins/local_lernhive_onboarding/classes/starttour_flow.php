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
 * Deterministic tour start flow — request-path logic extracted for testing.
 *
 * @package    local_lernhive_onboarding
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_onboarding;

defined('MOODLE_INTERNAL') || die();

/**
 * Core logic for `starttour.php` — loads a tour, primes the Moodle-native
 * tour-replay flag, clears prior completion state, and returns the
 * `\moodle_url` the page script must redirect to.
 *
 * Extracted from `starttour.php` so the flow can be unit-tested without
 * driving the full request lifecycle. `starttour.php` is now a thin
 * page-level wrapper that only handles `require_login()`, `require_sesskey()`,
 * and the final `redirect()`; every other decision lives here.
 *
 * ## Primed preferences
 *
 * Moodle 5.x stores replay/completion using
 * `\tool_usertours\tour::TOUR_REQUESTED_BY_USER` and
 * `\tool_usertours\tour::TOUR_LAST_COMPLETED_BY_USER`
 * (e.g. `tool_usertours_tour_reset_time_{id}`).
 *
 * For compatibility with older installs where onboarding data may still
 * carry legacy preference keys, we also prime/clear:
 * - `tool_usertours_{id}_requested`
 * - `tool_usertours_{id}_completed`
 * - `tool_usertours_{id}_lastStep`
 *
 * We never touch the `tool_usertours` player's internals and we never
 * redirect mid-tour — all we do is set the same preferences the player
 * itself would set, then hand control back to Moodle.
 */
class starttour_flow {

    /**
     * Prepare the redirect target for a catalog-initiated tour start.
     *
     * @param int             $tourid ID of the `tool_usertours_tours` row
     *                                the catalog "Start" button points at.
     * @param int             $userid User whose tour state is being primed.
     * @param \stdClass|null  $tour   Optional pre-loaded tour record — tests
     *                                inject this to avoid a DB round trip.
     *                                When null, loaded from the DB by id.
     * @return \moodle_url The resolved URL `starttour.php` should redirect to.
     * @throws \moodle_exception If the tour ID does not exist.
     */
    public static function prepare_redirect_url(
        int $tourid,
        int $userid,
        ?\stdClass $tour = null
    ): \moodle_url {
        global $DB;

        if ($tour === null) {
            $tour = $DB->get_record('tool_usertours_tours', ['id' => $tourid]);
            if (!$tour) {
                throw new \moodle_exception('invalidrecord', 'error');
            }
        }

        // 1. Pick the redirect target — prefer the tour's own `lh_start_url`,
        //    fall back to the 0.2.x pathmatch-strip for un-migrated tours.
        $starturl = self::extract_start_url($tour);
        if ($starturl !== '') {
            try {
                $redirect = start_url_resolver::resolve($starturl, $userid);
            } catch (\coding_exception $e) {
                // Defensive: extract_start_url() already filters empty
                // strings, so the resolver should not raise. If it
                // somehow does we still want a safe landing.
                debugging(
                    'starttour_flow: resolver raised on non-empty template '
                    . '(tourid=' . $tourid . '): ' . $e->getMessage(),
                    DEBUG_DEVELOPER
                );
                $redirect = self::fallback_redirect_url($tour);
            }
        } else {
            $redirect = self::fallback_redirect_url($tour);
        }

        // 2. Prime the user tour preferences (current + legacy keyspace).
        self::prime_tour_preferences($tourid, $userid);

        return $redirect;
    }

    /**
     * Prime replay preferences for the given tour/user.
     *
     * On Moodle 5.x the authoritative replay signal is
     * `tool_usertours_tour_reset_time_{tourid}` and must be set to a
     * timestamp (`time()`). Completion is stored under
     * `tool_usertours_tour_completion_time_{tourid}` and must be cleared.
     *
     * Legacy keys are still touched for backward compatibility with older
     * Moodle deployments and older tests:
     * - `tool_usertours_{id}_requested` is set to 1
     * - `tool_usertours_{id}_completed` and `_lastStep` are unset
     *
     * @param int $tourid
     * @param int $userid
     * @return void
     */
    private static function prime_tour_preferences(int $tourid, int $userid): void {
        // Moodle 5.x / current tool_usertours keys.
        if (class_exists(\tool_usertours\tour::class)
            && defined(\tool_usertours\tour::class . '::TOUR_REQUESTED_BY_USER')
        ) {
            $requestedkey = \tool_usertours\tour::TOUR_REQUESTED_BY_USER . $tourid;
            set_user_preference($requestedkey, time(), $userid);
        }
        if (class_exists(\tool_usertours\tour::class)
            && defined(\tool_usertours\tour::class . '::TOUR_LAST_COMPLETED_BY_USER')
        ) {
            $completedkey = \tool_usertours\tour::TOUR_LAST_COMPLETED_BY_USER . $tourid;
            unset_user_preference($completedkey, $userid);
        }

        // Legacy fallback keyspace used by earlier LernHive iterations.
        set_user_preference('tool_usertours_' . $tourid . '_requested', 1, $userid);
        unset_user_preference('tool_usertours_' . $tourid . '_completed', $userid);
        unset_user_preference('tool_usertours_' . $tourid . '_lastStep', $userid);
    }

    /**
     * Pull `lh_start_url` out of a tour's `configdata` JSON.
     *
     * Returns the empty string for any of: missing configdata, malformed
     * JSON, missing key. Callers treat '' as "no deterministic start
     * configured — use the fallback".
     *
     * @param \stdClass $tour Tour record from `tool_usertours_tours`.
     * @return string The raw `lh_start_url` template, or ''.
     */
    private static function extract_start_url(\stdClass $tour): string {
        if (empty($tour->configdata)) {
            return '';
        }
        $decoded = json_decode((string) $tour->configdata, true);
        if (!is_array($decoded) || empty($decoded['lh_start_url'])) {
            return '';
        }
        return (string) $decoded['lh_start_url'];
    }

    /**
     * 0.2.x pathmatch-strip fallback.
     *
     * Kept as a bridge so tours that have not been backfilled with a
     * `lh_start_url` (see LH-ONB-START-05) still launch somewhere
     * sensible. Scheduled for removal in 0.4.0 once the backfill
     * migration has landed everywhere.
     *
     * @param \stdClass $tour Tour record from `tool_usertours_tours`.
     * @return \moodle_url Fallback landing URL.
     */
    private static function fallback_redirect_url(\stdClass $tour): \moodle_url {
        $pathmatch = (string) ($tour->pathmatch ?? '');
        $stripped  = rtrim($pathmatch, '%');

        if ($stripped === '' || $stripped === '/') {
            return new \moodle_url('/local/lernhive_onboarding/tours.php');
        }

        try {
            return new \moodle_url($stripped);
        } catch (\Exception $e) {
            return new \moodle_url('/local/lernhive_onboarding/tours.php');
        }
    }
}
