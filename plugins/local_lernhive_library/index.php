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
 * In R2, catalog entries are sourced from the managed remote feed
 * when configured. Otherwise the plugin falls back to the local JSON
 * manifest setting. Invalid source payloads fail closed to an empty state.
 *
 * Library is a content creation tool, not a site configuration page —
 * it always renders as a `standard` page with the LernHive Plugin
 * Shell, regardless of whether the visitor is a siteadmin or a
 * teacher. The plugin still registers itself in the admin tree via
 * settings.php so admins can discover it via the site-admin search,
 * but the page does NOT call admin_externalpage_setup() — that
 * would force pagelayout='admin' and layer the admin tab bar on top
 * of the Plugin Shell, which owns its own navigation.
 *
 * @package    local_lernhive_library
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_lernhive_library\catalog;
use local_lernhive_library\output\catalog_page;

$context = \core\context\system::instance();

require_login();
require_capability('local/lernhive_library:import', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/lernhive_library/index.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_lernhive_library'));
$PAGE->set_heading(get_string('pluginname', 'local_lernhive_library'));

/** @var \local_lernhive_library\output\renderer $renderer */
$renderer = $PAGE->get_renderer('local_lernhive_library');

// Catalog source: seeded test entries can be injected in tests.
// Production resolves source via catalog defaults:
// remote feed (if configured) -> local manifest fallback.
$catalog = new catalog();

echo $OUTPUT->header();
echo $renderer->render_catalog_page(new catalog_page($catalog));
echo $OUTPUT->footer();
