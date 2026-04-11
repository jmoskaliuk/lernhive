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
 * Unit tests for the LernHive Copy simple-mode form.
 *
 * These tests exercise the validation contract we actually care about:
 *  - the form rejects a duplicate target shortname
 *  - the form rejects a duplicate target idnumber
 *  - the form rejects enddate < startdate
 *  - valid submissions produce no errors
 *
 * We do NOT hit `copy_helper::create_copy()` here — that is Moodle core
 * code and has its own coverage. The integration between validated
 * formdata and the helper is covered in index.php, which is out of
 * unit-test scope.
 *
 * @package    local_lernhive_copy
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_copy\form;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

/**
 * @covers \local_lernhive_copy\form\copy_form
 */
final class copy_form_test extends advanced_testcase {

    /**
     * A fresh set of valid form data pointing at a freshly-generated
     * source course. Individual tests mutate the array to trigger the
     * specific validation path they want to cover.
     */
    private function valid_formdata(int $categoryid, int $sourcecourseid): array {
        return [
            'courseid'  => $sourcecourseid,
            'fullname'  => 'Copy target course',
            'shortname' => 'lh-copy-target-' . random_int(1000, 999999),
            'category'  => $categoryid,
            'visible'   => 1,
            'startdate' => strtotime('tomorrow'),
            'enddate'   => strtotime('tomorrow + 14 days'),
            'idnumber'  => '',
            'userdata'  => 0,
        ];
    }

    /**
     * Valid submission → empty error map.
     */
    public function test_valid_submission_produces_no_errors(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $source = $generator->create_course();
        $category = $generator->create_category();

        $data = $this->valid_formdata($category->id, (int) $source->id);

        // `moodleform::validation()` is protected, so we go through the
        // normal form entry point: feed the data via $_POST, build a
        // fresh form instance, and call `no_submit_button_pressed()` /
        // `get_data()` would trigger validation. Simpler: call the
        // static hack `copy_form::mock_submit()`-style helper using
        // advanced_testcase.
        copy_form::mock_submit($data);
        $form = new copy_form();
        $this->assertTrue($form->is_validated());
        $this->assertNotNull($form->get_data());
    }

    /**
     * Target shortname already exists → error on `shortname`.
     */
    public function test_duplicate_shortname_is_rejected(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $source = $generator->create_course();
        $category = $generator->create_category();
        $existing = $generator->create_course(['shortname' => 'lh-copy-clash']);

        $data = $this->valid_formdata($category->id, (int) $source->id);
        $data['shortname'] = $existing->shortname;

        copy_form::mock_submit($data);
        $form = new copy_form();
        $this->assertNull($form->get_data(), 'Form should refuse duplicate shortname.');
    }

    /**
     * Target idnumber already exists → error on `idnumber`.
     */
    public function test_duplicate_idnumber_is_rejected(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $source = $generator->create_course();
        $category = $generator->create_category();
        $existing = $generator->create_course(['idnumber' => 'LH-COPY-ID-CLASH']);

        $data = $this->valid_formdata($category->id, (int) $source->id);
        $data['idnumber'] = $existing->idnumber;

        copy_form::mock_submit($data);
        $form = new copy_form();
        $this->assertNull($form->get_data(), 'Form should refuse duplicate idnumber.');
    }

    /**
     * enddate < startdate → error on `enddate`.
     */
    public function test_enddate_before_startdate_is_rejected(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $source = $generator->create_course();
        $category = $generator->create_category();

        $data = $this->valid_formdata($category->id, (int) $source->id);
        $data['startdate'] = strtotime('tomorrow + 30 days');
        $data['enddate'] = strtotime('tomorrow + 1 day');

        copy_form::mock_submit($data);
        $form = new copy_form();
        $this->assertNull($form->get_data(), 'Form should refuse enddate < startdate.');
    }
}
