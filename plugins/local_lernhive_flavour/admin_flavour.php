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
 * LernHive Flavour Setup — admin page that lets a site admin pick which
 * flavour profile should seed the installation's defaults.
 *
 * Flow:
 *   GET  → render flavour_picker (one card per registered profile)
 *   POST action=apply   → apply immediately (no overrides to warn about)
 *   POST action=confirm → render flavour_diff confirm dialog
 *   POST action=apply + confirmed=1 → apply after confirm
 *
 * @package    local_lernhive_flavour
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_lernhive_flavour\flavour_manager;
use local_lernhive_flavour\flavour_registry;
use local_lernhive_flavour\output\flavour_picker;
use local_lernhive_flavour\output\flavour_diff;

admin_externalpage_setup('local_lernhive_flavour_setup');

$context = \core\context\system::instance();
require_capability('local/lernhive_flavour:manage', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/lernhive_flavour/admin_flavour.php'));
$PAGE->set_title(get_string('page_title', 'local_lernhive_flavour'));
$PAGE->set_heading(get_string('page_title', 'local_lernhive_flavour'));
$PAGE->requires->css('/local/lernhive_flavour/styles.css');

/** @var \local_lernhive_flavour\output\renderer $renderer */
$renderer = $PAGE->get_renderer('local_lernhive_flavour');

// Handle POST actions first — the page body depends on the outcome.
$showdiff = false;
$difftarget = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $action = optional_param('action', '', PARAM_ALPHA);
    $flavour = optional_param('flavour', '', PARAM_ALPHANUMEXT);

    if (!flavour_registry::exists($flavour)) {
        \core\notification::error(get_string('err_unknown_flavour', 'local_lernhive_flavour'));
    } else if ($action === 'confirm') {
        // Admin clicked on a flavour card that would overwrite existing
        // settings — show the diff confirm dialog instead of applying.
        $showdiff = true;
        $difftarget = $flavour;
    } else if ($action === 'apply') {
        try {
            $result = flavour_manager::apply($flavour);
            $label = flavour_registry::get($flavour)->get_label();

            if ($result->overrides_detected) {
                \core\notification::success(
                    get_string('flavour_applied_with_overrides', 'local_lernhive_flavour', $label)
                );
            } else {
                \core\notification::success(
                    get_string('flavour_applied', 'local_lernhive_flavour', $label)
                );
            }
        } catch (\Throwable $e) {
            \core\notification::error($e->getMessage());
        }
    }
}

echo $OUTPUT->header();

if ($showdiff && $difftarget !== null) {
    echo $renderer->render_flavour_diff(new flavour_diff($difftarget));
} else {
    echo $renderer->render_flavour_picker(new flavour_picker());
}

echo $OUTPUT->footer();
