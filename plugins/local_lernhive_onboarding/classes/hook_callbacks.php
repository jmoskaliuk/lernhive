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
 * Hook callbacks for local_lernhive_onboarding.
 *
 * @package    local_lernhive_onboarding
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_onboarding;

use core\hook\output\before_standard_top_of_body_html_generation;
use local_lernhive_onboarding\output\dashboard_banner;
use tool_usertours\hook\before_serverside_filter_fetch;

defined('MOODLE_INTERNAL') || die();

/**
 * Top-of-body hook callbacks that inject Onboarding UI elements.
 */
class hook_callbacks {
    /** @var bool Guard against duplicate overlay wiring per request. */
    private static bool $completionoverlayqueued = false;

    /**
     * Inject the Trainer learning-path banner at the top of the dashboard.
     *
     * This runs on every top-of-body emit, so we short-circuit as cheaply
     * as possible when we're not on `/my/`. The expensive visibility checks
     * (capability + Level-1 completeness) run only when the pagelayout
     * matches the dashboard.
     *
     * @param before_standard_top_of_body_html_generation $hook
     */
    public static function inject_dashboard_banner(
        before_standard_top_of_body_html_generation $hook
    ): void {
        global $USER, $PAGE;

        // 1. Page-scope filter: only the standard Moodle dashboard.
        //
        // We check both `pagelayout` and `pagetype` because Moodle 5.x core
        // uses the `mydashboard` layout for `/my/` but third-party block
        // layouts sometimes remap the pagelayout and would otherwise lose
        // the banner. `pagetype === 'my-index'` is the canonical signal.
        $ondashboard = ($PAGE->pagelayout === 'mydashboard')
            || ($PAGE->pagetype === 'my-index');
        if (!$ondashboard) {
            return;
        }

        // 2. Cheap auth gate.
        if (!isloggedin() || isguestuser()) {
            return;
        }

        // 3. Trainer + progress gate — cap check + one DB round trip.
        if (!banner_gate::should_show((int) $USER->id)) {
            return;
        }

        // 4. Render via the standard renderer so the mustache template is
        //    testable and themeable. Renderer is tied to $PAGE, which is
        //    fully configured by the time this hook fires.
        try {
            $renderer = $PAGE->get_renderer('local_lernhive_onboarding');
            $banner = new dashboard_banner((int) $USER->id, 1);
            $html = $renderer->render_dashboard_banner($banner);
        } catch (\Throwable $e) {
            // Never break the dashboard because of a banner — degrade silently.
            debugging(
                'local_lernhive_onboarding: dashboard banner render failed: '
                . $e->getMessage(),
                DEBUG_DEVELOPER
            );
            return;
        }

        $hook->add_html($html);
    }

    /**
     * Configure server-side tour filters for one-request forced starts.
     *
     * When a user launches a tour from `starttour.php`, we store the target
     * tour id in session. On the very next page request, this callback:
     * - consumes that state (one-shot),
     * - disables the core role filter for that one request, and
     * - injects a custom filter that allows only the requested tour id.
     *
     * This keeps legacy role-filtered tours launchable from the LernHive
     * catalog while preserving standard filter behaviour for normal page loads.
     *
     * @param before_serverside_filter_fetch $hook
     * @return void
     */
    public static function configure_forced_tour_filter(
        before_serverside_filter_fetch $hook
    ): void {
        global $SESSION;

        if (empty($SESSION->local_lhonb_forced_tour_launch)
            || !is_array($SESSION->local_lhonb_forced_tour_launch)) {
            return;
        }

        $state = $SESSION->local_lhonb_forced_tour_launch;
        unset($SESSION->local_lhonb_forced_tour_launch);

        $tourid = (int) ($state['tourid'] ?? 0);
        $expires = (int) ($state['expires'] ?? 0);
        if ($tourid <= 0 || $expires < time()) {
            return;
        }

        forced_tour_state::set_forced_tour_id($tourid);

        // Legacy onboarding tours often carry hard-coded role filters that
        // don't reflect the current capability-based access model.
        $hook->remove_filter_by_classname(\tool_usertours\local\filter\role::class);
        $hook->add_filter_by_classname(\local_lernhive_onboarding\local\filter\forced_tour::class);

        self::queue_tour_completion_overlay();
    }

    /**
     * Wire a one-shot completion overlay shown after `tool_usertours/tourEnded`.
     *
     * Only queued for deterministic catalog starts (same request where the
     * forced-tour filter is active). This keeps normal page-tour behaviour
     * unchanged.
     *
     * @return void
     */
    private static function queue_tour_completion_overlay(): void {
        global $PAGE;

        if (self::$completionoverlayqueued) {
            return;
        }
        if (!isset($PAGE) || !($PAGE instanceof \moodle_page)) {
            return;
        }
        self::$completionoverlayqueued = true;

        $config = [
            'overviewUrl' => (new \moodle_url('/local/lernhive_onboarding/tours.php'))->out(false),
            'title' => get_string('tour_completion_overlay_title', 'local_lernhive_onboarding'),
            'body' => get_string('tour_completion_overlay_body', 'local_lernhive_onboarding'),
            'overviewCta' => get_string('tour_completion_overlay_overview', 'local_lernhive_onboarding'),
            'stayCta' => get_string('tour_completion_overlay_stay', 'local_lernhive_onboarding'),
        ];
        $json = json_encode(
            $config,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );
        if ($json === false) {
            $json = '{}';
        }

        $js = <<<JS
(function(config) {
    if (!config || window.__lhOnbCompletionOverlayWired) {
        return;
    }
    window.__lhOnbCompletionOverlayWired = true;

    const showOverlay = function() {
        require(['core/notification'], function(Notification) {
            Notification.confirm(
                config.title,
                config.body,
                config.overviewCta,
                config.stayCta,
                function() {
                    window.location.assign(config.overviewUrl);
                },
                function() {}
            );
        });
    };

    document.addEventListener('tool_usertours/tourEnded', showOverlay, {once: true});
})({$json});
JS;

        $PAGE->requires->js_init_code($js);
    }
}
