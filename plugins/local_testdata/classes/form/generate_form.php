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
 * Generate test data form.
 *
 * @package    local_testdata
 * @copyright  2024 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_testdata\form;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

class generate_form extends \moodleform {

    public function definition(): void {
        $mform = $this->_form;

        // ---- Dataset info ----
        $mform->addElement('header', 'datasethdr', get_string('gen_dataset_header', 'local_testdata'));

        $mform->addElement('text', 'datasetname', get_string('gen_dataset_name', 'local_testdata'), ['size' => 40]);
        $mform->setType('datasetname', PARAM_ALPHANUMEXT);
        $mform->addRule('datasetname', null, 'required');
        $mform->setDefault('datasetname', 'testdata_' . date('Ymd_His'));

        $mform->addElement('text', 'datasetdesc', get_string('gen_dataset_desc', 'local_testdata'), ['size' => 60]);
        $mform->setType('datasetdesc', PARAM_TEXT);

        // ---- Template selection ----
        $mform->addElement('header', 'templatehdr', get_string('gen_template_header', 'local_testdata'));

        $templates = $this->get_available_templates();
        $mform->addElement('select', 'template', get_string('gen_template', 'local_testdata'), $templates);
        $mform->addHelpButton('template', 'gen_template', 'local_testdata');

        // ---- Users ----
        $mform->addElement('header', 'usershdr', get_string('gen_users_header', 'local_testdata'));

        $mform->addElement('advcheckbox', 'gen_users', get_string('gen_users_enable', 'local_testdata'));
        $mform->setDefault('gen_users', 1);

        $mform->addElement('text', 'user_count', get_string('gen_users_count', 'local_testdata'), ['size' => 5]);
        $mform->setType('user_count', PARAM_INT);
        $mform->setDefault('user_count', 10);
        $mform->disabledIf('user_count', 'gen_users', 'notchecked');
        $mform->disabledIf('user_count', 'template', 'neq', 'none');

        $mform->addElement('text', 'user_password', get_string('gen_users_password', 'local_testdata'), ['size' => 20]);
        $mform->setType('user_password', PARAM_RAW);
        $mform->setDefault('user_password', 'Test1234!');
        $mform->disabledIf('user_password', 'gen_users', 'notchecked');

        $mform->addElement('text', 'user_prefix', get_string('gen_users_prefix', 'local_testdata'), ['size' => 20]);
        $mform->setType('user_prefix', PARAM_ALPHANUMEXT);
        $mform->setDefault('user_prefix', 'testuser');
        $mform->disabledIf('user_prefix', 'gen_users', 'notchecked');
        $mform->disabledIf('user_prefix', 'template', 'neq', 'none');

        // ---- Courses ----
        $mform->addElement('header', 'courseshdr', get_string('gen_courses_header', 'local_testdata'));

        $mform->addElement('advcheckbox', 'gen_courses', get_string('gen_courses_enable', 'local_testdata'));
        $mform->setDefault('gen_courses', 1);

        $mform->addElement('text', 'course_count', get_string('gen_courses_count', 'local_testdata'), ['size' => 5]);
        $mform->setType('course_count', PARAM_INT);
        $mform->setDefault('course_count', 2);
        $mform->disabledIf('course_count', 'gen_courses', 'notchecked');
        $mform->disabledIf('course_count', 'template', 'neq', 'none');

        $mform->addElement('text', 'course_prefix', get_string('gen_courses_prefix', 'local_testdata'), ['size' => 20]);
        $mform->setType('course_prefix', PARAM_TEXT);
        $mform->setDefault('course_prefix', 'Testkurs');
        $mform->disabledIf('course_prefix', 'gen_courses', 'notchecked');
        $mform->disabledIf('course_prefix', 'template', 'neq', 'none');

        // ---- Questions ----
        $mform->addElement('header', 'questionshdr', get_string('gen_questions_header', 'local_testdata'));

        $mform->addElement('advcheckbox', 'gen_questions', get_string('gen_questions_enable', 'local_testdata'));
        $mform->setDefault('gen_questions', 1);

        $mform->addElement('text', 'questions_per_course', get_string('gen_questions_per_course', 'local_testdata'), ['size' => 5]);
        $mform->setType('questions_per_course', PARAM_INT);
        $mform->setDefault('questions_per_course', 10);
        $mform->disabledIf('questions_per_course', 'gen_questions', 'notchecked');
        $mform->disabledIf('questions_per_course', 'template', 'neq', 'none');

        // ---- Activities ----
        $mform->addElement('header', 'activitieshdr', get_string('gen_activities_header', 'local_testdata'));

        $mform->addElement('advcheckbox', 'gen_activities', get_string('gen_activities_enable', 'local_testdata'));
        $mform->setDefault('gen_activities', 1);

        $activitytypes = [
            'leitnerflow' => 'LeitnerFlow',
        ];
        $mform->addElement('select', 'activity_type', get_string('gen_activity_type', 'local_testdata'), $activitytypes);
        $mform->disabledIf('activity_type', 'gen_activities', 'notchecked');

        // ---- Enrolments ----
        $mform->addElement('header', 'enrolhdr', get_string('gen_enrol_header', 'local_testdata'));

        $mform->addElement('advcheckbox', 'gen_enrol', get_string('gen_enrol_enable', 'local_testdata'));
        $mform->setDefault('gen_enrol', 1);

        $roles = ['student' => get_string('defaultcoursestudent'), 'teacher' => get_string('defaultcourseteacher')];
        $mform->addElement('select', 'enrol_role', get_string('gen_enrol_role', 'local_testdata'), $roles);
        $mform->disabledIf('enrol_role', 'gen_enrol', 'notchecked');

        // ---- Submit ----
        $this->add_action_buttons(true, get_string('gen_submit', 'local_testdata'));
    }

    /**
     * Get available JSON config templates.
     *
     * @return array Template options for select element.
     */
    private function get_available_templates(): array {
        global $CFG;

        $templates = ['none' => get_string('gen_template_none', 'local_testdata')];

        $configdir = __DIR__ . '/../../configs';
        if (is_dir($configdir)) {
            $files = glob($configdir . '/*.json');
            foreach ($files as $file) {
                $basename = basename($file, '.json');
                $json = json_decode(file_get_contents($file), true);
                $label = $json['description'] ?? $basename;
                $templates[$basename] = $label;
            }
        }

        return $templates;
    }

    /**
     * Form validation.
     */
    public function validation($data, $files): array {
        global $DB;

        $errors = parent::validation($data, $files);

        // Check dataset name uniqueness.
        if (!empty($data['datasetname'])) {
            if ($DB->record_exists('local_testdata_sets', ['name' => $data['datasetname']])) {
                $errors['datasetname'] = get_string('gen_error_name_exists', 'local_testdata');
            }
        }

        // If no template, must have at least one entity type enabled.
        if ($data['template'] === 'none') {
            if (empty($data['gen_users']) && empty($data['gen_courses'])) {
                $errors['gen_users'] = get_string('gen_error_nothing_selected', 'local_testdata');
            }
        }

        return $errors;
    }
}
