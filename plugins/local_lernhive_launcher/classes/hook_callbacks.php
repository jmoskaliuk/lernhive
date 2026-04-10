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
 * Hook callbacks for the launcher plugin.
 *
 * @package    local_lernhive_launcher
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_launcher;

use core\hook\output\before_standard_top_of_body_html_generation;
use local_lernhive_launcher\output\launcher;

defined('MOODLE_INTERNAL') || die();

/**
 * Theme-independent launcher fallback hooks.
 */
class hook_callbacks {
    /**
     * Inject a small launcher fallback for themes other than theme_lernhive.
     *
     * @param before_standard_top_of_body_html_generation $hook
     * @return void
     */
    public static function before_standard_top_of_body_html(before_standard_top_of_body_html_generation $hook): void {
        global $PAGE;

        if (!isloggedin() || isguestuser()) {
            return;
        }

        if (($PAGE->theme->name ?? '') === 'lernhive') {
            return;
        }

        if ($PAGE->pagelayout === 'login' || $PAGE->pagelayout === 'popup') {
            return;
        }

        if ($PAGE->pagetype === 'local-lernhive-launcher-index') {
            return;
        }

        $actions = action_provider::get_visible_actions();
        if (empty($actions)) {
            return;
        }

        $renderer = $PAGE->get_renderer('local_lernhive_launcher');
        $renderable = new launcher($actions);
        $html = $renderer->render_launcher($renderable);

        $css = '<style>
.local-lernhive-launcher-fallback {
    margin: 1rem;
    max-width: 26rem;
}
.local-lernhive-launcher-fallback .local-lernhive-launcher__panel {
    border: 1px solid #d9e1e7;
    border-radius: 1rem;
    background: #fff;
    box-shadow: 0 0.75rem 2rem rgba(17, 24, 39, 0.08);
}
.local-lernhive-launcher-fallback .local-lernhive-launcher__summary {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    padding: 1rem 1.125rem;
    cursor: pointer;
}
.local-lernhive-launcher-fallback .local-lernhive-launcher__title {
    font-weight: 700;
}
.local-lernhive-launcher-fallback .local-lernhive-launcher__description,
.local-lernhive-launcher-fallback .local-lernhive-launcher__copy span,
.local-lernhive-launcher-fallback .local-lernhive-launcher__empty {
    color: #52606d;
    font-size: 0.95rem;
}
.local-lernhive-launcher-fallback .local-lernhive-launcher__actions {
    display: grid;
    gap: 0.75rem;
    padding: 0 1rem 1rem;
}
.local-lernhive-launcher-fallback .local-lernhive-launcher__action {
    display: grid;
    grid-template-columns: 2.5rem 1fr;
    gap: 0.75rem;
    align-items: center;
    padding: 0.875rem 1rem;
    border: 1px solid #d9e1e7;
    border-radius: 0.875rem;
    color: inherit;
    text-decoration: none;
    background: #f8fafc;
}
.local-lernhive-launcher-fallback .local-lernhive-launcher__action:hover,
.local-lernhive-launcher-fallback .local-lernhive-launcher__action:focus-visible {
    border-color: #194866;
    outline: none;
}
.local-lernhive-launcher-fallback .local-lernhive-launcher__icon {
    display: grid;
    place-items: center;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.75rem;
    background: #fff3e8;
    color: #d97706;
}
.local-lernhive-launcher-fallback .local-lernhive-launcher__icon svg {
    width: 1.25rem;
    height: 1.25rem;
    stroke: currentColor;
    stroke-width: 2;
    fill: none;
    stroke-linecap: round;
    stroke-linejoin: round;
}
.local-lernhive-launcher-fallback .local-lernhive-launcher__copy {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}
</style>';

        $hook->add_html($css . '<div class="local-lernhive-launcher-fallback">' . $html . '</div>');
    }
}
