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
 * Privacy provider tests.
 *
 * @package    local_culactivity_stream
 * @copyright  2019 Amanda Doughty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_culactivity_stream;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\deletion_criteria;
use local_culactivity_stream\privacy\provider;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider tests class.
 *
 * @group culactivity
 *
 * @package    local_culactivity_stream
 * @copyright  2019 Amanda Doughty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class privacy_provider_test extends \core_privacy\tests\provider_testcase {
    /** @var stdClass The teacher object. */
    protected $teacher;

    /** @var \core\event\course_module_created The event object. */
    protected $event;

    /** @var stdClass The course object. */
    protected $course;

    /** @var stdClass The choice object. */
    protected $choice;

    /**
     * {@inheritdoc}
     */
    protected function setUp () : void {
        $this->resetAfterTest();

        global $DB, $CFG, $USER;

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        // Create a teacher who will create the choice.
        $teacher = $generator->create_user();
        $USER = $teacher;
        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher']);
        $generator->enrol_user($teacher->id,  $course->id, $teacherrole->id);

        $options = ['fried rice', 'spring rolls', 'sweet and sour pork', 'satay beef', 'gyouza'];
        $params = [
            'course' => $course->id,
            'option' => $options,
            'name' => 'First Choice Activity',
            'showpreview' => 0
        ];

        // A choice activity.
        $plugingenerator = $generator->get_plugin_generator('mod_choice');
        $choice = $plugingenerator->create_instance($params);
        $this->teacher = $teacher;
        $this->course = $course;
        $this->choice = $choice;
    }

    /**
     * Test for provider::get_metadata().
     */
    public function test_get_metadata() {
        $collection = new collection('local_culactivity_stream');
        $newcollection = provider::get_metadata($collection);
        $itemcollection = $newcollection->get_collection();
        $this->assertCount(2, $itemcollection);

        $table = reset($itemcollection);
        $this->assertEquals('message_culactivity_stream_q', $table->get_name());

        $privacyfields = $table->get_privacy_fields();
        $this->assertArrayHasKey('sent', $privacyfields);
        $this->assertArrayHasKey('userfromid', $privacyfields);
        $this->assertArrayHasKey('courseid', $privacyfields);
        $this->assertArrayHasKey('cmid', $privacyfields);
        $this->assertArrayHasKey('smallmessage', $privacyfields);
        $this->assertArrayHasKey('component', $privacyfields);
        $this->assertArrayHasKey('modulename', $privacyfields);
        $this->assertArrayHasKey('timecreated', $privacyfields);
        $this->assertArrayHasKey('contexturl', $privacyfields);
        $this->assertArrayHasKey('contexturlname', $privacyfields);
    }

    /**
     * Test for provider::get_contexts_for_userid().
     */
    public function test_get_contexts_for_userid() {
        $contextlist = provider::get_contexts_for_userid($this->teacher->id);
        $this->assertCount(1, $contextlist);
        $contextforuser = $contextlist->current();
        $context = \context_user::instance($this->teacher->id);
        $this->assertEquals($context->id, $contextforuser->id);
    }

    /**
     * Test for provider::export_user_data().
     */
    public function test_export_for_context() {
        $context = \context_user::instance($this->teacher->id);

        // Export all of the data for the context.
        $this->export_context_data_for_user($this->teacher->id, $context, 'local_culactivity_stream');
        $writer = \core_privacy\local\request\writer::with_context($context);
        $this->assertTrue($writer->has_any_data());
    }

    /**
     * Test for provider::delete_data_for_all_users_in_context().
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB;

        // Delete data based on context.
        $context = \context_user::instance($this->teacher->id);
        provider::delete_data_for_all_users_in_context($context);

        // After deletion, all queued messages should be deleted.
        $count = $DB->count_records('message_culactivity_stream_q');
        $this->assertEquals(0, $count);
    }

    /**
     * Test for provider::delete_data_for_user().
     */
    public function test_delete_data_for_user() {
        global $DB, $USER, $CFG;

        $generator = $this->getDataGenerator();
        // Create a teacher who will update the choice.
        $teacher2 = $generator->create_user();
        $USER = $teacher2;
        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher']);
        $generator->enrol_user($teacher2->id,  $this->course->id, $teacherrole->id);
        $cm = get_coursemodule_from_instance('choice', $this->choice->id);
        $cm->name = 'First Choice Activity V2';

        $options = ['fried rice', 'spring rolls', 'sweet and sour pork', 'satay beef', 'gyouza'];
        $params = [
            'course' => $this->course->id,
            'option' => $options,
            'name' => 'Second Choice Activity',
            'showpreview' => 0
        ];

        $plugingenerator = $generator->get_plugin_generator('mod_choice');
        $choice = $plugingenerator->create_instance($params);

        // Before deletion, we should have 2 messages.
        $count = $DB->count_records('message_culactivity_stream_q');
        $this->assertEquals(2, $count);

        $context = \context_user::instance($this->teacher->id);

        $contextlist = new \core_privacy\local\request\approved_contextlist($this->teacher, 'local_culactivity_stream',
            [$context->id]);

        provider::delete_data_for_user($contextlist);

        // After deletion, the messages from the first teacher should have been deleted.
        $count = $DB->count_records('message_culactivity_stream_q', ['userfromid' => $this->teacher->id]);
        $this->assertEquals(0, $count);

        // Confirm that we only have one message available.
        $messages = $DB->get_records('message_culactivity_stream_q');
        $this->assertCount(1, $messages);
        $lastmessage = reset($messages);
        // And that it's the other teacher's response.
        $this->assertEquals($teacher2->id, $lastmessage->userfromid);
    }

    /**
     * Test for provider::get_users_in_context().
     */
    public function test_get_users_in_context() {
        $context = \context_user::instance($this->teacher->id);

        $userlist = new \core_privacy\local\request\userlist($context, 'local_culactivity_stream');
        \local_culactivity_stream\privacy\provider::get_users_in_context($userlist);

        $this->assertEquals(
                [$this->teacher->id],
                $userlist->get_userids()
        );
    }

    /**
     * Test for provider::get_users_in_context() with invalid context type.
     */
    public function test_get_users_in_context_invalid_context_type() {
        $cm = get_coursemodule_from_instance('choice', $this->choice->id);
        $cmcontext = \context_module::instance($cm->id);
        $userlist = new \core_privacy\local\request\userlist($cmcontext, 'local_culactivity_stream');
        \local_culactivity_stream\privacy\provider::get_users_in_context($userlist);

        $this->assertCount(0, $userlist->get_userids());
    }

    /**
     * Test for provider::delete_data_for_users().
     */
    public function test_delete_data_for_users() {
        global $DB, $USER, $CFG;

        $generator = $this->getDataGenerator();
        // Create a teacher who will update the choice.
        $teacher2 = $generator->create_user();
        $USER = $teacher2;
        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher']);
        $generator->enrol_user($teacher2->id,  $this->course->id, $teacherrole->id);
        $cm = get_coursemodule_from_instance('choice', $this->choice->id);
        $cm->name = 'First Choice Activity V2';

        $options = ['fried rice', 'spring rolls', 'sweet and sour pork', 'satay beef', 'gyouza'];
        $params = [
            'course' => $this->course->id,
            'option' => $options,
            'name' => 'Second Choice Activity',
            'showpreview' => 0
        ];

        $plugingenerator = $generator->get_plugin_generator('mod_choice');
        $choice = $plugingenerator->create_instance($params);

        // Before deletion, we should have 2 messages.
        $notifications = $DB->count_records('message_culactivity_stream_q');
        $this->assertEquals(2, $notifications);

        $context = \context_user::instance($this->teacher->id);

        $approveduserlist = new \core_privacy\local\request\approved_userlist($context, 'local_culactivity_stream',
                [$this->teacher->id, $teacher2->id]);
        provider::delete_data_for_users($approveduserlist);

        $notifications = $DB->get_records('message_culactivity_stream_q');

        $this->assertCount(1, $notifications);
    }
}
