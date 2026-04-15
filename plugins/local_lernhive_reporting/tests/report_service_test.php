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
 * Unit tests for report_service.
 *
 * @package    local_lernhive_reporting
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lernhive_reporting;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

/**
 * @covers \local_lernhive_reporting\report_service
 */
final class report_service_test extends advanced_testcase {

    /**
     * Active enrolments are counted, suspended users are excluded.
     */
    public function test_get_user_count_for_course_counts_only_active_users(): void {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $active = $generator->create_user();
        $suspended = $generator->create_user(['suspended' => 1]);

        $generator->enrol_user($active->id, $course->id, 'student');
        $generator->enrol_user($suspended->id, $course->id, 'student');

        $service = new report_service();

        $this->assertSame(1, $service->get_user_count_for_course((int)$course->id));
    }

    /**
     * Completion overview reports completed/pending split and percentage.
     */
    public function test_get_completion_for_course_returns_expected_metrics(): void {
        global $DB;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $u1 = $generator->create_user();
        $u2 = $generator->create_user();

        $generator->enrol_user($u1->id, $course->id, 'student');
        $generator->enrol_user($u2->id, $course->id, 'student');

        $DB->insert_record('course_completions', (object)[
            'userid'        => $u1->id,
            'course'        => $course->id,
            'timeenrolled'  => time(),
            'timestarted'   => time(),
            'timecompleted' => time(),
            'reaggregate'   => 0,
        ]);

        $service = new report_service();
        $metrics = $service->get_completion_for_course((int)$course->id);

        $this->assertSame(2, $metrics['participants']);
        $this->assertSame(1, $metrics['completed']);
        $this->assertSame(1, $metrics['pending']);
        $this->assertSame(50, $metrics['completionrate']);
    }

    /**
     * Popular courses are sorted by user count desc, then course name asc.
     */
    public function test_get_popular_courses_orders_by_user_count_then_name(): void {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $coursebeta = $generator->create_course(['fullname' => 'Beta Course']);
        $coursezeta = $generator->create_course(['fullname' => 'Zeta Course']);
        $coursealpha = $generator->create_course(['fullname' => 'Alpha Course']);

        $u1 = $generator->create_user();
        $u2 = $generator->create_user();
        $u3 = $generator->create_user();
        $u4 = $generator->create_user();

        // Beta: 2 users.
        $generator->enrol_user($u1->id, $coursebeta->id, 'student');
        $generator->enrol_user($u2->id, $coursebeta->id, 'student');

        // Zeta: 1 user.
        $generator->enrol_user($u3->id, $coursezeta->id, 'student');

        // Alpha: 1 user.
        $generator->enrol_user($u4->id, $coursealpha->id, 'student');

        $service = new report_service();
        $rows = $service->get_popular_courses(3);

        $this->assertCount(3, $rows);
        $this->assertSame('Beta Course', $rows[0]->coursename);
        $this->assertSame(2, $rows[0]->usercount);
        $this->assertSame('Alpha Course', $rows[1]->coursename);
        $this->assertSame('Zeta Course', $rows[2]->coursename);
    }

    /**
     * Completion table sorts by completed desc, then participants desc.
     */
    public function test_get_completion_table_orders_by_completed_then_participants(): void {
        global $DB;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $courseone = $generator->create_course(['fullname' => 'Course One']);
        $coursetwo = $generator->create_course(['fullname' => 'Course Two']);
        $coursethree = $generator->create_course(['fullname' => 'Course Three']);

        $users = [];
        for ($i = 0; $i < 7; $i++) {
            $users[] = $generator->create_user();
        }

        // Course one: 3 participants, 2 completed.
        $generator->enrol_user($users[0]->id, $courseone->id, 'student');
        $generator->enrol_user($users[1]->id, $courseone->id, 'student');
        $generator->enrol_user($users[2]->id, $courseone->id, 'student');

        // Course two: 2 participants, 2 completed.
        $generator->enrol_user($users[3]->id, $coursetwo->id, 'student');
        $generator->enrol_user($users[4]->id, $coursetwo->id, 'student');

        // Course three: 2 participants, 1 completed.
        $generator->enrol_user($users[5]->id, $coursethree->id, 'student');
        $generator->enrol_user($users[6]->id, $coursethree->id, 'student');

        $now = time();
        foreach ([$users[0], $users[1]] as $u) {
            $DB->insert_record('course_completions', (object)[
                'userid' => $u->id,
                'course' => $courseone->id,
                'timeenrolled' => $now,
                'timestarted' => $now,
                'timecompleted' => $now,
                'reaggregate' => 0,
            ]);
        }
        foreach ([$users[3], $users[4]] as $u) {
            $DB->insert_record('course_completions', (object)[
                'userid' => $u->id,
                'course' => $coursetwo->id,
                'timeenrolled' => $now,
                'timestarted' => $now,
                'timecompleted' => $now,
                'reaggregate' => 0,
            ]);
        }
        $DB->insert_record('course_completions', (object)[
            'userid' => $users[5]->id,
            'course' => $coursethree->id,
            'timeenrolled' => $now,
            'timestarted' => $now,
            'timecompleted' => $now,
            'reaggregate' => 0,
        ]);

        $service = new report_service();
        $rows = $service->get_completion_table(3);

        $this->assertCount(3, $rows);
        $this->assertSame('Course One', $rows[0]->coursename);
        $this->assertSame(3, $rows[0]->participants);
        $this->assertSame(2, $rows[0]->completed);
        $this->assertSame(67, $rows[0]->completionrate);

        $this->assertSame('Course Two', $rows[1]->coursename);
        $this->assertSame(2, $rows[1]->participants);
        $this->assertSame(2, $rows[1]->completed);
        $this->assertSame(100, $rows[1]->completionrate);

        $this->assertSame('Course Three', $rows[2]->coursename);
    }

    /**
     * Non-global users only see enrolled courses where they have reporting-relevant capabilities.
     */
    public function test_get_selectable_courses_filters_to_teaching_capability(): void {
        global $DB;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        $allowed = $generator->create_course(['fullname' => 'Allowed Course']);
        $denied = $generator->create_course(['fullname' => 'Denied Course']);

        $generator->enrol_user($user->id, $allowed->id, 'student');
        $generator->enrol_user($user->id, $denied->id, 'student');

        $editingteacherid = (int)$DB->get_field('role', 'id', ['shortname' => 'editingteacher']);
        role_assign($editingteacherid, $user->id, \core\context\course::instance($allowed->id)->id);

        $this->setUser($user);

        $service = new report_service();
        $courses = $service->get_selectable_courses();

        $this->assertArrayHasKey((int)$allowed->id, $courses);
        $this->assertArrayNotHasKey((int)$denied->id, $courses);
    }
}
