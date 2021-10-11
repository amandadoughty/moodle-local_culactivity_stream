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
 * Tests the events observers.
 *
 * @package local_culactivity_stream
 * @category test
 * @copyright 2020 Amanda Doughty
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* vendor/bin/phpunit local/culactivity_stream/tests/event_observers_test.php */

defined('MOODLE_INTERNAL') || die();

/**
 * Class for testing the event observer.
 *
 * @group culactivity
 *
 * @package local_culactivity_stream
 * @category test
 * @copyright 2020 Amanda Doughty
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_culactivity_stream_event_observers_testcase extends advanced_testcase {

    /**
     * Test the course module update observer.
     *
     * @return void
     */
    public function test_schedule_notification () {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create course and modules.
        $course = $this->getDataGenerator()->create_course(array('numsections' => 5));

        // Generate an assignment to create a course_module_created event.
        $assign = $this->getDataGenerator()->create_module('assign', array('course' => $course->id));

        // Get the module context.
        $modcontext = context_module::instance($assign->cmid);

        // Get course module.
        $cm = get_coursemodule_from_id(null, $assign->cmid, $course->id, false, MUST_EXIST);

        // Create a course_module_updated event.
        $event = \core\event\course_module_updated::create_from_cm($cm, $modcontext);

        // Trigger the event.
        $event->trigger();

        // We should have two queued records.
        $records = $DB->get_records('message_culactivity_stream_q');
        $msgtxt1 = "{$assign->name} created in {$course->fullname}.";
        $msgtxt2 = "{$assign->name} updated in {$course->fullname}.";

        $this->assertCount(2, $records);
        $this->assertEquals($msgtxt1, reset($records)->smallmessage);
        $this->assertEquals($msgtxt2, end($records)->smallmessage);
    }
}
