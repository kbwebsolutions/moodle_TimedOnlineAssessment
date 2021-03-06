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
 * Contains the event tests for the plugin.
 *
 * @package   assignsubmission_onlinetext
 * @copyright Titus
 * @author    Marcus Green
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace assignsubmission_timedonline;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/tests/generator.php');
use assignsubmission_timedonline\observer;
class events_test extends \advanced_testcase {

    // Use the generator helper.
    use \mod_assign_test_generator;

    /**
     * Test that the assessable_uploaded event is fired when an online text submission is saved.
     */
    public function test_submission_sweep() {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student1 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $student2 = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $params = [
               'assignsubmission_timedonline_enabled' => 1,
               'assignsubmission_timedonline_timelimit' => 1
         ];
        $assign = $this->create_instance($course, $params);
        $context = $assign->get_context();

        $this->setAdminUser();

        $submission1 = $assign->get_user_submission($student1->id, true);
        $submission2 = $assign->get_user_submission($student2->id, true);

        // Ensure they have run out of time so the draft will be submitted.
        $submissionparams = [
            'id' => $submission1->id,
            'timecreated' => (time() - 1626948853)
        ];
        $DB->update_record('assign_submission', $submissionparams);

        $submissionparams['id'] = $submission2->id;
        $DB->update_record('assign_submission', $submissionparams);

        $autosaveparams = [
            'elementid' => 'id_responsetext_editor',
            'userid' => $student1->id,
            'contextid' => $context->id,
            'drafttext' => ' '
        ];
        $DB->insert_record('editor_atto_autosave', $autosaveparams);

        $autosaveparams2 = $autosaveparams;
        $autosaveparams2['userid'] = $student2->id;
        $DB->insert_record('editor_atto_autosave', $autosaveparams2);
        $DB->delete_records('timedonline_status');

        $statusparams = [
            'userid' => $student1->id,
            'assignment' => $context->id,
            'timestarted' => (time() - 1626948853)
        ];
        $DB->insert_record('timedonline_status', $statusparams);
        $statusparams['userid'] = $student2->id;
        $DB->insert_record('timedonline_status', $statusparams);

        $sink = $this->redirectEvents();
        $task = \core\task\manager::get_scheduled_task('assignsubmission_timedonline\task\submission_sweep');
        $task->execute();
        $events = $sink->get_events();
        // Notification, Submitted, and Swept for two submissions.
        $this->assertCount(0, $events, 'Submission should not happen with blank draft text');
        $response = $DB->get_records('assignsubmission_timedonline');
        // No Submissions even though the time has expired because text was empty.
        $this->assertEmpty($response);
        $submissions = $DB->get_records_menu("assign_submission", [], '', 'id,status');
        foreach ($submissions as $status) {
            $this->assertEquals('new', $status);
        }

        $statusrecords = $DB->get_records('timedonline_status');
        foreach ($statusrecords as $record) {
            $DB->update_record('timedonline_status', ['id' => $record->id, 'timestarted' => (time() + 10000)]);
        }

        $autosaveparams['drafttext'] = '<p>student submission</p>';
        $autosaveparams2['drafttext'] = '<p>student submission</p>';

        $DB->delete_records('editor_atto_autosave');
        $DB->insert_record('editor_atto_autosave', $autosaveparams);
        $DB->insert_record('editor_atto_autosave', $autosaveparams2);

        $sink = $this->redirectEvents();
        $task = \core\task\manager::get_scheduled_task('assignsubmission_timedonline\task\submission_sweep');
        $task->execute();
        $events = $sink->get_events();
        // No events becauses no submissions made as time has not run out.
        $this->assertCount(0, $events, 'Submission should not happen until the time has run out');
        $response = $DB->get_records('assignsubmission_timedonline');
        $response = reset($response);
        $this->assertFalse($response);

        $statusrecords = $DB->get_records('timedonline_status');
        foreach ($statusrecords as $record) {
            $DB->update_record('timedonline_status', ['id' => $record->id, 'timestarted' => (time() - 1626948853)]);
        }

        $sink = $this->redirectEvents();
        $task = \core\task\manager::get_scheduled_task('assignsubmission_timedonline\task\submission_sweep');
        $task->execute();
        $events = $sink->get_events();

        $this->assertCount(6, $events, 'Submissions should have happene with non blank draft  text');
        $response = $DB->get_records('assignsubmission_timedonline');
        $response = reset($response);
        $this->assertEquals('<p>student submission</p>', $response->responsetext);
        $submissions = $DB->get_records_menu("assign_submission", [], '', 'id,status');
        foreach ($submissions as $status) {
            $this->assertEquals('submitted', $status);
        }
        // All draft text should be deleted after submission.
        $this->assertEmpty($DB->count_records('editor_atto_autosave'));
    }

    /**
     * Test that the submission_created event is fired when an timedonline submission is saved.
     */
    public function test_submission_created() {
        $this->resetAfterTest();
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $student1 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $student2 = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $assign = $this->create_instance($course);
        $context = $assign->get_context();
        // Should be a method but I don't know which.
        $instanceid = $DB->get_field('context', 'instanceid', ['id' => $context->id]);

        $submission1 = $assign->get_user_submission($student1->id, true);
        $data = (object) [
            'responsetext_editor' => [
                'text' => 'Submission text',
                'format' => FORMAT_HTML,
            ],
            'id' => $instanceid
        ];

        $sink = $this->redirectEvents();
        $plugin = $assign->get_submission_plugin_by_type('timedonline');

        $plugin->save($submission1, $data);
        $format = $plugin->get_editor_format('timedonline', $submission1->id);
        $this->assertEquals(FORMAT_HTML, $format);
        $events = $sink->get_events();

        // Attempt started, Asessable uploaded (?), Submission created.
        $this->assertCount(3, $events);
        $event = $events[2];
        $this->assertInstanceOf('\assignsubmission_timedonline\event\submission_created', $event);
        $this->assertEquals($context->id, $event->contextid);
        $this->assertEquals($course->id, $event->courseid);
        $this->assertEquals($submission1->id, $event->other['submissionid']);
        $this->assertEquals($submission1->attemptnumber, $event->other['submissionattempt']);
        $this->assertEquals($submission1->status, $event->other['submissionstatus']);
        $this->assertEquals($submission1->userid, $event->relateduserid);
    }
    public function test_set_status() {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $this->setUser($student);
        $assign = $this->create_instance($course);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $assign->view('editsubmission');
        $events = $sink->get_events();
        $event = reset($events);
        observer::set_status($event);
        $this->assertNotNull($DB->get_records('timedonline_status'));
    }

}
