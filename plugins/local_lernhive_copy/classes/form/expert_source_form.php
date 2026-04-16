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
 * Source picker for Expert copy mode.
 *
 * @package    local_lernhive_copy
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_copy\form;

use html_writer;
use moodleform;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Expert mode launcher form.
 *
 * This form only resolves the source course and then redirects into Moodle's
 * native copy workflow (`/backup/copy.php`) which exposes the full set of
 * advanced copy options.
 */
class expert_source_form extends moodleform {

    /**
     * Build the form definition.
     */
    public function definition() {
        $mform = $this->_form;
        $fixedsourcecourseid = (int) ($this->_customdata['fixedsourcecourseid'] ?? 0);
        $fixedsourcename = trim((string) ($this->_customdata['fixedsourcename'] ?? ''));

        if ($fixedsourcecourseid > 0) {
            $mform->addElement('hidden', 'courseid', $fixedsourcecourseid);
            $mform->setType('courseid', PARAM_INT);
            if ($fixedsourcename !== '') {
                $mform->addElement(
                    'static',
                    'selectedsource',
                    get_string('form_source_course', 'local_lernhive_copy'),
                    s($fixedsourcename)
                );
            }
        } else {
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
        }

        $mform->addElement(
            'static',
            'expertintro',
            '',
            html_writer::div(
                get_string('expert_intro', 'local_lernhive_copy'),
                'lh-copy-callout lh-copy-callout--info'
            )
        );

        $this->add_action_buttons(true, get_string('expert_submit', 'local_lernhive_copy'));
    }
}
