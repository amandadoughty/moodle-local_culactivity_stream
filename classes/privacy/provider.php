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
 * Privacy Subsystem implementation for local_culactivity_stream.
 *
 * @package    local_culactivity_stream
 * @copyright  2018 Amanda Doughty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_culactivity_stream\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy Subsystem implementation for local_culactivity_stream.
 *
 * @copyright  2018 Amanda Doughty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements 
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {

    use \core_privacy\local\legacy_polyfill;

    /**
     * Return the fields which contain personal data.
     *
     * @param collection $items a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function _get_metadata($items) {
        $items->add_database_table(
            'message_culactivity_stream_q',
            [
                'sent' => 'privacy:metadata:message_culactivity_stream_q:sent',
                'userfromid' => 'privacy:metadata:message_culactivity_stream_q:userfromid',
                'courseid' => 'privacy:metadata:message_culactivity_stream_q:courseid',
                'cmid' => 'privacy:metadata:message_culactivity_stream_q:cmid',
                'smallmessage' => 'privacy:metadata:message_culactivity_stream_q:smallmessage',
                'component' => 'privacy:metadata:message_culactivity_stream_q:component',
                'modulename' => 'privacy:metadata:message_culactivity_stream_q:modulename',
                'timecreated' => 'privacy:metadata:message_culactivity_stream_q:timecreated',
                'contexturl' => 'privacy:metadata:message_culactivity_stream_q:contexturl',
                'contexturlname' => 'privacy:metadata:message_culactivity_stream_q:contexturlname'
            ],
            'privacy:metadata:message_culactivity_stream_q'
        );

        $items->add_subsystem_link(
            'core_message',
            [],
            'privacy:metadata:core_message'
        );

        return $items;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function _get_contexts_for_userid($userid) {
        // Messages are in the system context.
        $contextlist = new contextlist();
        $contextlist->add_system_context();

        return $contextlist;
    }

    /**
     * Export personal data for the given approved_contextlist. User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function _export_user_data($contextlist) {
        if (empty($contextlist->count())) {
            return;
        }

        // Remove non-system contexts. If it ends up empty then early return.
        $contexts = array_filter($contextlist->get_contexts(), function($context) {
            return $context->contextlevel == CONTEXT_SYSTEM;
        });

        if (empty($contexts)) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        // Export the message_culactivity_stream_q.
        self::export_user_data_message_culactivity_stream_q($userid);
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function _delete_data_for_all_users_in_context($context) {
        global $DB;

        if (!$context instanceof \context_system) {
            return;
        }

        $DB->delete_records('message_culactivity_stream_q');
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function _delete_data_for_user($contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        // Remove non-system contexts. If it ends up empty then early return.
        $contexts = array_filter($contextlist->get_contexts(), function($context) {
            return $context->contextlevel == CONTEXT_SYSTEM;
        });

        if (empty($contexts)) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        $DB->delete_records_select('message_culactivity_stream_q', 'userfromid = ?', [$userid]);
    }

    /**
     * Export the notification data.
     *
     * @param int $userid
     */
    protected static function export_user_data_message_culactivity_stream_q($userid) {
        global $DB;

        $context = \context_system::instance();

        $notificationdata = [];
        $select = "userfromid = ?";
        $message_culactivity_stream_q = $DB->get_recordset_select('message_culactivity_stream_q', $select, [$userid, $userid], 'timecreated ASC');
        foreach ($message_culactivity_stream_q as $notification) {
            $data = (object) [
                'sent' => transform::yesno($notification->sent),
                'courseid' => $notification->courseid,
                'cmid' => $notification->cmid,
                'smallmessage' => $notification->smallmessage,
                'component' => $notification->component,
                'modulename' => $notification->modulename,
                'timecreated' => transform::datetime($notification->timecreated),
                'contexturl' => $notification->contexturl,
                'contexturlname' => $notification->contexturlname
            ];

            $notificationdata[] = $data;
        }
        $message_culactivity_stream_q->close();

        writer::with_context($context)->export_data([get_string('message_culactivity_stream_q', 'local_culactivity_stream')], (object) $notificationdata);
    }
}