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
// php admin/tool/task/cli/schedule_task.php --execute=\\local_culactivity_stream\\task\\messaging_cleanup_task
// .

/**
 * Simple task to delete old messaging records.
 */
class messaging_cleanup_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskmessagingcleanup', 'local_culactivity_stream');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $CFG, $DB;

        $timenow = time();

        // Cleanup messaging.
        if (!empty($CFG->messagingdeleteactivityfeeddelay)) {
            $notificationdeletetime = $timenow - $CFG->messagingdeleteactivityfeeddelay;
            $params = array('notificationdeletetime' => $notificationdeletetime);
            $DB->delete_records_select('message_culactivity_stream_q', 'sent=1 AND timecreated<:notificationdeletetime', $params);
            $DB->delete_records_select('message_culactivity_stream', 'timecreated<:notificationdeletetime', $params);
        }

    }

}
