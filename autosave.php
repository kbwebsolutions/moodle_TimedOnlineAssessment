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
 * Auto save triggered from timer.js ajax call
 *
 * @package    assignsubmission_timedonline
 * @author     Marcus Green/Scott Braithwaite
 * @copyright  Titus 2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/assign/externallib.php');
require_login();

if (!defined('AJAX_SCRIPT')) {
    define('AJAX_SCRIPT', true);
}

global $USER;

$responsetext = optional_param('responsetext', '', PARAM_RAW);
$assignmentid = optional_param('assignmentid', '', PARAM_INT);

$params = ['assignment' => $assignmentid, 'groupid' => 0, 'userid' => $USER->id];
try {

    $other = 'Timelimit exceeded, response submitted by javascript';
    $cm = get_coursemodule_from_instance('assign', $assignmentid);
    $context = \context_module::instance($cm->id);
    $eventdata = [
        'context' => $context,
        'relateduserid' => $USER->id,
        'other' => $other
    ];

    $sql = 'SELECT *
              FROM {assign_submission}
             WHERE assignment = :assignment
               AND groupid = :groupid
               AND userid = :userid
               AND status != "submitted"
          ORDER BY attemptnumber DESC';
    $submissions = $DB->get_records_sql($sql, $params, 0, 1);
    if (! $submissions) {
        return false;
    }
    $submission = reset($submissions);
    $autosubmitevent = \assignsubmission_timedonline\event\auto_submitted::create($eventdata);
    $autosubmitevent->trigger();

    $data = (object) [
        "assignment" => $assignmentid,
        "submission" => $submission->id,
        "responsetext" => $responsetext,
        "responsetextformat" => FORMAT_HTML,
        "timestarted" => time(),
        "timefinished" => time()
    ];

    $DB->insert_record("assignsubmission_timedonline", $data);
    mod_assign_external::submit_for_grading($assignmentid, true);
} catch (Exception $e) {
    echo('Unhandled AJAX');
}
