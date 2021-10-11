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
 * @package local_culactivity_stream
 * @category test
 * @copyright 2020 Amanda Doughty
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* vendor/bin/phpunit local/culactivity_stream/tests/message_cleanup_test.php */

defined('MOODLE_INTERNAL') || die();

use local_culactivity_stream\task\messaging_cleanup_task;

/**
 * Class for testing the message clean up task.
 *
 * @group culactivity
 *
 * @package local_culactivity_stream
 * @category test
 * @copyright 2020 Amanda Doughty
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_culactivity_stream_messaging_cleanup_testcase extends advanced_testcase {

    /**
     * Test that all queued and processed notifications are cleaned up.
     *
     * @return void
     */
    public function test_cleanup_all_notifications() {
        global $DB;

        $this->resetAfterTest();

        // Create the test data.
        $course = $this->getDataGenerator()->create_course();

        $user1 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $user2 = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $now = time();

        $eventdata = new \core\message\message();
        $eventdata->courseid = $course->id;
        $eventdata->name = 'gradenotifications';
        $eventdata->component = 'moodle';
        $eventdata->userfrom = $user2;
        $eventdata->subject = 'message subject';
        $eventdata->fullmessage = 'message body';
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml = '<p>message body</p>';
        $eventdata->smallmessage = 'small message';
        $eventdata->notification = 1;
        $eventdata->userto = $user1;

        // Send a message.
        message_send($eventdata);

        // Create a queued notification.
        $queueid1 = $this->send_fake_queued_culactivity_stream_notification(
            $user2,
            $course->id,
            'Message 1',
            $now
        );

        // Create a sent queued notification.
        $queueid2 = $this->send_fake_sent_culactivity_stream_notification(
            $user2,
            $course->id,
            'Message 1',
            $now
        );

        // We wait to obtain later timestamp.
        sleep(6);

        // Send a message.
        $eventdata->userfrom = $user1;
        $eventdata->userto = $user2;
        message_send($eventdata);

        // Create a queued notification.
        $queueid3 = $this->send_fake_queued_culactivity_stream_notification(
            $user2,
            $course->id,
            'Message 1',
            $now
        );

        // Sanity check.
        $this->assertEquals(2, $DB->count_records('message_culactivity_stream'));
        $this->assertEquals(3, $DB->count_records('message_culactivity_stream_q'));

        // Delete all notifications > 5 seconds old.
        set_config('messagingdeleteactivityfeeddelay', 5);
        (new messaging_cleanup_task())->execute();

        // We should have just one record now, matching the second message we sent.
        $records = $DB->get_records('message_culactivity_stream');

        $this->assertCount(1, $records);
        $this->assertEquals($user1->id, reset($records)->userfromid);

        // We should have two records now, matching the first and third queued notifications we sent.
        $records = $DB->get_records('message_culactivity_stream_q');

        $this->assertCount(2, $records);
        $this->assertEquals($queueid1, reset($records)->id);
        $this->assertEquals($queueid3, end($records)->id);
    }

    /**
     * Send a fake culactivity_stream notification to queue.
     *
     * @param stdClass $userfrom user object of the one sending the message.
     * @param int $courseid course id of the course updated.
     * @param string $message message to send.
     * @param int $timecreated time the message was created.
     * @return int the id of the message
     */
    protected function send_fake_queued_culactivity_stream_notification(
        $userfrom,
        $courseid,
        $message = 'Hello world!',
        $timecreated = 0
    ) {
        global $DB, $CFG;

        $record = new stdClass();
        $record->useridfrom = $userfrom->id;
        $record->courseid = $courseid;
        $record->cmid = 100999;
        $record->smallmessage = $message;
        $record->component = 'local_culactivity_stream';
        $record->modulename = 'Fake activity';
        $record->timecreated = $timecreated ? $timecreated : time();
        $record->contexturl = "$CFG->wwwroot/mod/fake/view.php?id=100999";
        $record->contexturlname  = 'Fake activity';

        $id = $DB->insert_record('message_culactivity_stream_q', $record);

        return $id;
    }

    /**
     * Send a fake sent culactivity_stream notification to queue.
     *
     * @param stdClass $userfrom user object of the one sending the message.
     * @param int $courseid course id of the course updated.
     * @param string $message message to send.
     * @param int $timecreated time the message was created.
     * @return int the id of the message
     */
    protected function send_fake_sent_culactivity_stream_notification(
        $userfrom,
        $courseid,
        $message = 'Hello world!',
        $timecreated = 0
    ) {
        global $DB, $CFG;

        $record = new stdClass();
        $record->useridfrom = $userfrom->id;
        $record->courseid = $courseid;
        $record->cmid = 100999;
        $record->smallmessage = $message;
        $record->component = 'local_culactivity_stream';
        $record->modulename = 'Fake activity';
        $record->timecreated = $timecreated ? $timecreated : time();
        $record->contexturl = "$CFG->wwwroot/mod/fake/view.php?id=100999";
        $record->contexturlname  = 'Fake activity';
        $record->sent = 1;

        $id = $DB->insert_record('message_culactivity_stream_q', $record);

        return $id;
    }
}
