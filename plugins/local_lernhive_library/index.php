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
 * Library catalog entry page.
 *
 * Release 1 renders an empty catalog state — the real catalog source
 * arrives with a later milestone (see docs/04-tasks.md).
 *
 * @package    local_lernhive_library
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_lernhive_library\catalog;
use local_lernhive_library\output\catalog_page;

$context = \core\context\system::instance();

if (is_siteadmin()) {
    admin_externalpage_setup('local_lernhive_library_catalog');
} else {
    require_login();
    require_capability('local/lernhive_library:import', $context);

    $PAGE->set_context($context);
    $PAGE->set_url(new moodle_url('/local/lernhive_library/index.php'));
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title(get_string('pluginname', 'local_lernhive_library'));
    $PAGE->set_heading(get_string('pluginname', 'local_lernhive_library'));
}

/** @var \local_lernhive_library\output\renderer $renderer */
$renderer = $PAGE->get_renderer('local_lernhive_library');

// R1 stub: empty catalog. The eventual catalog source will be built
// from eLeDia's managed backend and live behind a separate class.
$catalog = new catalog();

echo $OUTPUT->header();
echo $renderer->render_catalog_page(new catalog_page($catalog));
echo $OUTPUT->footer();
