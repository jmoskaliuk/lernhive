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
 * R2.0 wires up the Simple copy flow for the course source: a
 * moodleform asks for source course, target category, and new
 * fullname/shortname, then hands the validated data to Moodle's
 * `\copy_helper::create_copy()` which queues an async copy task.
 *
 * Template source keeps the R1 stub — curated templates need a
 * separate catalogue integration tracked in `docs/04-tasks.md`.
 *
 * Copy is a content creation tool, not a site configuration page —
 * it always renders as a `standard` page with the LernHive Plugin
 * Shell, regardless of whether the visitor is a siteadmin or a
 * teacher. The plugin still registers itself in the admin tree via
 * settings.php so admins can discover it via the site-admin search,
 * but the page does NOT call admin_externalpage_setup() — that
 * would force pagelayout='admin' and layer the admin tab bar on top
 * of the Plugin Shell, which owns its own navigation.
 *
 * @package    local_lernhive_copy
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/backup/util/helper/copy_helper.class.php');

use local_lernhive_copy\form\copy_form;
use local_lernhive_copy\source;
use local_lernhive_copy\output\wizard_page;

// `optional_param` returns '' if the query param is missing; source::from_request
// treats any non-'template' value (including '') as the default course source.
$source = source::from_request(optional_param('source', '', PARAM_ALPHA));

$context = \core\context\system::instance();
$pageurl = new moodle_url(
    '/local/lernhive_copy/index.php',
    $source->is_template() ? ['source' => 'template'] : []
);

require_login();
require_capability('local/lernhive_copy:use', $context);

$PAGE->set_context($context);
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_lernhive_copy'));
$PAGE->set_heading(get_string('pluginname', 'local_lernhive_copy'));

// The Simple copy flow only applies to the course source for R2.0 —
// template copy reuses the R1 stub until the catalogue source is wired
// up. Building the form early lets `is_cancelled()` and `get_data()`
// short-circuit before we render anything.
$form = null;
if (!$source->is_template()) {
    $form = new copy_form($pageurl->out(false));

    if ($form->is_cancelled()) {
        redirect(new moodle_url('/local/lernhive_contenthub/index.php'));
    }

    if ($data = $form->get_data()) {
        // Normalise to the shape `copy_helper::process_formdata()`
        // requires. Casting here keeps the helper happy even if the
        // form element delivers strings (e.g. the course autocomplete
        // historically returned arrays under some configurations).
        $courseid = is_array($data->courseid)
            ? (int) reset($data->courseid)
            : (int) $data->courseid;

        // Double-check the user actually has backup rights on the
        // chosen source course — the form element already enforces
        // this but we don't trust client-side filtering alone.
        $sourcecontext = \core\context\course::instance($courseid, MUST_EXIST);
        require_capability('moodle/backup:backupcourse', $sourcecontext);
        require_capability('moodle/restore:restorecourse', $sourcecontext);

        $formdata = (object) [
            'courseid'  => $courseid,
            'fullname'  => $data->fullname,
            'shortname' => $data->shortname,
            'category'  => (int) $data->category,
            'visible'   => (int) $data->visible,
            'startdate' => (int) $data->startdate,
            'enddate'   => isset($data->enddate) ? (int) $data->enddate : 0,
            'idnumber'  => (string) ($data->idnumber ?? ''),
            'userdata'  => (int) $data->userdata,
        ];

        $processed = \copy_helper::process_formdata($formdata);
        \copy_helper::create_copy($processed);

        $progressurl = new moodle_url(
            '/backup/copyprogress.php',
            ['id' => $courseid]
        );
        redirect(
            $progressurl,
            get_string('form_queued', 'local_lernhive_copy'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
}

/** @var \local_lernhive_copy\output\renderer $renderer */
$renderer = $PAGE->get_renderer('local_lernhive_copy');

$formhtml = $form !== null ? $form->render() : null;

echo $OUTPUT->header();
echo $renderer->render_wizard_page(new wizard_page($source, $formhtml));
echo $OUTPUT->footer();
