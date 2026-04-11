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
 * Idempotent provisioning helper for the Onboarding Sandbox course.
 *
 * @package    local_lernhive_onboarding
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_onboarding;

defined('MOODLE_INTERNAL') || die();

/**
 * Manages the "Onboarding Sandbox" course used as the safe target for
 * trainer tours that need a course context.
 *
 * ## Why a dedicated sandbox
 *
 * A novice trainer fumbling through a guided course-setup tour inside
 * a live production course is a support ticket waiting to happen.
 * Shipping an empty, hidden sandbox course gives the tours a
 * deterministic target — `{DEMOCOURSEID}` in a tour's `start_url`
 * resolves to this course, and the user never accidentally modifies
 * real content.
 *
 * ## Lifecycle
 *
 * - **Install / upgrade**: `ensure()` is called from `db/install.php`
 *   on fresh installs and from the 0.2.7 savepoint in `db/upgrade.php`
 *   for existing sites upgrading from 0.2.x.
 * - **Admin deletes the course manually**: next call to `ensure()`
 *   notices the stored ID points at nothing and rebuilds. The
 *   shortname lookup is the second line of defence — if the config
 *   key was lost but the course still exists (e.g. an uninstall /
 *   reinstall cycle), we rewire the config without duplicating.
 * - **Plugin uninstall**: `forget()` only drops the plugin config key.
 *   The course itself stays because an admin may have added real
 *   content (graded assignments, participants, …). Deleting it
 *   unconditionally would be destructive and non-recoverable.
 *
 * ## Idempotency
 *
 * `ensure()` can be called any number of times: if the sandbox course
 * already exists and the config key still matches, it short-circuits
 * after one DB read.
 */
class sandbox_course {

    /**
     * Stable shortname for the sandbox course. Used as a second-line
     * lookup so we don't lose the course to a config-only wipe.
     */
    public const SHORTNAME = 'lh_onboarding_sandbox';

    /**
     * Plugin config key that stores the live course ID.
     */
    public const CONFIG_KEY = 'democourseid';

    /**
     * Ensure the sandbox course exists and the plugin config points at
     * it. Safe to call repeatedly; cheap when everything is already
     * in place (single DB read).
     *
     * @return int The sandbox course ID.
     */
    public static function ensure(): int {
        global $DB, $CFG;

        // Fast path: stored ID is still valid.
        $stored = (int) (get_config('local_lernhive_onboarding', self::CONFIG_KEY) ?: 0);
        if ($stored > 0 && $DB->record_exists('course', ['id' => $stored])) {
            return $stored;
        }

        // Second-line lookup: maybe the course survived a config wipe
        // (uninstall → reinstall) — rewire the config instead of
        // duplicating.
        $existing = $DB->get_record('course', ['shortname' => self::SHORTNAME]);
        if ($existing) {
            set_config(self::CONFIG_KEY, (int) $existing->id, 'local_lernhive_onboarding');
            return (int) $existing->id;
        }

        // Fresh creation path.
        require_once($CFG->dirroot . '/course/lib.php');

        $data = (object) [
            'category'      => self::pick_target_category(),
            'shortname'     => self::SHORTNAME,
            'fullname'      => get_string('sandbox_course_fullname', 'local_lernhive_onboarding'),
            'summary'       => get_string('sandbox_course_summary', 'local_lernhive_onboarding'),
            'summaryformat' => FORMAT_HTML,
            'visible'       => 0,   // Hidden by default — admins can unhide if they want to poke around.
            'format'        => 'topics',
            'numsections'   => 1,
        ];

        $course = create_course($data);

        set_config(self::CONFIG_KEY, (int) $course->id, 'local_lernhive_onboarding');

        return (int) $course->id;
    }

    /**
     * Drop the plugin config key that points at the sandbox course.
     *
     * The course record itself is intentionally kept — admins may have
     * added real content, and deleting a course is a destructive,
     * non-recoverable action. Admins who want to remove the course
     * have to do so manually via Site administration → Courses.
     *
     * Called from `db/uninstall.php`.
     */
    public static function forget(): void {
        unset_config(self::CONFIG_KEY, 'local_lernhive_onboarding');
    }

    /**
     * Pick the least-surprising target category for the sandbox
     * course: the first visible top-level category (typically
     * "Miscellaneous", id=1). Falls back to id=1 literal as a
     * last-ditch default on exotic installs.
     *
     * @return int Category ID.
     */
    private static function pick_target_category(): int {
        global $DB;

        $cat = $DB->get_record_sql(
            'SELECT id
               FROM {course_categories}
              WHERE parent = 0
                AND visible = 1
           ORDER BY sortorder ASC, id ASC',
            [],
            IGNORE_MULTIPLE
        );

        return $cat ? (int) $cat->id : 1;
    }
}
