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
 * ContentHub entry page.
 *
 * Dual-mode access:
 *   - Site admins reach it as an admin_externalpage registered in
 *     settings.php (shows inside the admin tree with the full breadcrumb).
 *   - Non-admin users (teachers, course creators) reach it directly and
 *     see it as a standalone site page.
 *
 * @package    local_lernhive_contenthub
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_lernhive_contenthub\output\hub_page;

$context = \core\context\system::instance();

// Non-admins don't go through admin_externalpage_setup — they'd be
// bounced. We route admins through the admin tree and everyone else
// through a direct page so the hub is reachable from the launcher too.
if (is_siteadmin()) {
    admin_externalpage_setup('local_lernhive_contenthub_hub');
} else {
    require_login();
    require_capability('local/lernhive_contenthub:view', $context);

    $PAGE->set_context($context);
    $PAGE->set_url(new moodle_url('/local/lernhive_contenthub/index.php'));
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title(get_string('page_title', 'local_lernhive_contenthub'));
    $PAGE->set_heading(get_string('page_title', 'local_lernhive_contenthub'));
}

/** @var \local_lernhive_contenthub\output\renderer $renderer */
$renderer = $PAGE->get_renderer('local_lernhive_contenthub');

echo $OUTPUT->header();
echo $renderer->render_hub_page(new hub_page());
echo $OUTPUT->footer();
