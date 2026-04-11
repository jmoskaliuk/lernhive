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
 * MoodleForm: LernHive Copy wizard — Simple mode.
 *
 * Unlike Moodle's built-in `\core_backup\output\copy_form`, which lives
 * inside an existing course page and receives the source course via
 * `$customdata['course']`, the LernHive entry point comes from the
 * ContentHub and does NOT have a source course in context. The form
 * therefore starts with a source-course autocomplete and produces a
 * formdata object compatible with `\copy_helper::process_formdata()`.
 *
 * @package    local_lernhive_copy
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_copy\form;

use context_system;
use core_course_category;
use DateTime;
use moodleform;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Simple-mode copy wizard form.
 *
 * Fields match the shape required by `\copy_helper::process_formdata()`:
 * courseid, fullname, shortname, category, visible, startdate, enddate,
 * idnumber, userdata. "Expert mode" (kept roles per source course) is
 * deferred to a later slice.
 */
class copy_form extends moodleform {

    /**
     * Build the form definition.
     */
    public function definition() {
        $mform = $this->_form;

        // Source course picker — uses the built-in autocomplete "course"
        // element. The element itself does not filter by capability; we
        // re-check `moodle/backup:backupcourse` server-side in index.php
        // before handing anything to the copy helper.
        $mform->addElement(
            'course',
            'courseid',
            get_string('form_source_course', 'local_lernhive_copy'),
            [
                'multiple' => false,
                'limittoenrolled' => false,
            ]
        );
        $mform->addRule('courseid', null, 'required', null, 'client');
        $mform->addHelpButton('courseid', 'form_source_course', 'local_lernhive_copy');

        // Destination fullname.
        $mform->addElement(
            'text',
            'fullname',
            get_string('form_target_fullname', 'local_lernhive_copy'),
            'maxlength="254" size="50"'
        );
        $mform->setType('fullname', PARAM_TEXT);
        $mform->addRule('fullname', null, 'required', null, 'client');

        // Destination shortname.
        $mform->addElement(
            'text',
            'shortname',
            get_string('form_target_shortname', 'local_lernhive_copy'),
            'maxlength="100" size="20"'
        );
        $mform->setType('shortname', PARAM_TEXT);
        $mform->addRule('shortname', null, 'required', null, 'client');

        // Destination category — only categories the user can create
        // courses in are listed.
        $categorylist = core_course_category::make_categories_list('moodle/course:create');
        if (empty($categorylist)) {
            // Form will refuse submission in validation() if this happens,
            // but we still need an element so the form renders at all.
            $categorylist = [0 => get_string('none')];
        }
        $mform->addElement(
            'autocomplete',
            'category',
            get_string('form_target_category', 'local_lernhive_copy'),
            $categorylist
        );
        $mform->addRule('category', null, 'required', null, 'client');

        // Visibility.
        $mform->addElement(
            'select',
            'visible',
            get_string('form_target_visible', 'local_lernhive_copy'),
            [
                0 => get_string('hide'),
                1 => get_string('show'),
            ]
        );
        $mform->setDefault('visible', 1);

        // Start date — default to tomorrow 00:00, same as Moodle core.
        $mform->addElement(
            'date_time_selector',
            'startdate',
            get_string('startdate')
        );
        $default = (new DateTime())->setTimestamp(usergetmidnight(time()));
        $default->modify('+1 day');
        $mform->setDefault('startdate', $default->getTimestamp());

        // End date (optional).
        $mform->addElement(
            'date_time_selector',
            'enddate',
            get_string('enddate'),
            ['optional' => true]
        );

        // ID number (optional).
        $mform->addElement(
            'text',
            'idnumber',
            get_string('idnumbercourse'),
            'maxlength="100" size="10"'
        );
        $mform->setType('idnumber', PARAM_RAW);
        $mform->setDefault('idnumber', '');

        // User data toggle — simple mode defaults to off. Frozen if the
        // user lacks the capabilities core backup/restore needs.
        $mform->addElement(
            'select',
            'userdata',
            get_string('form_include_userdata', 'local_lernhive_copy'),
            [
                0 => get_string('no'),
                1 => get_string('yes'),
            ]
        );
        $mform->setDefault('userdata', 0);
        $mform->addHelpButton('userdata', 'form_include_userdata', 'local_lernhive_copy');

        $context = context_system::instance();
        $required = [
            'moodle/restore:createuser',
            'moodle/backup:userinfo',
            'moodle/restore:userinfo',
        ];
        if (!has_all_capabilities($required, $context)) {
            $mform->hardFreeze('userdata');
            $mform->setConstant('userdata', 0);
        }

        $this->add_action_buttons(true, get_string('form_submit', 'local_lernhive_copy'));
    }

    /**
     * Server-side validation.
     *
     * Mirrors the duplicate-shortname/idnumber checks from Moodle core's
     * `copy_form` plus date ordering. We intentionally do NOT re-check
     * capabilities here — that happens in index.php via
     * `require_capability()`.
     *
     * @param array $data  Submitted form data.
     * @param array $files Submitted files.
     * @return array Field → error message map; empty on success.
     */
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        // Target shortname must be globally unique.
        if (!empty($data['shortname'])) {
            $existing = $DB->get_record(
                'course',
                ['shortname' => $data['shortname']],
                'fullname',
                IGNORE_MULTIPLE
            );
            if ($existing) {
                $errors['shortname'] = get_string(
                    'shortnametaken',
                    '',
                    $existing->fullname
                );
            }
        }

        // Target idnumber (if set) must also be globally unique.
        if (!empty($data['idnumber'])) {
            $existing = $DB->get_record(
                'course',
                ['idnumber' => $data['idnumber']],
                'fullname',
                IGNORE_MULTIPLE
            );
            if ($existing) {
                $errors['idnumber'] = get_string(
                    'courseidnumbertaken',
                    'error',
                    $existing->fullname
                );
            }
        }

        // End date (if supplied) must be after start date. Moodle core
        // has `course_validate_dates()` which returns an error string
        // code on failure — reuse it so our form behaves the same.
        if (function_exists('course_validate_dates')) {
            if ($errorcode = course_validate_dates($data)) {
                $errors['enddate'] = get_string($errorcode, 'error');
            }
        }

        return $errors;
    }
}
