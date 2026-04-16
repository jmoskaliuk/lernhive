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
 * Serves two related ContentHub paths:
 *   - Copy card:     no ?source=        -> source::TYPE_COURSE
 *   - Template card: ?source=template   -> source::TYPE_TEMPLATE
 *
 * Course source supports two modes:
 *   - Simple mode: in-plugin form -> copy_helper::create_copy()
 *   - Expert mode: source picker -> redirect to core /backup/copy.php
 *
 * Template source is wired to local_lernhive_library's catalog backend.
 * Users first pick a template, then continue in Simple or Expert mode.
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
use local_lernhive_copy\form\expert_source_form;
use local_lernhive_copy\output\wizard_page;
use local_lernhive_copy\source;

/** @var string User preference key for the default target category. */
const LOCAL_LERNHIVE_COPY_DEFAULT_CATEGORY_PREFERENCE = 'local_lernhive_copy_default_category';

/**
 * Normalise mode input from query parameters.
 *
 * @param string $raw
 * @return string
 */
function local_lernhive_copy_normalise_mode(string $raw): string {
    return $raw === 'expert' ? 'expert' : 'simple';
}

/**
 * Build wizard URLs while preserving source + mode semantics.
 *
 * @param source $source
 * @param string $mode
 * @param string $templateid
 * @return moodle_url
 */
function local_lernhive_copy_build_wizard_url(source $source, string $mode, string $templateid = ''): moodle_url {
    $params = [];
    if ($source->is_template()) {
        $params['source'] = 'template';
        if ($templateid !== '') {
            $params['templateid'] = $templateid;
        }
    }
    if ($mode === 'expert') {
        $params['mode'] = 'expert';
    }

    return new moodle_url('/local/lernhive_copy/index.php', $params);
}

/**
 * Normalise courseid values from moodleform payloads.
 *
 * @param mixed $courseid
 * @return int
 */
function local_lernhive_copy_normalise_courseid(mixed $courseid): int {
    if (is_array($courseid)) {
        return (int) reset($courseid);
    }
    return (int) $courseid;
}

// `optional_param` returns '' if the query param is missing; source::from_request
// treats any non-'template' value (including '') as the default course source.
$source = source::from_request(optional_param('source', '', PARAM_ALPHA));
$mode = local_lernhive_copy_normalise_mode(optional_param('mode', 'simple', PARAM_ALPHA));
$templateid = $source->is_template() ? trim(optional_param('templateid', '', PARAM_ALPHANUMEXT)) : '';

$context = \core\context\system::instance();
$returnurl = new moodle_url('/local/lernhive_contenthub/index.php');
$pageurl = local_lernhive_copy_build_wizard_url($source, $mode, $templateid);

require_login();
require_capability('local/lernhive_copy:use', $context);

$PAGE->set_context($context);
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_lernhive_copy'));
$PAGE->set_heading(get_string('pluginname', 'local_lernhive_copy'));

$templateentries = [];
$templatewarning = '';
$activetemplate = '';
$fixedsourcecourseid = 0;
$fixedsourcename = '';

if ($source->is_template()) {
    if (!class_exists('\\local_lernhive_library\\catalog')) {
        $templatewarning = get_string('template_library_missing', 'local_lernhive_copy');
    } else {
        /** @var \local_lernhive_library\catalog $catalog */
        $catalog = new \local_lernhive_library\catalog();
        $supportstemplatemapping = method_exists($catalog, 'find_by_id')
            && class_exists('\\local_lernhive_library\\catalog_entry')
            && method_exists('\\local_lernhive_library\\catalog_entry', 'has_source_course');

        if (!$supportstemplatemapping) {
            $templatewarning = get_string('template_library_unsupported', 'local_lernhive_copy');
        }

        foreach ($catalog->all() as $entry) {
            $entryctx = $entry->to_template_context();
            $hasaction = false;
            if ($supportstemplatemapping && $entry->has_source_course()) {
                $hasaction = true;
            }
            if ($hasaction && !$DB->record_exists('course', ['id' => (int) $entry->sourcecourseid])) {
                $hasaction = false;
            }
            $templateentries[] = [
                'id' => $entryctx['id'],
                'title' => $entryctx['title'],
                'description' => $entryctx['description'],
                'version' => $entryctx['version'],
                'updated' => $entryctx['updated'],
                'language' => $entryctx['language'],
                'hasaction' => $hasaction,
                'actionurl' => local_lernhive_copy_build_wizard_url($source, $mode, $entry->id)->out(false),
                'isactive' => ($templateid !== '' && $templateid === $entry->id),
            ];
        }

        if ($templateid !== '') {
            if (!$supportstemplatemapping) {
                $templatewarning = get_string('template_library_unsupported', 'local_lernhive_copy');
            } else {
                $selected = $catalog->find_by_id($templateid);
                if ($selected === null) {
                    $templatewarning = get_string('template_not_found', 'local_lernhive_copy', $templateid);
                } else if (!$selected->has_source_course()) {
                    $templatewarning = get_string('template_source_missing', 'local_lernhive_copy');
                } else {
                    $fixedsourcecourseid = (int) $selected->sourcecourseid;
                    if (!$DB->record_exists('course', ['id' => $fixedsourcecourseid])) {
                        $templatewarning = get_string('template_source_deleted', 'local_lernhive_copy');
                        $fixedsourcecourseid = 0;
                    } else {
                        $fixedsourcename = $selected->title;
                        $activetemplate = $selected->title;
                    }
                }
            }
        }
    }
}

$canrenderform = !$source->is_template() || $fixedsourcecourseid > 0;
$defaultcategory = (int) get_user_preferences(LOCAL_LERNHIVE_COPY_DEFAULT_CATEGORY_PREFERENCE, 0);
$form = null;

if ($canrenderform) {
    if ($mode === 'expert') {
        $form = new expert_source_form(
            $pageurl->out(false),
            [
                'fixedsourcecourseid' => $fixedsourcecourseid,
                'fixedsourcename' => $fixedsourcename,
            ]
        );

        if ($form->is_cancelled()) {
            redirect($returnurl);
        }

        if ($data = $form->get_data()) {
            $courseid = local_lernhive_copy_normalise_courseid($data->courseid);

            $sourcecontext = \core\context\course::instance($courseid, MUST_EXIST);
            require_capability('moodle/backup:backupcourse', $sourcecontext);
            require_capability('moodle/restore:restorecourse', $sourcecontext);

            redirect(new moodle_url('/backup/copy.php', ['id' => $courseid]));
        }
    } else {
        $form = new copy_form(
            $pageurl->out(false),
            [
                'fixedsourcecourseid' => $fixedsourcecourseid,
                'fixedsourcename' => $fixedsourcename,
                'defaultcategory' => $defaultcategory,
            ]
        );

        if ($form->is_cancelled()) {
            redirect($returnurl);
        }

        if ($data = $form->get_data()) {
            // Normalise to the shape `copy_helper::process_formdata()`
            // requires. Casting here keeps the helper happy even if the
            // form element delivers strings (e.g. the course autocomplete
            // historically returned arrays under some configurations).
            $courseid = local_lernhive_copy_normalise_courseid($data->courseid);

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

            if ($formdata->category > 0) {
                set_user_preference(
                    LOCAL_LERNHIVE_COPY_DEFAULT_CATEGORY_PREFERENCE,
                    $formdata->category
                );
            }

            $processed = \copy_helper::process_formdata($formdata);
            \copy_helper::create_copy($processed);

            $progressurl = new moodle_url('/backup/copyprogress.php', ['id' => $courseid]);
            redirect(
                $progressurl,
                get_string('form_queued', 'local_lernhive_copy'),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        }
    }
}

$viewstate = [
    'mode' => $mode,
    'showmodetoggle' => $canrenderform,
    'simpleurl' => local_lernhive_copy_build_wizard_url($source, 'simple', $templateid)->out(false),
    'experturl' => local_lernhive_copy_build_wizard_url($source, 'expert', $templateid)->out(false),
    'returnurl' => $returnurl->out(false),
    'showtemplatelist' => $source->is_template(),
    'templateempty' => empty($templateentries),
    'templateentries' => $templateentries,
    'templatewarning' => $templatewarning,
    'activetemplate' => $activetemplate,
];

/** @var \local_lernhive_copy\output\renderer $renderer */
$renderer = $PAGE->get_renderer('local_lernhive_copy');
$formhtml = $form !== null ? $form->render() : null;

echo $OUTPUT->header();
echo $renderer->render_wizard_page(new wizard_page($source, $formhtml, $viewstate));
echo $OUTPUT->footer();
