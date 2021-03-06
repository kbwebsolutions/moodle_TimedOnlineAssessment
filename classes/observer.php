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
 * Event handler definition for local_btp.
 *
 * @package assignsubmission_timedonline
 * @author Marcus Green
 * @copyright 2021 Titus Learning
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace assignsubmission_timedonline;

defined('MOODLE_INTERNAL') || die;

use mod_assign\event\submission_form_viewed;
class observer {
    /**
     * Record the assignment attempt as started
     *
     * @param submission_form_viewed $event
     * @return void
     */
    public static function set_status(submission_form_viewed $event) {
        global $DB, $USER;
        $data = $event->get_data();
        $cminfo = get_coursemodule_from_id('assign', $data["contextinstanceid"]);
        $attempt = [
            'userid' => $USER->id,
            'assignment' => $cminfo->instance,
        ];
        if (!$DB->get_record('timedonline_status', $attempt)) {
            $attempt['timestarted'] = time();
            $DB->insert_record('timedonline_status', $attempt);
        }
    }

}
