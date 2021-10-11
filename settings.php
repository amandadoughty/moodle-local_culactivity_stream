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
 * CUL Activity Stream admin settings.
 *
 * @package    local_culactivity_stream
 * @copyright  2013 Amanda Doughty <amanda.doughty.1@city.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_culactivity_stream', 'CUL Activity Stream');
    $ADMIN->add('localplugins', $settings);

    $options = [
        DAYSECS => new lang_string('secondstotime86400'),
        WEEKSECS => new lang_string('secondstotime604800'),
        2620800 => new lang_string('nummonth', 'moodle', 1),
        15724800 => new lang_string('nummonths', 'moodle', 6),
        31449600 => new lang_string('numyear', 'moodle', 1),
        0 => new lang_string('never')
    ];
    $settings->add(new admin_setting_configselect(
        'messagingdeleteactivityfeeddelay',
        new lang_string('messagingdeleteactivityfeeddelay', 'local_culactivity_stream'),
        new lang_string('configmessagingdeleteactivityfeeddelay', 'local_culactivity_stream'),
        31449600,
        $options
    ));
}
