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
 * Tests the message clean up task.
 *
 * @group culactivity
 *
 * @package local_culactivity_stream
 * @category test
 * @copyright 2020 Amanda Doughty
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* vendor/bin/phpunit local/culactivity_stream/tests/process_queue_test.php */

defined('MOODLE_INTERNAL') || die();

use local_culactivity_stream\task\process_queue;

/**
 * Class for testing the process queue task.
 *
 * @package local_culactivity_stream
 * @category test
 * @copyright 2020 Amanda Doughty
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_culactivity_stream_process_queue_testcase extends advanced_testcase {

    /**
     * Test that all queued notifications are processed.
     *
     * @return void
     */
    public function test_process_queue () {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create course and modules.
        $course = $this->getDataGenerator()->create_course(array('numsections' => 5));

        // Create enrolments.
        $user1 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $user2 = $this->getDataGenerator()->create_and_enrol($course, 'student');

        // Generate an assignment to create a course_module_created event.
        $assign = $this->getDataGenerator()->create_module('assign', array('course' => $course->id));

        // Run the task.
        (new process_queue())->execute();

        // The queue should be marked as sent.
        $records = $DB->get_records('message_culactivity_stream_q');

        $this->assertCount(1, $records);
        $this->assertEquals(1, reset($records)->sent);

        // We should have two message records.
        $records = $DB->get_records('notifications');
        $msgtxt = "{$assign->name} created in {$course->fullname}.";

        $this->assertCount(2, $records);

        $users = [];
        $message1 = reset($records);
        $users[] = $message1->useridto;
        $message2 = next($records);
        $users[] = $message2->useridto;

        $this->assertEquals($msgtxt, $message1->fullmessage);
        $this->assertTrue(in_array($user1->id, $users));
        $this->assertEquals($msgtxt, $message2->fullmessage);
        $this->assertTrue(in_array($user2->id, $users));
    }
}
