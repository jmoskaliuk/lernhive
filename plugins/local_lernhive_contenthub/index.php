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
 * ContentHub is a content creation tool — not a site configuration page.
 * It always renders as a `standard` page with the LernHive Plugin Shell,
 * regardless of whether the visitor is a siteadmin or a teacher.
 *
 * The plugin still registers itself in the admin tree via settings.php
 * (under Local plugins → LernHive ContentHub) so admins can discover it
 * via the site-admin search, but the page does NOT call
 * admin_externalpage_setup() — that would force pagelayout='admin' and
 * layer the admin tab bar on top of the Plugin Shell, which owns its own
 * navigation (ContentHub header strip, content-creation card grid).
 *
 * @package    local_lernhive_contenthub
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_lernhive_contenthub\output\hub_page;

$context = \core\context\system::instance();

require_login();
require_capability('local/lernhive_contenthub:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/lernhive_contenthub/index.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('page_title', 'local_lernhive_contenthub'));
$PAGE->set_heading(get_string('page_title', 'local_lernhive_contenthub'));

/** @var \local_lernhive_contenthub\output\renderer $renderer */
$renderer = $PAGE->get_renderer('local_lernhive_contenthub');

echo $OUTPUT->header();
echo $renderer->render_hub_page(new hub_page());
echo $OUTPUT->footer();
