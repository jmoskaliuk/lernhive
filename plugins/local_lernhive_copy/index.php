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
 * Copy wizard entry page.
 *
 * Serves two related entry points from the ContentHub:
 *   - Copy card:     no ?source=        → source::TYPE_COURSE
 *   - Template card: ?source=template   → source::TYPE_TEMPLATE
 *
 * R1 scope is an UI-only stub that explains the mode choices and
 * links back to the Moodle core backup/restore screen.
 *
 * @package    local_lernhive_copy
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_lernhive_copy\source;
use local_lernhive_copy\output\wizard_page;

// `optional_param` returns '' if the query param is missing; source::from_request
// treats any non-'template' value (including '') as the default course source.
$source = source::from_request(optional_param('source', '', PARAM_ALPHA));

$context = \core\context\system::instance();

if (is_siteadmin()) {
    admin_externalpage_setup('local_lernhive_copy_wizard');
} else {
    require_login();
    require_capability('local/lernhive_copy:use', $context);

    $PAGE->set_context($context);
    $PAGE->set_url(new moodle_url('/local/lernhive_copy/index.php',
        $source->is_template() ? ['source' => 'template'] : []));
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title(get_string('pluginname', 'local_lernhive_copy'));
    $PAGE->set_heading(get_string('pluginname', 'local_lernhive_copy'));
}

/** @var \local_lernhive_copy\output\renderer $renderer */
$renderer = $PAGE->get_renderer('local_lernhive_copy');

echo $OUTPUT->header();
echo $renderer->render_wizard_page(new wizard_page($source));
echo $OUTPUT->footer();
