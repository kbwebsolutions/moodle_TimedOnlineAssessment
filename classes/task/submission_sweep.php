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
 * Sweep submissions that were not submitted by the user.
 *
 * @package    assignsubmission_timedonline
 * @copyright  2022 Titus {@link http://www.tituslearning.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace assignsubmission_timedonline\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/locallib.php');
/**
 * Extends core scheduled task
 *
 * @package    assignsubmission_timedonline
 * @copyright  2022 Titus {@link http://www.tituslearning.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submission_sweep extends \core\task\scheduled_task {

    /**
     * Called automatically by cron
     */
    public function execute() {
        if (get_config('assignsubmission_timedonline', 'enable_submission_sweep')) {
            if ($overtimesubmission = $this->get_submissions()) {
                $this->submit_assign($overtimesubmission);
            }
        }
    }

    /**
     * Submit assignments that have data in atto_autosave
     *
     * @param array $overtimesubmission
     * @return void
     */
    public function submit_assign(array $overtimesubmission) : void {
        global $DB;
        foreach ($overtimesubmission as $submission) {
            $sql = "SELECT eas.drafttext
                      FROM {editor_atto_autosave} eas
                      JOIN {context} ctx
                        ON ctx.id =  eas.contextid
                      JOIN {course_modules} cm
                        ON cm.id = ctx.instanceid
                     WHERE cm.instance = :assignment
                       AND eas.id IN (
                              SELECT MAX(id)
                                FROM {editor_atto_autosave}
                               WHERE elementid = 'id_responsetext_editor'
                                 AND userid = :userid
                    )";
            $params = ['userid' => $submission->userid, 'assignment' => $submission->assignment ];
            $autosave = $DB->get_record_sql($sql, $params);
            if (!isset($autosave->drafttext) || ctype_space($autosave->drafttext)) {
                continue;
            }
            $data = (object) [
                "assignment" => $submission->assignment,
                "submission" => $submission->submissionid,
                "responsetext" => $autosave->drafttext,
                "responsetextformat" => FORMAT_HTML,
                "timestarted" => time(),
                "timefinished" => time()
            ];

            $DB->insert_record("assignsubmission_timedonline", $data);
            $data = (object)[
                'submission' => $submission->assignment,
                'userid' => $submission->userid
            ];

            $assignrecord = $DB->get_record('assign', ['id' => $submission->assignment], '*', MUST_EXIST);
            list($course, $cm) = get_course_and_cm_from_instance($assignrecord->id, 'assign');
            $context = \context_module::instance($cm->id);
            $assign = new \assign($context, $cm, $course);

            // Log entry to indicate the submission was done via the swept event.
            $user = \core_user::get_user($submission->userid);
            $other = 'Timelimit exceeded, text from editor buffer swept for grading';
            $eventdata = [
                'context' => $context,
                'relateduserid' => $user->id,
                'other' => $other
            ];
            $sweptevent = \assignsubmission_timedonline\event\submission_swept::create($eventdata);
            $sweptevent->trigger();

            // The actual submission, which does its own logging.
            $assign->submit_for_grading($data, []);
        }
    }
    /**
     * Get submissions that have gone overtime
     *
     * @return array
     */
    public function get_submissions() : array {
        global $DB;
        $sqlnewsubmissions = "SELECT DISTINCT asub.id As submissionid
                FROM {assign_submission} asub
                JOIN {assign_plugin_config} apc
                  ON apc.assignment = asub.assignment
                JOIN {timedonline_status} tos
                  ON tos.userid = asub.userid
               WHERE (asub.status = 'new'
                  OR asub.status = 'draft')
                 AND apc.plugin = 'timedonline'";
        $newsubmissions = $DB->get_records_sql($sqlnewsubmissions);
        $ids = array_keys($newsubmissions);
        list($insql, $inparams) = $DB->get_in_or_equal($ids);
        if ($newsubmissions) {
            $sql = "SELECT asub.id As submissionid, asub.userid, asub.assignment
                      FROM {assign_submission} asub
                      JOIN {assign_plugin_config} apc
                        ON apc.assignment = asub.assignment
                     WHERE apc.name = 'timelimit'
                       AND plugin = 'timedonline'
                       AND (asub.timecreated + (apc.value * 60)) < ?
                       AND asub.id ". $insql;
            array_unshift($inparams, time());
            $overtimesubmission = $DB->get_records_sql($sql, $inparams);
            return $overtimesubmission;
        } else {
            return [];
        }

    }
    /**
     * Required by base class
     *
     * @return string
     */
    public function get_name() {
        return get_string("submissionsweep", "assignsubmission_timedonline");
    }
}
