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
 * CUL Activity Stream event handlers
 *
 * @package    local
 * @subpackage culactivity_stream
 * @copyright  2013 Amanda Doughty <amanda.doughty.1@city.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */


/**
 * local_culactivity_stream_cron()
 * Called periodically by the Moodle cron job.
 * @return void
 */
function local_culactivity_stream_cron() {
    local_culactivity_stream_process_queue();
}

function local_culactivity_stream_process_queue() {
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
            $context = $context = context_course::instance($message->courseid);
        } catch (Exception $e) {
            mtrace($e->getMessage());
        }

        if ($course && $context) {
            // For each user that can see this.
            $users = get_enrolled_users($context);

            foreach ($users as $user) {
                if ($course->visible
                    || has_capability('moodle/course:viewhiddencourses', $context, $user->id)) {
                    $modinfo = get_fast_modinfo($course, $user->id);
                    try {
                        $mod = $modinfo->get_cm($message->cmid);
                    } catch (Exception $e) {
                        mtrace("Invalid course module $message->cmid in $course->shortname");
                        // Update queue.
                        $message->sent = -1;
                        break;
                    }
                    if ($mod->uservisible) {
                        $message->userto = $user;
                        message_send($message);
                    }
                }
            }
        } else {
            mtrace("Invalid course id: $message->courseid");
            // Update queue.
            $message->sent = -1;
        }

        $result = $DB->update_record('message_culactivity_stream_q', $message);
    }

    return true;
}

