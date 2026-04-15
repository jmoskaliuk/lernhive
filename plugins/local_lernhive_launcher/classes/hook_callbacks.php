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
    --lh-launcher-border: #dbe7ee;
    --lh-launcher-title: #0f2330;
    --lh-launcher-copy: #415563;
    --lh-launcher-accent: #0f4c5c;
    margin: 1rem auto;
    max-width: min(60rem, calc(100vw - 2rem));
}
.local-lernhive-launcher-fallback .local-lernhive-launcher__panel {
    border: 1px solid var(--lh-launcher-border);
    border-radius: 1.125rem;
    background: linear-gradient(180deg, #f5fafc 0%, #ffffff 18rem);
    box-shadow: 0 0.9rem 2.5rem rgba(15, 35, 48, 0.09);
    overflow: hidden;
}
.local-lernhive-launcher-fallback .local-lernhive-launcher__summary {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    padding: 1.05rem 1.2rem;
    cursor: pointer;
    list-style: none;
    border-bottom: 1px solid #e8eff4;
    background: linear-gradient(120deg, #ffffff 0%, #eff6fb 100%);
}
.local-lernhive-launcher-fallback .local-lernhive-launcher__summary::-webkit-details-marker {
    display: none;
}
.local-lernhive-launcher-fallback .local-lernhive-launcher__title {
    color: var(--lh-launcher-title);
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: 0.01em;
}
.local-lernhive-launcher-fallback .local-lernhive-launcher__description,
.local-lernhive-launcher-fallback .local-lernhive-launcher__copy span,
.local-lernhive-launcher-fallback .local-lernhive-launcher__empty {
    color: var(--lh-launcher-copy);
    font-size: 0.94rem;
    line-height: 1.4;
}
.local-lernhive-launcher-fallback .local-lernhive-launcher__actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr));
    gap: 0.8rem;
    padding: 1rem;
}
.local-lernhive-launcher-fallback .local-lernhive-launcher__action {
    display: grid;
    grid-template-columns: 2.65rem 1fr;
    gap: 0.8rem;
    align-items: center;
    padding: 0.9rem 0.95rem;
    border: 1px solid #d6e2ea;
    border-radius: 0.95rem;
    color: inherit;
    text-decoration: none;
    background: #ffffff;
    transition: transform 120ms ease, border-color 120ms ease, box-shadow 120ms ease;
}
.local-lernhive-launcher-fallback .local-lernhive-launcher__action:hover,
.local-lernhive-launcher-fallback .local-lernhive-launcher__action:focus-visible {
    border-color: var(--lh-launcher-accent);
    box-shadow: 0 0.45rem 1.4rem rgba(17, 47, 64, 0.16);
    transform: translateY(-1px);
    outline: none;
}
.local-lernhive-launcher-fallback .local-lernhive-launcher__icon {
    display: grid;
    place-items: center;
    width: 2.65rem;
    height: 2.65rem;
    border-radius: 0.78rem;
    background: #edf6ff;
    color: #0a4f92;
}
.local-lernhive-launcher-fallback .local-lernhive-launcher__action[data-actionid="contenthub"] .local-lernhive-launcher__icon {
    background: #ebfbf4;
    color: #1c7d4f;
}
.local-lernhive-launcher-fallback .local-lernhive-launcher__action[data-actionid="library"] .local-lernhive-launcher__icon {
    background: #fff5e9;
    color: #9a5c00;
}
.local-lernhive-launcher-fallback .local-lernhive-launcher__action[data-actionid="reports"] .local-lernhive-launcher__icon {
    background: #eef0ff;
    color: #3740a0;
}
.local-lernhive-launcher-fallback .local-lernhive-launcher__action[data-actionid="create_snack"] .local-lernhive-launcher__icon {
    background: #ffeaf3;
    color: #a91a64;
}
.local-lernhive-launcher-fallback .local-lernhive-launcher__action[data-actionid="create_community"] .local-lernhive-launcher__icon {
    background: #eafff2;
    color: #17663f;
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
    gap: 0.16rem;
}
.local-lernhive-launcher-fallback .local-lernhive-launcher__copy strong {
    color: #132a39;
    font-size: 0.97rem;
    letter-spacing: 0.01em;
}
.local-lernhive-launcher-fallback .local-lernhive-launcher__empty {
    margin: 0;
    padding: 1rem 1.2rem 1.1rem;
}
@media (max-width: 640px) {
    .local-lernhive-launcher-fallback .local-lernhive-launcher__actions {
        grid-template-columns: 1fr;
        padding: 0.9rem;
    }
    .local-lernhive-launcher-fallback .local-lernhive-launcher__action {
        grid-template-columns: 2.4rem 1fr;
        padding: 0.82rem 0.86rem;
    }
    .local-lernhive-launcher-fallback .local-lernhive-launcher__icon {
        width: 2.4rem;
        height: 2.4rem;
    }
}
</style>';

        $hook->add_html($css . '<div class="local-lernhive-launcher-fallback">' . $html . '</div>');
    }
}
