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
 * Deterministic tour start-URL resolver for LernHive Onboarding.
 *
 * @package    local_lernhive_onboarding
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_onboarding;

defined('MOODLE_INTERNAL') || die();

/**
 * Substitutes LernHive placeholders in a tour `start_url` template and
 * returns a ready-to-redirect {@see \moodle_url}.
 *
 * `tool_usertours` binds every tour to a single `pathmatch`, which is a
 * filter pattern rather than a real URL. For deterministic catalog-driven
 * tour starts we therefore store an explicit `start_url` on every tour
 * (see `tour_importer::import_tour()` and LH-ONB-START-02). This class
 * turns that template into the absolute `moodle_url` that `starttour.php`
 * will redirect to.
 *
 * ## Placeholders
 *
 * | Placeholder        | Value                                                      |
 * |--------------------|------------------------------------------------------------|
 * | `{USERID}`         | The `$userid` argument passed to `resolve()`.              |
 * | `{SYSCONTEXTID}`   | `\context_system::instance()->id`                          |
 * | `{SITEID}`         | The `SITEID` constant (Moodle Frontpage course ID).        |
 * | `{DEMOCOURSEID}`   | `get_config('local_lernhive_onboarding', 'democourseid')`  |
 * |                    | or `0` if the Onboarding Sandbox course is not yet seeded. |
 *
 * ## Forward compatibility
 *
 * Unknown placeholders — for example `{COHORTID}` that a future release
 * might add — are deliberately left **literal** so an older plugin
 * version does not silently mangle tours authored against a newer one.
 * If the resulting URL contains curly braces, Moodle simply produces
 * a 404 when the user follows it, which is a loud, debuggable failure
 * instead of a silent wrong-redirect.
 */
class start_url_resolver {

    /**
     * Resolve a tour `start_url` template against the current Moodle
     * runtime context and return the concrete redirect target.
     *
     * The method is deliberately side-effect-free: it reads from
     * Moodle globals (system context, SITEID, plugin config) but never
     * writes anything, so it is trivially unit-testable.
     *
     * @param string $template The `start_url` string from tour JSON.
     *                         Must be a non-empty, non-whitespace string;
     *                         typically a Moodle-relative path like
     *                         `/user/editadvanced.php?id={USERID}`.
     * @param int    $userid   The user whose start URL is being resolved.
     *                         Used to substitute `{USERID}`.
     * @return \moodle_url The fully resolved redirect target.
     * @throws \coding_exception If `$template` is empty or whitespace.
     */
    public static function resolve(string $template, int $userid): \moodle_url {
        if (trim($template) === '') {
            throw new \coding_exception(
                'local_lernhive_onboarding\\start_url_resolver::resolve() '
                . 'requires a non-empty template — callers must fall back '
                . 'to the pathmatch strip before invoking this resolver.'
            );
        }

        $democourseid = (int) (get_config('local_lernhive_onboarding', 'democourseid') ?: 0);

        // strtr() with an array applies the substitutions atomically:
        // known placeholders get replaced, everything else (including
        // any unknown `{FOO}` tokens) stays literal. Intentional — see
        // class docblock on forward compatibility.
        $replacements = [
            '{USERID}'       => (string) $userid,
            '{SYSCONTEXTID}' => (string) \context_system::instance()->id,
            '{SITEID}'       => (string) SITEID,
            '{DEMOCOURSEID}' => (string) $democourseid,
        ];

        $resolved = strtr($template, $replacements);

        return new \moodle_url($resolved);
    }
}
