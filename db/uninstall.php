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
 * Uninstallation code for the CUL Activity Stream local plugin
 *
 * @package    local
 * @subpackage culactivity_stream
 * @copyright  2013 Amanda Doughty <amanda.doughty.1@city.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Uninstall the plugin.
 *
 * @return boolean Always true (indicating success).
 */
function xmldb_local_culactivity_stream__uninstall() {
    global $DB;

    $dbman = $DB->get_manager();

    $table = new xmldb_table('message_culactivity_stream_q');
    if ($dbman->table_exists($table)) {
        $dbman->drop_table($table);
    }

    return true;
}
