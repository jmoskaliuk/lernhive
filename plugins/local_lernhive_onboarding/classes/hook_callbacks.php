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

defined('MOODLE_INTERNAL') || die();

/**
 * Top-of-body hook callbacks that inject Onboarding UI elements.
 */
class hook_callbacks {

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
}
