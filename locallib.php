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
 * This file contains the definition for the library class for timedonline submission plugin
 *
 * This class provides all the functionality for the new assign module.
 *
 * @package assignsubmission_timedonline
 * @copyright 2021 Titus {@link http://www.tituslearning.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
// File area for online text submission assignment.
define('ASSIGNSUBMISSION_TIMEDONLINE_FILEAREA', 'submissions_timedonline');

/*
* @package assignsubmission_timedonline
* @copyright 2021 Titus {@link http://www.tituslearning.com}
* @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
class assign_submission_timedonline extends assign_submission_plugin {

    /**
     * Get the name of the online text submission plugin
     * @return string
     */
    public function get_name() {
        return get_string('timedonline', 'assignsubmission_timedonline');
    }


    /**
     * Get timedonline submission information from the database
     *
     * @param  int $submissionid
     * @return mixed
     */
    private function get_timedonline_submission($submissionid) {
        global $DB;
        $sql = "SELECT *
                  FROM {assignsubmission_timedonline}
                 WHERE submission = :submissionid
              ORDER BY id DESC";
        return $DB->get_record_sql($sql, ['submissionid' => $submissionid], IGNORE_MULTIPLE);
    }

    /**
     * Remove a submission. Triggered
     * from menu option in gradebook
     *
     * @param stdClass $submission The submission
     * @return boolean
     */
    public function remove(stdClass $submission) {
        global $DB;

        $submissionid = $submission ? $submission->id : 0;
        if ($submissionid) {
            $DB->delete_records('assignsubmission_timedonline', ['submission' => $submissionid]);
            $DB->delete_records('assign_submission', ['id' => $submissionid]);
            $DB->delete_records('timedonline_status', ['assignment' => $submission->assignment, 'userid' => $submission->userid]);
        }
        return true;
    }

    /**
     * Get the settings for timedonline submission plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
        global $PAGE;
        // The hideIf doesn't work with editor fields see https://tracker.moodle.org/browse/MDL-53848.
        // And also  https://moodle.org/mod/forum/discuss.php?d=401937.
        // So this bit of js does the hide/reveal.
        $PAGE->requires->js_call_amd('assignsubmission_timedonline/editform', 'init');

        $mform->addElement('text', 'assignsubmission_timedonline_timelimit',
         get_string('timelimit', 'assignsubmission_timedonline'),
         ['maxlength' => 4, 'size' => 4]);
        $mform->addHelpButton('assignsubmission_timedonline_timelimit', 'timelimit', 'assignsubmission_timedonline');
        $mform->addRule('assignsubmission_timedonline_timelimit', null, 'numeric', null, 'client');

        $mform->setDefault('assignsubmission_timedonline_timelimit',
         $this->get_config('timelimit') == 0 ? '' : $this->get_config('timelimit'));
        $mform->setType('assignsubmission_timedonline_timelimit', PARAM_FLOAT);

        $mform->addElement('editor', 'assignsubmission_timedonline_questiontext',
        get_string('questiontext', 'assignsubmission_timedonline'));
        $mform->setDefault('assignsubmission_timedonline_questiontext', [
            'text' => ($this->get_config('questiontext') == '0' ? '' : $this->get_config('questiontext'))
        ]);
        $mform->setType('assignsubmission_timedonline_questiontext', PARAM_RAW);

        $mform->addHelpButton('assignsubmission_timedonline_questiontext', 'questiontext', 'assignsubmission_timedonline');
        $mform->hideIf('assignsubmission_timedonline_timelimit',
        'assignsubmission_timedonline_enabled',
        'notchecked');
    }

    /**
     * Save the settings for timedonline submission plugin
     *
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $data) :?bool {

        if (empty($data->assignsubmission_timedonline_timelimit)) {
            $timelimit = 0;
        } else {
            $timelimit = $data->assignsubmission_timedonline_timelimit;
        }
        if (empty($data->assignsubmission_timedonline_questiontext['text'])) {
            $questiontext = '';
        } else {
            $questiontext = $data->assignsubmission_timedonline_questiontext['text'];
        }

        $this->set_config('timelimit', $timelimit);
        $this->set_config('questiontext', $questiontext);

        return true;
    }
    /**
     * Add form elements for settings
     *
     * @param mixed $submission can be null
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return true if elements were added to the form
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {
        global $OUTPUT, $PAGE;

        $editoroptions = $this->get_edit_options();
        $submissionid = $submission ? $submission->id : 0;

        if (!isset($data->responsetext)) {
            $data->responsetext = '';
        }
        if (!isset($data->responsetextformat)) {
            $data->responsetextformat = editors_get_preferred_format();
        }

        if ($submission) {
            $timedonlinesubmission = $this->get_timedonline_submission($submission->id);
            if ($timedonlinesubmission) {
                $data->responsetext = $timedonlinesubmission->responsetext;
                $data->responsetextformat = $timedonlinesubmission->responsetextformat;
            }

        }

        $questiontext = $this->get_config('questiontext') == '0' ? '' : $this->get_config('questiontext');
        $instanceid = $this->assignment->get_context()->instanceid;
        $timer = $OUTPUT->render_from_template('assignsubmission_timedonline/timer', null);
        $mform->addElement('html', $timer);
        $assignmentid = $this->assignment->get_instance()->id;

        $endtime = $this->get_endtime();
        $PAGE->requires->js_call_amd('assignsubmission_timedonline/timer', 'init', [$endtime, $assignmentid, $instanceid]);

        $html = $OUTPUT->render_from_template('assignsubmission_timedonline/question', ['questiontext' => $questiontext]);
        $mform->addElement('html', $html);
        $PAGE->requires->js_call_amd('assignsubmission_timedonline/attempt', 'init');

        $data = file_prepare_standard_editor($data,
                                             'responsetext',
                                             $editoroptions,
                                             $this->assignment->get_context(),
                                             'assignsubmission_timedonline',
                                             ASSIGNSUBMISSION_TIMEDONLINE_FILEAREA,
                                             $submissionid);

         $mform->addElement('editor', 'responsetext_editor',
         get_string('addyourresponse', 'assignsubmission_timedonline'), ['rows' => 40]);

         return true;
    }


    /**
     * Editor format options
     *
     * @return array
     */
    private function get_edit_options() {
        $editoroptions = array(
            'noclean' => false,
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $this->assignment->get_course()->maxbytes,
            'context' => $this->assignment->get_context(),
            'return_types' => (FILE_INTERNAL | FILE_EXTERNAL | FILE_CONTROLLED_LINK),
            'removeorphaneddrafts' => true // Whether or not to remove any draft files which aren't referenced in the text.
        );
        return $editoroptions;
    }

    /**
     * Save the students response data to the database
     * when making an attempt at the assignment
     *
     * @param stdClass $submission
     * @param stdClass $data from course_modules table
     * @return bool
     */
    public function save(stdClass $submission, stdClass $data) {
        global $USER, $DB, $PAGE;
        $cminfo = get_coursemodule_from_id('assign', $data->id);
        $params = [
            'userid' => $USER->id,
            'assign' => $cminfo->instance
        ];
        $timestarted = $this->get_timestarted($USER, $this->assignment);
        $timeranout = (time() > ($timestarted + $this->get_config('timelimit') * 60)) ? true : false;
        $timesubmitted = $this->get_time_submitted($USER, $this->assignment);
        if ($timeranout) {
            \core\notification::add(get_string("timeranout", "assignsubmission_timedonline"), \core\notification::WARNING);
            if ($timesubmitted) {
                $params = $PAGE->url->remove_params('action');
                redirect(new moodle_url($PAGE->url->out_omit_querystring(), ['id' => $params['id']]));
            }

        }

        $editoroptions = $this->get_edit_options();

        $data = file_postupdate_standard_editor($data,
                                                'responsetext',
                                                $editoroptions,
                                                $this->assignment->get_context(),
                                                'assignsubmission_timedonline',
                                                ASSIGNSUBMISSION_TIMEDONLINE_FILEAREA,
                                                $submission->id);

        $timedonlinesubmission = $this->get_timedonline_submission($submission->id);

        $fs = get_file_storage();

        $files = $fs->get_area_files($this->assignment->get_context()->id,
                                     'assignsubmission_timedonline',
                                     ASSIGNSUBMISSION_TIMEDONLINE_FILEAREA,
                                     $submission->id,
                                     'id',
                                     false);

        $params = [
            'context' => context_module::instance($this->assignment->get_course_module()->id),
            'courseid' => $this->assignment->get_course()->id,
            'objectid' => $submission->id,
            'other' => [
                'pathnamehashes' => array_keys($files),
                'content' => trim($data->responsetext),
                'format' => $data->responsetext_editor['format']
             ]
        ];
        if (!empty($submission->userid) && ($submission->userid != $USER->id)) {
            $params['relateduserid'] = $submission->userid;
        }
        if ($this->assignment->is_blind_marking()) {
            $params['anonymous'] = 1;
        }
        $event = \assignsubmission_timedonline\event\assessable_uploaded::create($params);
        $event->trigger();

        $groupname = null;
        $groupid = 0;
        // Get the group name as other fields are not transcribed in the logs and this information is important.
        if (empty($submission->userid) && !empty($submission->groupid)) {
            $groupname = $DB->get_field('groups', 'name', array('id' => $submission->groupid), MUST_EXIST);
            $groupid = $submission->groupid;
        } else {
            $params['relateduserid'] = $submission->userid;
        }

        // Unset the objectid and other field from params for use in submission events.
        unset($params['objectid']);
        unset($params['other']);
        $params['other'] = array(
            'submissionid' => $submission->id,
            'submissionattempt' => $submission->attemptnumber,
            'submissionstatus' => $submission->status,
            'groupid' => $groupid,
            'groupname' => $groupname
        );

        if ($timedonlinesubmission) {
            $timedonlinesubmission->responsetext = $data->responsetext;
            $timedonlinesubmission->responsetextformat = $data->responsetext_editor['format'];
            $params['objectid'] = $timedonlinesubmission->id;
            $updatestatus = $DB->update_record('assignsubmission_timedonline', $timedonlinesubmission);
            $event = assignsubmission_timedonline\event\submission_updated::create($params);
            $event->set_assign($this->assignment);
            $event->trigger();
            return $updatestatus;
        } else {

            $timedonlinesubmission = new stdClass();
            $timedonlinesubmission->responsetext = $data->responsetext;
            $timedonlinesubmission->responsetextformat = $data->responsetext_editor['format'];

            $timedonlinesubmission->submission = $submission->id;
            $timedonlinesubmission->assignment = $this->assignment->get_instance()->id;
            $timedonlinesubmission->id = $DB->insert_record('assignsubmission_timedonline', $timedonlinesubmission);
            $params['objectid'] = $timedonlinesubmission->id;
            $event = \assignsubmission_timedonline\event\submission_created::create($params);
            $event->set_assign($this->assignment);
            $event->trigger();
            return $timedonlinesubmission->id > 0;
        }

    }

    /**
     * Return a list of the text fields that can be imported/exported by this plugin
     *
     * @return array An array of field names and descriptions. (name=>description, ...)
     */
    public function get_editor_fields() {
        return ['responsetext' => get_string('pluginname', 'assignsubmission_timedonline')];
    }

    /**
     * Get the saved text content from the editor
     *
     * @param string $name
     * @param int $submissionid
     * @return string
     */
    public function get_editor_text($name, $submissionid) {
        if ($name == 'responsetext') {
            $timedonlinesubmission = $this->get_timedonline_submission($submissionid);
            if ($timedonlinesubmission) {
                return $timedonlinesubmission->responsetext;
            }
        }

        return '';
    }

    /**
     * Get the content format for the editor
     * This will always be FORMAT_HTML
     * as the plugin assumes the use of atto
     *
     * @param string $name
     * @param int $submissionid
     * @return int
     */
    public function get_editor_format($name, $submissionid) {
        if ($name == 'timedonline') {
            $timedonlinesubmission = $this->get_timedonline_submission($submissionid);
            if ($timedonlinesubmission) {
                return $timedonlinesubmission->responsetextformat;
            }
        }

        return 0;
    }


     /**
      * If word count was being used it would be included here
      *
      * @param stdClass $submission
      * @param bool $showviewlink - If the summary has been truncated set this to true
      * @return string
      */
    public function view_summary(stdClass $submission, & $showviewlink) {

        $timedonlinesubmission = $this->get_timedonline_submission($submission->id);
        // Always show the view link.
        $showviewlink = true;

        if ($timedonlinesubmission) {
            // This contains the shortened version of the text plus an optional 'Export to portfolio' button.
            $text = $this->assignment->render_editor_content(ASSIGNSUBMISSION_TIMEDONLINE_FILEAREA,
                                                             $timedonlinesubmission->submission,
                                                             $this->get_type(),
                                                             'responsetext',
                                                             'assignsubmission_timedonline', true);
            return $text;
        }
        return '';
    }

    /**
     * Produce a list of files suitable for export that represent this submission.
     * This may be legacy code as this plugin does not accept the submission of
     * files. On the other hand the onlinetext submission type has this code.
     *
     * @param stdClass $submission - For this is the submission data
     * @param stdClass $user - This is the user record for this submission
     * @return array - return an array of files indexed by filename
     */
    public function get_files(stdClass $submission, stdClass $user) {

        $files = array();
        $timedonlinesubmission = $this->get_timedonline_submission($submission->id);

        // Note that this check is the same logic as the result from the is_empty function but we do
        // not call it directly because we already have the submission record.
        if ($timedonlinesubmission) {
            // Do not pass the text through format_text. The result may not be displayed in Moodle and
            // may be passed to external services such as document conversion or portfolios.
            $formattedtext = $this->assignment->download_rewrite_pluginfile_urls($timedonlinesubmission->responsetext,
             $user, $this);
            $head = '<head><meta charset="UTF-8"></head>';
            $submissioncontent = '<!DOCTYPE html><html>' . $head . '<body>'. $formattedtext . '</body></html>';

            $filename = get_string('timedonlinefilename', 'assignsubmission_timedonline');
            $files[$filename] = array($submissioncontent);

            $fs = get_file_storage();

            $fsfiles = $fs->get_area_files($this->assignment->get_context()->id,
                                           'assignsubmission_timedonline',
                                           ASSIGNSUBMISSION_TIMEDONLINE_FILEAREA,
                                           $submission->id,
                                           'timemodified',
                                           false);

            foreach ($fsfiles as $file) {
                $files[$file->get_filename()] = $file;
            }
        }

        return $files;
    }

    /**
     * Display the saved text content from the editor in the view table
     *
     * @param stdClass $submission
     * @return string
     */
    public function view(stdClass $submission) {
        global $CFG;
        $result = '';

        $timedonlinesubmission = $this->get_timedonline_submission($submission->id);

        if ($timedonlinesubmission) {

            // Render for portfolio API.
            $result .= $this->assignment->render_editor_content(ASSIGNSUBMISSION_TIMEDONLINE_FILEAREA,
                                                                $timedonlinesubmission->submission,
                                                                $this->get_type(),
                                                                'responsetext',
                                                                'assignsubmission_timedonline');

            $plagiarismlinks = '';

            if (!empty($CFG->enableplagiarism)) {
                require_once($CFG->libdir . '/plagiarismlib.php');

                $plagiarismlinks .= plagiarism_get_links(array('userid' => $submission->userid,
                    'content' => trim($timedonlinesubmission->responsetext),
                    'cmid' => $this->assignment->get_course_module()->id,
                    'course' => $this->assignment->get_course()->id,
                    'assignment' => $submission->assignment));
            }
        }

        return $plagiarismlinks . $result;
    }

    /**
     * Return true if this plugin can upgrade an old Moodle 2.2 assignment of this type and version.
     *
     * @param string $type old assignment subtype
     * @param int $version old assignment version
     * @return bool True if upgrade is possible
     */
    public function can_upgrade($type, $version) {
        if ($type == 'online' && $version >= 2011112900) {
            return true;
        }
        return false;
    }


    /**
     * Upgrade the settings from the old assignment to the new plugin based one
     *
     * @param context $oldcontext - the database for the old assignment context
     * @param stdClass $oldassignment - the database for the old assignment instance
     * @param string $log record log events here
     * @return bool Was it a success?
     */
    public function upgrade_settings(context $oldcontext, stdClass $oldassignment, & $log) {
        // No settings to upgrade.
        return true;
    }


    /**
     * Formatting for log info
     *
     * @param stdClass $submission The new submission
     * @return string
     */
    public function format_for_log(stdClass $submission) {
        // Format the info for each submission plugin (will be logged).
        $timedonlinesubmission = $this->get_timedonline_submission($submission->id);
        return $timedonlinesubmission->responsetext;
    }

    /**
     * The assignment has been deleted - cleanup
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        $DB->delete_records('assignsubmission_timedonline',
                            array('assignment' => $this->assignment->get_instance()->id));

        return true;
    }

    /**
     * No text is set for this plugin
     *
     * @param stdClass $submission
     * @return bool
     */
    public function is_empty(stdClass $submission) {

        $timedonlinesubmission = $this->get_timedonline_submission($submission->id);
        $wordcount = 0;
        $hasinsertedresources = false;

        if (isset($timedonlinesubmission->responsetext)) {
            $wordcount = count_words(trim($timedonlinesubmission->responsetext));
            // Check if the online text submission contains video, audio or image elements
            // that can be ignored and stripped by count_words().
            $hasinsertedresources = preg_match('/<\s*((video|audio)[^>]*>(.*?)<\s*\/\s*(video|audio)>)|(img[^>]*>(.*?))/',
                    trim($timedonlinesubmission->responsetext));
        }

        return  $wordcount == 0 && !$hasinsertedresources;
    }

    /**
     * Determine if a submission is empty
     *
     * This is distinct from is_empty in that it is intended to be used to
     * determine if a submission made before saving is empty.
     *
     * @param stdClass $data The submission data
     * @return bool
     */
    public function submission_is_empty(stdClass $data) {
        if (!isset($data->responsetext_editor)) {
            return true;
        }
        $wordcount = 0;
        $hasinsertedresources = false;

        if (isset($data->responsetext_editor['text'])) {
            $wordcount = count_words(trim((string)$data->responsetext_editor['text']));
            // Check if the online text submission contains video, audio or image elements
            // that can be ignored and stripped by count_words().
            $hasinsertedresources = preg_match('/<\s*((video|audio)[^>]*>(.*?)<\s*\/\s*(video|audio)>)|(img[^>]*>(.*?))/',
                    trim((string)$data->responsetext_editor['text']));
        }

        return $wordcount == 0 && !$hasinsertedresources;
    }

    /**
     * Get file areas returns a list of areas this plugin stores files
     * @return array - An array of fileareas (keys) and descriptions (values)
     */
    public function get_file_areas() {
        return array(assignsubmission_timedonline_FILEAREA => $this->get_name());
    }

    /**
     * Copy the student's submission from a previous submission. Used when a student opts to base their resubmission
     * on the last submission.
     * @param stdClass $sourcesubmission
     * @param stdClass $destsubmission
     */
    public function copy_submission(stdClass $sourcesubmission, stdClass $destsubmission) {
        global $DB;

        // Copy the files across (attached via the text editor).
        $contextid = $this->assignment->get_context()->id;
        $fs = get_file_storage();
        $files = $fs->get_area_files($contextid, 'assignsubmission_timedonline',
        ASSIGNSUBMISSION_TIMEDONLINE_FILEAREA, $sourcesubmission->id, 'id', false);
        foreach ($files as $file) {
            $fieldupdates = array('itemid' => $destsubmission->id);
            $fs->create_file_from_storedfile($fieldupdates, $file);
        }

        // Copy the assignsubmission_timedonline record.
        $timedonlinesubmission = $this->get_timedonline_submission($sourcesubmission->id);
        if ($timedonlinesubmission) {
            unset($timedonlinesubmission->id);
            $timedonlinesubmission->submission = $destsubmission->id;
            $DB->insert_record('assignsubmission_timedonline', $timedonlinesubmission);
        }
        return true;
    }

    /**
     * Return a description of external params suitable for uploading an timedonline submission from a webservice.
     *
     * @return external_description|null
     */
    public function get_external_parameters() {
        $editorparams = array('text' => new external_value(PARAM_RAW, 'The text for this submission.'),
                              'format' => new external_value(PARAM_INT, 'The format for this submission'),
                              'itemid' => new external_value(PARAM_INT, 'The draft area id for files attached to the submission'));
        $editorstructure = new external_single_structure($editorparams, 'Editor structure', VALUE_OPTIONAL);
        return array('responsetext_editor' => $editorstructure);
    }

    /**
     * Compare word count of timedonline submission to word limit, and return result.
     *
     * @param string $submissiontext timedonline submission text from editor
     * @return string Error message if limit is enabled and exceeded, otherwise null
     */
    public function check_word_count($submissiontext) {
        global $OUTPUT;

        $wordlimitenabled = $this->get_config('wordlimitenabled');
        $wordlimit = $this->get_config('wordlimit');

        if ($wordlimitenabled == 0) {
            return null;
        }

        // Count words and compare to limit.
        $wordcount = count_words($submissiontext);
        if ($wordcount <= $wordlimit) {
            return null;
        } else {
            $errormsg = get_string('wordlimitexceeded', 'assignsubmission_timedonline',
                    array('limit' => $wordlimit, 'count' => $wordcount));
            return $OUTPUT->error_text($errormsg);
        }
    }

    /**
     * Return the plugin configs for external functions.
     *
     * @return array the list of settings
     * @since Moodle 3.2
     */
    public function get_config_for_external() {
        return (array) $this->get_config();
    }

    /**
     * Get time assignment was submitted based on the status being
     * 'submitted' and the time of last modification
     *
     * @param stdClass $user
     * @param assign $assignment
     * @return int
     */
    public function get_time_submitted(stdClass $user, assign $assignment) :int {
        global $DB;
        $assignmentid = $assignment->get_instance()->id;

        $sql = "SELECT timemodified
                    AS timesubmitted
                  FROM {assign_submission}
                 WHERE userid = :userid
                   AND assignment = :assignment
                   AND status = 'submitted'
              ORDER BY timecreated DESC";

        $params = [
            'userid' => $user->id,
            'assignment' => $assignmentid
        ];
        return ($DB->get_field_sql($sql, $params, IGNORE_MULTIPLE));

    }

    /**
     * Get the time the attempt was started from the timedonline_status
     * table. If there is no value, insert time now and return it
     * @param stdClass $user
     * @param assign $assignment
     * @return int
     */
    public function get_timestarted(stdClass $user, assign $assignment) :int {
        global $DB, $USER;
        $assignmentid = $assignment->get_instance()->id;

        $params = [
            'userid' => $user->id,
            'assignment' => $assignmentid
        ];
        if (!$DB->get_record('timedonline_status', $params)) {
            $params['timestarted'] = time();
            $cm = get_coursemodule_from_instance('assign', $assignmentid);
            $context = \context_module::instance($cm->id);
            $eventdata = [
                'context' => $context,
                'relateduserid' => $USER->id,
                'other' => 'Attempt started'
            ];
            $startevent = \assignsubmission_timedonline\event\attempt_started::create($eventdata);
            $startevent->trigger();
            $DB->insert_record('timedonline_status', $params);
            unset($params['timestarted']);
        }

        $timestarted = $DB->get_field('timedonline_status', 'timestarted', $params);
        return $timestarted;
    }

    /**
     * get code for end time for current user.
     * Used in js for countdown clock
     * @return float|int
     * @throws dml_exception
     */
    public function get_endtime() {
        global $DB, $USER;
        $timestarted = $this->get_timestarted($USER, $this->assignment);

        $params = [
                'name' => 'timelimit',
                'plugin' => 'timedonline',
                'assignment' => $this->assignment->get_instance()->id
        ];

        $minutes = $DB->get_record('assign_plugin_config', $params);
        $endtime = $timestarted + ($minutes->value * 60);
        return $endtime;
    }

}
