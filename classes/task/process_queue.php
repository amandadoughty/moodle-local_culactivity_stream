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
 * CUL Activity Stream scheduled task.
 *
 * @package    local_culactivity_stream
 * @copyright  2013 Amanda Doughty <amanda.doughty.1@city.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

namespace local_culactivity_stream\task;

// To run on command line:
// php admin/tool/task/cli/schedule_task.php --execute=\\local_culactivity_stream\\task\\process_queue
// .

class process_queue extends \core\task\scheduled_task {

    public function get_name() {
        // Shown in admin screens.
        return get_string('processqueue', 'local_culactivity_stream');
    }

    public function execute() {
        global $CFG, $DB;
        // Get the messages from the queue.
        $messages = $DB->get_recordset('message_culactivity_stream_q', array('sent' => 0));

        // Loop through messages.
        foreach ($messages as $message) {
            // Required.
            $message->name = 'course_updates';
            $message->userfrom = $DB->get_record('user', array('id' => $message->userfromid));
            $message->subject = $message->smallmessage;
            $message->fullmessage = $message->smallmessage;
            $message->fullmessageformat = FORMAT_PLAIN;
            $message->fullmessagehtml = $message->smallmessage;
            // Update queue.
            $message->sent = 1;
            // Optional.
            $message->notification = 1;

            try {
                $course = $DB->get_record('course', array('id' => $message->courseid));
                $context = $context = \context_course::instance($message->courseid);
            } catch (\Exception $e) {
                mtrace($e->getMessage());
            }

            if ($course && $context) {
                // For each user that can see this.
                $users = get_enrolled_users($context);
                $countsent = 0;

                foreach ($users as $user) {
                    if ($course->visible
                        || has_capability('moodle/course:viewhiddencourses', $context, $user->id)) {
                        $modinfo = get_fast_modinfo($course, $user->id);
                        try {
                            $mod = $modinfo->get_cm($message->cmid);
                        } catch (\Exception $e) {
                            mtrace("Invalid course module $message->cmid in $course->shortname");
                            // Update queue.
                            $message->sent = -1;
                            break;
                        }
                        if ($mod->uservisible) {
                            $message->userto = $user;
                            message_send($message);
                            $countsent++;
                        }
                    }
                }
            } else {
                mtrace("Invalid course id: $message->courseid");
                // Update queue.
                $message->sent = -1;
            }

            mtrace("$countsent users were notified of '$message->smallmessage'");
            $result = $DB->update_record('message_culactivity_stream_q', $message);
        }

        return true;
    }
}

